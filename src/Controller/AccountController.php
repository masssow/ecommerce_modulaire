<?php
// src/Controller/AccountController.php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Order;
use App\Entity\Adresse;
use App\Entity\ReturnRequest;
use App\Form\AdresseTypeForm;
use App\Entity\UserPaymentMethod;
use App\Repository\OrderRepository;
use App\Repository\AdresseRepository;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserPaymentMethodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class AccountController extends AbstractController
{
    private const RETURN_WINDOW_DAYS = 14; // J+14 après livraison

    /* ========================= PROFIL ========================= */

    #[Route('/mon-compte', name: 'account_profile', methods: ['GET'])]
    public function profile(AdresseRepository $adresseRepo, OrderRepository $orderRepo, UserPaymentMethodRepository $walletRepo, FavoriteRepository $favorites ): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->getUser();

        // On fournit les adresses à la vue (ordre: défaut d’abord)
        $addresses = $adresseRepo->findBy(
            ['user' => $user],
            ['isDefault' => 'DESC', 'createdAt' => 'DESC']
        );
        $wallet = $walletRepo->findWalletForUser($user);
        $orders = $orderRepo->findByUser($user);
        $favList = $favorites->findForUserWithJoins($user);
        $favIds  = array_map(fn($f) => $f->getProductVariant()->getId(), $favList);
        return $this->render('account/index.html.twig', [
            'user'      => $user,
            'addresses' => $addresses,
            'orders'    => $orders,
            'wallet'    => $wallet,
        ]);
    }

    /* ========================= COMMANDES ====================== */

    #[Route('/account/orders', name: 'account_orders', methods: ['GET'])]
    public function orders(OrderRepository $orders): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->getUser();

        $list = $orders->findByUser($user);

        return $this->render('account/order/orders.html.twig', [
            'orders' => $list,
        ]);
    }

    #[Route('/account/orders/{id}', name: 'account_order_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function orderShow(Order $order): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $this->assertOrderOwner($order);

        $lastDeliveredAt   = $this->getLastDeliveredAt($order);
        $isReturnable      = $this->isReturnEligible($order);

        return $this->render('account/order/order_show.html.twig', [
            'order'             => $order,
            'lastDeliveredAt'   => $lastDeliveredAt,
            'isReturnable'      => $isReturnable,
            'returnWindowDays'  => self::RETURN_WINDOW_DAYS,
        ]);
    }

    #[Route('/account/orders/{id}/return', name: 'account_order_request_return', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function orderRequestReturn(
        Order $order,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->assertOrderOwner($order);

        if (!$this->isCsrfTokenValid('order_return_' . $order->getId(), (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('account_order_show', ['id' => $order->getId()]);
        }

        if (!$this->isReturnEligible($order)) {
            $this->addFlash('error', 'La commande n’est plus éligible au retour.');
            return $this->redirectToRoute('account_order_show', ['id' => $order->getId()]);
        }

        $rr = new ReturnRequest();
        $rr->setOrders($order);
        $rr->setStatus('requested'); // requested | approved | rejected | refunded
        $rr->setRequestedAt(new \DateTimeImmutable());

        $em->persist($rr);
        $em->flush();

        $this->addFlash('success', 'Votre demande de retour a bien été enregistrée.');
        return $this->redirectToRoute('account_order_show', ['id' => $order->getId()]);
    }

    /* ========================= ADRESSES ======================= */

    #[Route('/account/addresses', name: 'address_index', methods: ['GET'])]
    public function addressIndex(AdresseRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        /** @var User $user */
        $user = $this->getUser();

        $addresses = $repo->findBy(
            ['user' => $user],
            ['isDefault' => 'DESC', 'createdAt' => 'DESC']
        );

        // On réutilise la page "Mon compte" (ancre #addresses si besoin)
        return $this->render('account/index.html.twig', [
            'user'      => $user,
            'addresses' => $addresses,
        ]);
    }

    #[Route('/account/addresses/new', name: 'address_new', methods: ['GET', 'POST'])]
    public function addressNew(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $adresse = new Adresse();
        $adresse->setUser($user);

        $form = $this->createForm(AdresseTypeForm::class, $adresse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($adresse);
            $em->flush();
            $this->addFlash('success', 'Adresse ajoutée.');
            return $this->redirectToRoute('account_profile'); // ancre possible: #addresses
        }

        return $this->render('account/address_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Ajouter une adresse',
        ]);
    }

    #[Route('/account/addresses/{id}/edit', name: 'address_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function addressEdit(
        Adresse $adresse,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->assertAddressOwner($adresse);

        $form = $this->createForm(AdresseTypeForm::class, $adresse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adresse->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'Adresse mise à jour.');
            return $this->redirectToRoute('account_profile');
        }

        return $this->render('account/address_form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier l’adresse',
        ]);
    }

    #[Route('/account/addresses/{id}/default', name: 'address_make_default', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addressMakeDefault(
        Adresse $adresse,
        Request $request,
        AdresseRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->assertAddressOwner($adresse);

        if (!$this->isCsrfTokenValid('address_default_' . $adresse->getId(), (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('account_profile');
        }

        /** @var User $user */
        $user = $this->getUser();

        // Si tu souhaites un "par défaut" par type (livraison/facturation),
        // filtre ici par ->findBy(['user'=>$user, 'type'=>$adresse->getType()])
        $all = $repo->findBy(['user' => $user]);
        foreach ($all as $a) {
            $a->setIsDefault(false);
        }

        $adresse->setIsDefault(true);
        $em->flush();

        $this->addFlash('success', 'Adresse définie par défaut.');
        return $this->redirectToRoute('account_profile');
    }

    #[Route('/account/addresses/{id}/delete', name: 'address_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addressDelete(
        Adresse $adresse,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $this->assertAddressOwner($adresse);

        if (!$this->isCsrfTokenValid('address_delete_' . $adresse->getId(), (string)$request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('account_profile');
        }

        try {
            $em->remove($adresse);
            $em->flush();
            $this->addFlash('success', 'Adresse supprimée.');
        } catch (\Throwable $e) {
            // Possible si l’adresse est référencée par une commande (RESTRICT)
            $this->addFlash('error', 'Impossible de supprimer cette adresse (déjà utilisée dans une commande).');
        }

        return $this->redirectToRoute('account_profile');
    }

    /* ========================= HELPERS ======================== */

    private function assertOrderOwner(Order $order): void
    {
        /** @var User $user */
        $user = $this->getUser();
        $ownerId = $order->getCustomer()?->getUser()?->getId();
        if ($ownerId === null || $ownerId !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function assertAddressOwner(Adresse $adresse): void
    {
        /** @var User $user */
        $user = $this->getUser();
        $ownerId = $adresse->getUser()?->getId();
        if ($ownerId === null || $ownerId !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function getLastDeliveredAt(Order $order): ?\DateTimeImmutable
    {
        $last = null;
        foreach ($order->getShipments() as $s) {
            $d = $s->getDeliveredAt();
            if ($d && ($last === null || $d > $last)) {
                $last = $d;
            }
        }
        return $last;
    }

    private function isReturnEligible(Order $order): bool
    {
        $deliveredAt = $this->getLastDeliveredAt($order);
        if (!$deliveredAt) {
            return false;
        }
        $limit = $deliveredAt->modify('+' . self::RETURN_WINDOW_DAYS . ' days');
        return (new \DateTimeImmutable()) <= $limit;
    }
}

<?php

// src/Controller/AccountPaymentController.php
namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPaymentMethod;
use App\Form\UserPaymentFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserPaymentMethodRepository;
use App\Form\UserPaymentMethodType; // MVP (voir §4)
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/mon-compte/paiements')]
final class UserPaymentMethodController extends AbstractController
{
    #[Route('', name: 'account_payments_index', methods: ['GET'])]
    public function index(UserPaymentMethodRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        /** @var User $user */
        $user = $this->getUser();

        $wallet = $repo->findBy(['user' => $user], ['isDefault' => 'DESC', 'createdAt' => 'DESC']);

        return $this->render('account/index.html.twig', [
            'wallet' => $wallet,
        ]);
    }

    #[Route('/ajouter', name: 'payment_method_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        /** @var User $user */
        $user = $this->getUser();

        // MVP: simple form pour capturer brand/last4/exp (DEMO). En prod => Stripe SetupIntent.
        $pm = new UserPaymentMethod();
        $pm->setUser($user);

        $form = $this->createForm(UserPaymentFormType::class, $pm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // S'il n'existe aucun PM, celui-ci devient par défaut
            $hasAny = $em->getRepository(UserPaymentMethod::class)->count(['user' => $user]) > 0;
            if (!$hasAny) {
                $pm->setIsDefault(true);
            }
            $em->persist($pm);
            $em->flush();

            $this->addFlash('success', 'Moyen de paiement ajouté.');
            return $this->redirectToRoute('account_profile'); // ou 'account_payments_index'
        }

        return $this->render('account/payment/new_payment.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/defaut', name: 'payment_method_make_default', methods: ['POST'])]
    public function makeDefault(UserPaymentMethod $pm, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $this->denyIfNotOwner($pm);

        // Désactiver les autres 'default' de l'user
        $repo = $em->getRepository(UserPaymentMethod::class);
        foreach ($repo->findBy(['user' => $pm->getUser()]) as $other) {
            $other->setIsDefault($other->getId() === $pm->getId());
        }
        $em->flush();

        $this->addFlash('success', 'Défini comme moyen de paiement par défaut.');
        return $this->redirectToRoute('account_profile', [], 303);
    }

    #[Route('/{id}/supprimer', name: 'payment_method_delete', methods: ['POST'])]
    public function delete(UserPaymentMethod $pm, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $this->denyIfNotOwner($pm);

        $this->isCsrfTokenValidOrThrow($request->request->get('_token'), 'pm_delete_' . $pm->getId());

        $em->remove($pm);
        $em->flush();

        $this->addFlash('success', 'Moyen de paiement supprimé.');
        return $this->redirectToRoute('account_profile', [], 303);
    }

    private function denyIfNotOwner(UserPaymentMethod $pm): void
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($pm->getUser()?->getId() !== $user?->getId()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function isCsrfTokenValidOrThrow(?string $token, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, $token)) {
            throw $this->createAccessDeniedException('CSRF invalide.');
        }
    }
}

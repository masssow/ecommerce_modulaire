<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\ReturnItem;
use App\Entity\ReturnRequest;
use App\Entity\SupportMessage;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $email   = trim((string) $request->request->get('email'));
            $subject = trim((string) $request->request->get('subject', ''));
            $body    = trim((string) $request->request->get('message'));

            $msg = new SupportMessage();
            $msg->setType(SupportMessage::TYPE_CONTACT);
            $msg->setEmail($email);
            $msg->setSubject($subject);
            $msg->setBody($body);

            $em->persist($msg);
            $em->flush();

            // TODO optionnel : envoyer un mail au support

            $this->addFlash('success', 'Votre message a bien été envoyé.');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('support/contact.html.twig');
    }


    #[Route('/retours', name: 'app_return', methods: ['GET'])]
    public function returnsInfo(): Response
    {
        // Page texte + bouton "Faire une demande de retour"
        return $this->render('support/return.html.twig');
    }

    #[Route('/formulaire-de-retour', name: 'app_returnForm', methods: ['GET', 'POST'])]
    public function returnForm(
        Request $request,
        EntityManagerInterface $em,
        OrderRepository $orders,
        Security $security,
    ): Response {
        /** @var User|null $user */
        $user = $security->getUser();

        $userEmail = $user?->getEmail() ?? '';
        if ($request->isMethod('POST')) {
            $email       = trim((string) $request->request->get('email', '')) ?: $userEmail;
            $orderNumber = trim((string) $request->request->get('order_number', ''));
            $reason      = trim((string) $request->request->get('reason', ''));
            $requestKind = trim((string) $request->request->get('request_kind', ''));
            $messageBody = trim((string) $request->request->get('message', ''));

            if (!$email || !$orderNumber || !$requestKind || !$reason) {
                $this->addFlash('error', 'Merci de remplir tous les champs obligatoires.');
                return $this->redirectToRoute('app_returnForm');
            }

            // 1) SupportMessage (trace générique)
            $support = new SupportMessage();
            $support
                ->setType('RETURN_REQUEST')
                ->setEmail($email)
                ->setSubject('Demande de retour pour commande ' . $orderNumber)
                ->setBody($messageBody !== '' ? $messageBody : $reason)
                ->setOrderNumber($orderNumber)
                ->setRequestKind($requestKind);
            // status & createdAt sont initialisés dans le __construct()
            $em->persist($support);

            // 2) Lien éventuel vers la commande
            $order = $orders->findOneBy(['number' => $orderNumber]);
            if ($order) {
                $support->setOrderRequest($order);
            }

            // 3) ReturnRequest / ReturnItem minimal pour le MVP
            $returnRequest = new ReturnRequest();
            $returnRequest->setStatus('pending');
            $returnRequest->setRequestedAt(new \DateTimeImmutable());
            if ($order) {
                $returnRequest->setOrders($order);
            }
            $em->persist($returnRequest);

            $item = new ReturnItem();
            $item->setStatus('requested');
            $item->setRequestedAt(new \DateTimeImmutable());
            $item->setReturnRequest($returnRequest);
            // on ne renseigne pas encore OrderItem : MVP
            $em->persist($item);

            $em->flush();

            $this->addFlash('success', 'Votre demande de retour a bien été enregistrée.');
            return $this->redirectToRoute('app_home');
        }

        // GET : affichage du formulaire
        return $this->render('support/returnForm.html.twig', [
            'prefilled_email' => $userEmail,
        ]);
    }


    #[Route('/vendre-sur-lacrose', name: 'app_sell_on_lacrose', methods: ['GET', 'POST'])]
    public function sell(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $email   = trim((string) $request->request->get('email'));
            $subject = 'Candidature vendeur Lacrose';
            $body    = trim((string) $request->request->get('message'));

            $msg = new SupportMessage();
            $msg->setType(SupportMessage::TYPE_SELLER_APPLICATION);
            $msg->setEmail($email);
            $msg->setSubject($subject);
            $msg->setStatus(SupportMessage::STATUS_NEW);
            $msg->setBody($body);

            $em->persist($msg);
            $em->flush();

            // TODO optionnel : mail à l’équipe commerciale

            $this->addFlash('success', 'Votre candidature a bien été envoyée. Nous reviendrons vers vous rapidement.');

            return $this->redirectToRoute('app_sell_on_lacrose');
        }
        return $this->render('support/sell.html.twig');
    }
}

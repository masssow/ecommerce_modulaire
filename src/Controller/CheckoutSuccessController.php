<?php

namespace App\Controller;

use App\Service\Checkout\CheckoutConfirmationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/checkout', name: 'app_checkout_')]
final class CheckoutSuccessController extends AbstractController
{
    public function __construct(private readonly CheckoutConfirmationService $confirmation) {}

    #[Route('/success', name: 'success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $sessionId = (string) $request->query->get('session_id', '');
        $vm = $this->confirmation->handleSuccessRedirect($sessionId, $this->getUser());

        // On réutilise ton template existant "checkout/confirm.html.twig"
        return $this->render('checkout/success.html.twig', [
            'order'        => $vm->order,
            'paid'         => $vm->paid,
            'paymentError' => $vm->paid ? null : $vm->message,
            // optionnel si tu veux les afficher :
            'expectedTotal' => $vm->expectedTotal,
            'status'       => $vm->status,
            'message'      => $vm->message,
            'orderNumber'   => $vm->orderNumber,  
        ]);
    }

    #[Route('/cancel', name: 'cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('info', 'Paiement annulé. Vous pouvez réessayer.');
        return $this->redirectToRoute('cart_page');
    }
}

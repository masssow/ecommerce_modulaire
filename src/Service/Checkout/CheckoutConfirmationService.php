<?php

namespace App\Service\Checkout;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\StripeClient;
use Symfony\Component\Security\Core\User\UserInterface;

final class CheckoutConfirmationService
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly OrderRepository $orders,
        private readonly EntityManagerInterface $em,
        private readonly CartClearer $cartClearer,
        private readonly MoneyFormatter $money,
    ) {}

    /**
     * Vérifie la session Stripe, retrouve l'Order, compare les montants (CENTIMES)
     * et vide le panier si tout colle. Le webhook reste la source de vérité du statut payé.
     */
    public function handleSuccessRedirect(?string $sessionId, UserInterface $user): SuccessViewModel
    {
        $vm = new SuccessViewModel();

        if (empty($sessionId)) {
            $vm->message = "Identifiant de session Stripe manquant.";
            return $vm;
        }

        /** @var StripeSession $cs */
        $cs = $this->stripe->checkout->sessions->retrieve($sessionId, ['expand' => ['payment_intent']]);

        $order = $this->orders->findOneBy(['stripeSessionId' => $cs->id])
            ?? $this->orders->findOneBy(['number' => $cs->client_reference_id]);

        if (!$order instanceof Order) {
            $vm->message = "Commande introuvable pour cette session.";
            return $vm;
        }

        // Contrôle d’appartenance
        if ($order->getCustomer()?->getUser() !== $user) {
            $vm->message = "Cette commande n'appartient pas à l'utilisateur connecté.";
            return $vm;
        }

        $amountCts   = (int) ($cs->amount_total ?? 0); // Stripe = centimes
        $expectedCts = (int) $order->getGrandTotal();  // Local  = centimes

        $vm->order        = $order;
        $vm->orderNumber  = (string) $order->getNumber();
        $vm->expectedTotal = $this->money->eurCents($expectedCts);
        $vm->status       = (string) $order->getStatus();

        $paid     = ($cs->payment_status ?? null) === 'paid';
        $amountOk = ($amountCts === $expectedCts);
        $vm->paid = $paid && $amountOk;

        if ($vm->paid) {
            $this->cartClearer->clear(); // vider panier côté session
            $vm->message = "Merci ! Votre paiement a bien été pris en compte.";
        } else {
            $vm->message = $paid ? "Montant non concordant." : "Le paiement n'est pas finalisé.";
        }

        return $vm;
    }
}

final class SuccessViewModel
{
    public bool $paid = false;
    public ?Order $order = null;
    public ?string $orderNumber = null;
    public ?string $expectedTotal = null;
    public ?string $status = null;
    public ?string $message = null;
}

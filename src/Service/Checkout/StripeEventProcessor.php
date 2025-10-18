<?php

namespace App\Service\Checkout;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Traite les webhooks Stripe et met à jour la commande.
 * PSR-4 : fichier = src/Service/Checkout/StripeEventProcessor.php
 * FQCN   = App\Service\Checkout\StripeEventProcessor
 */
final class StripeEventProcessor
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ?\App\Service\OrderEmailService $emails = null, // optionnel
        private readonly bool $webhooksEnabled = true                    // optionnel (feature flag)
    ) {}

    public function handle(\Stripe\Event $event): void
    {
        if (!$this->webhooksEnabled) {
            return;
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                /** @var \Stripe\Checkout\Session $s */
                $s = $event->data->object;
                $order = $this->resolveOrderFromSession($s);
                if ($order) {
                    if (($s->payment_status ?? null) === 'paid') {
                        $this->markPaid($order, $s->payment_intent ?? null, (int)($s->amount_total ?? 0), (string)($s->currency ?? 'eur'));
                    } else {
                        $this->markPaymentFailed($order, 'unpaid on checkout.session.completed');
                    }
                }
                break;

            case 'payment_intent.succeeded':
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;
                $order = $this->resolveOrderByMetadata($pi->metadata['order_id'] ?? null, $pi->client_secret ?? null);
                if ($order) {
                    $this->markPaid($order, $pi->id, (int)($pi->amount_received ?? $pi->amount ?? 0), (string)($pi->currency ?? 'eur'));
                }
                break;

            case 'payment_intent.payment_failed':
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;
                $order = $this->resolveOrderByMetadata($pi->metadata['order_id'] ?? null, $pi->client_secret ?? null);
                if ($order) {
                    $reason = $pi->last_payment_error->message ?? 'payment_failed';
                    $this->markPaymentFailed($order, $reason);
                }
                break;

            default:
                return;
        }

        $this->em->flush();
    }

    private function resolveOrderFromSession(\Stripe\Checkout\Session $s): ?Order
    {
        $repo = $this->em->getRepository(Order::class);

        // 1) priorité : metadata.order_id
        $oid = $s->metadata['order_id'] ?? null;
        if ($oid) {
            $o = $repo->find((int) $oid);
            if ($o) return $o;
        }

        // 2) fallback : client_reference_id = number
        $ref = $s->client_reference_id ?? null;
        if ($ref && method_exists($repo, 'findOneBy')) {
            $o = $repo->findOneBy(['number' => (string) $ref]);
            if ($o) return $o;
        }

        return null;
    }

    private function resolveOrderByMetadata(?string $orderId, ?string $ref = null): ?Order
    {
        $repo = $this->em->getRepository(Order::class);
        if ($orderId) {
            $o = $repo->find((int) $orderId);
            if ($o) return $o;
        }
        if ($ref && method_exists($repo, 'findOneBy')) {
            $o = $repo->findOneBy(['number' => (string) $ref]);
            if ($o) return $o;
        }
        return null;
    }

    private function markPaid(Order $order, ?string $paymentIntentId, int $amountCts, string $currency): void
    {
        $old = method_exists($order, 'getStatus') ? (string) $order->getStatus() : null;
        if ($old === 'paid') {
            return; // idempotent
        }

        method_exists($order, 'setStatus') && $order->setStatus('paid');

        // Si tu as une entité Payment reliée
        if (class_exists(\App\Entity\Payment::class)) {
            $paymentRepo = $this->em->getRepository(\App\Entity\Payment::class);
            $payment = $paymentRepo->findOneBy(['orders' => $order]) ?? new \App\Entity\Payment();
            method_exists($payment, 'setOrders')   && $payment->setOrders($order);
            method_exists($payment, 'setStatus')   && $payment->setStatus('succeeded');
            method_exists($payment, 'setPaidAt')   && $payment->setPaidAt(new \DateTimeImmutable());
            method_exists($payment, 'setAmount')   && $payment->setAmount($amountCts);
            method_exists($payment, 'setCurrency') && $payment->setCurrency(strtoupper($currency));
            if ($paymentIntentId) {
                foreach (['setProviderRef', 'setStripePaymentIntentId'] as $setter) {
                    if (method_exists($payment, $setter)) {
                        $payment->{$setter}($paymentIntentId);
                        break;
                    }
                }
            }
            $this->em->persist($payment);
        }

        // Email auto éventuel
        $this->emails?->sendOnStatusChange($order, $old, 'paid');
    }

    private function markPaymentFailed(Order $order, string $reason): void
    {
        $old = method_exists($order, 'getStatus') ? (string) $order->getStatus() : null;
        method_exists($order, 'setStatus') && $order->setStatus('cancelled');

        if (class_exists(\App\Entity\Payment::class)) {
            $paymentRepo = $this->em->getRepository(\App\Entity\Payment::class);
            $payment = $paymentRepo->findOneBy(['orders' => $order]) ?? new \App\Entity\Payment();
            method_exists($payment, 'setOrders') && $payment->setOrders($order);
            method_exists($payment, 'setStatus') && $payment->setStatus('failed');
            method_exists($payment, 'setFailureReason') && $payment->setFailureReason($reason);
            $this->em->persist($payment);
        }

        // $this->emails?->sendOnStatusChange($order, $old, 'cancelled');
    }
}

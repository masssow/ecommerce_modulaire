<?php

namespace App\Service;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

final class StripeEventProcessor
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function handle(\Stripe\Event $event): void
    {
        switch ($event->type) {
            case 'checkout.session.completed':
                /** @var \Stripe\Checkout\Session $s */
                $s = $event->data->object;
                $order = $this->resolveOrder($s->metadata['order_id'] ?? null);
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
                $order = $this->resolveOrder($pi->metadata['order_id'] ?? null);
                if ($order) {
                    $this->markPaid($order, $pi->id, (int)($pi->amount_received ?? $pi->amount ?? 0), (string)($pi->currency ?? 'eur'));
                }
                break;

            case 'payment_intent.payment_failed':
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;
                $order = $this->resolveOrder($pi->metadata['order_id'] ?? null);
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

    private function resolveOrder(?string $orderId): ?Order
    {
        return $orderId ? $this->em->getRepository(Order::class)->find((int)$orderId) : null;
    }

    private function markPaid(Order $order, ?string $paymentIntentId, int $amountCts, string $currency): void
    {
        if (method_exists($order, 'getStatus') && $order->getStatus() === 'paid') return;

        method_exists($order, 'setStatus') && $order->setStatus('paid');

        if (class_exists(\App\Entity\Payment::class)) {
            $paymentRepo = $this->em->getRepository(\App\Entity\Payment::class);
            $payment = $paymentRepo->findOneBy(['orders' => $order]) ?? new \App\Entity\Payment();
            method_exists($payment, 'setOrders') && $payment->setOrders($order);
            method_exists($payment, 'setStatus') && $payment->setStatus('succeeded');
            method_exists($payment, 'setPaidAt') && $payment->setPaidAt(new \DateTimeImmutable());
            method_exists($payment, 'setAmount') && $payment->setAmount($amountCts);
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
    }

    private function markPaymentFailed(Order $order, string $reason): void
    {
        method_exists($order, 'setStatus') && $order->setStatus('cancelled');

        if (class_exists(\App\Entity\Payment::class)) {
            $paymentRepo = $this->em->getRepository(\App\Entity\Payment::class);
            $payment = $paymentRepo->findOneBy(['orders' => $order]) ?? new \App\Entity\Payment();
            method_exists($payment, 'setOrders') && $payment->setOrders($order);
            method_exists($payment, 'setStatus') && $payment->setStatus('failed');
            method_exists($payment, 'setFailureReason') && $payment->setFailureReason($reason);
            $this->em->persist($payment);
        }
    }
}

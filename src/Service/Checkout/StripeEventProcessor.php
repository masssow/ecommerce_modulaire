<?php

namespace App\Service\Checkout;

use App\Entity\Order;
use App\Entity\Payment;
use App\Message\Email\SendOrderEmail;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class StripeEventProcessor
{
    private bool $dispatchPaid = false;
    private bool $dispatchFailed = false;
    private ?int $dispatchOrderId = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly bool $webhooksEnabled = true,
        private readonly ?LoggerInterface $logger = null,
        private readonly MessageBusInterface $bus, // async via messenger routing
    ) {}

    public function handle(\Stripe\Event $event): void
    {
        if (!$this->webhooksEnabled) {
            $this->logger?->info('[StripeEventProcessor] Webhooks disabled, skipping.', [
                'type' => $event->type ?? null,
            ]);
            return;
        }

        // reset flags per event
        $this->dispatchPaid = false;
        $this->dispatchFailed = false;
        $this->dispatchOrderId = null;

        $this->logger?->info('[StripeEventProcessor] Handling event', [
            'type'     => $event->type ?? null,
            'event_id' => $event->id ?? null,
        ]);

        switch ($event->type) {

            // ✅ IMPORTANT: on ignore totalement ce event pour l’email/statut
            // Source of truth = payment_intent.succeeded / payment_intent.payment_failed
            case 'checkout.session.completed':
                /** @var \Stripe\Checkout\Session $s */
                $s = $event->data->object;

                $this->logger?->info('[StripeEventProcessor] checkout.session.completed received (ignored for status/email)', [
                    'session_id'     => $s->id ?? null,
                    'payment_status' => $s->payment_status ?? null,
                ]);
                break;

            case 'payment_intent.succeeded':
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;

                $this->logger?->info('[StripeEventProcessor] payment_intent.succeeded received', [
                    'pi_id'     => $pi->id ?? null,
                    'order_id'  => $pi->metadata['order_id'] ?? null,
                    'amount'    => $pi->amount_received ?? $pi->amount ?? null,
                    'currency'  => $pi->currency ?? null,
                ]);

                $order = $this->resolveOrderByMetadata(
                    $pi->metadata['order_id'] ?? null,
                    $pi->client_secret ?? null
                );

                if (!$order) {
                    $this->logger?->error('[StripeEventProcessor] Order not found for payment_intent.succeeded', [
                        'pi_id'    => $pi->id ?? null,
                        'order_id' => $pi->metadata['order_id'] ?? null,
                    ]);
                    break;
                }

                $this->markPaid(
                    $order,
                    $pi->id ?? null,
                    (int) ($pi->amount_received ?? $pi->amount ?? 0),
                    (string) ($pi->currency ?? 'eur')
                );
                break;

            case 'payment_intent.payment_failed':
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;

                $this->logger?->info('[StripeEventProcessor] payment_intent.payment_failed received', [
                    'pi_id'    => $pi->id ?? null,
                    'order_id' => $pi->metadata['order_id'] ?? null,
                ]);

                $order = $this->resolveOrderByMetadata(
                    $pi->metadata['order_id'] ?? null,
                    $pi->client_secret ?? null
                );

                if (!$order) {
                    $this->logger?->error('[StripeEventProcessor] Order not found for payment_intent.payment_failed', [
                        'pi_id'    => $pi->id ?? null,
                        'order_id' => $pi->metadata['order_id'] ?? null,
                    ]);
                    break;
                }

                $reason = $pi->last_payment_error->message ?? 'payment_failed';
                $this->markPaymentFailed($order, (string) $reason);
                break;

            default:
                $this->logger?->info('[StripeEventProcessor] Event ignored', [
                    'type'     => $event->type ?? null,
                    'event_id' => $event->id ?? null,
                ]);
                return;
        }

        // ✅ DB first
        $this->em->flush();

        // ✅ dispatch after flush (async routing -> safe for webhook)
        try {
            if ($this->dispatchOrderId) {
                if ($this->dispatchPaid) {
                    $this->logger?->info('[StripeEventProcessor] Dispatch SendOrderEmail(paid)', [
                        'orderId' => $this->dispatchOrderId,
                    ]);
                    $this->bus->dispatch(new SendOrderEmail($this->dispatchOrderId, 'paid'));
                } elseif ($this->dispatchFailed) {
                    $this->logger?->info('[StripeEventProcessor] Dispatch SendOrderEmail(failed)', [
                        'orderId' => $this->dispatchOrderId,
                    ]);
                    $this->bus->dispatch(new SendOrderEmail($this->dispatchOrderId, 'failed'));
                }
            }
        } catch (\Throwable $e) {
            // ne jamais casser le webhook pour un problème de dispatch
            $this->logger?->error('[StripeEventProcessor] Dispatch failed (ignored for webhook)', [
                'orderId' => $this->dispatchOrderId,
                'error'   => $e->getMessage(),
            ]);
        }

        $this->logger?->info('[StripeEventProcessor] Flush done for event', [
            'type'     => $event->type ?? null,
            'event_id' => $event->id ?? null,
        ]);
    }

    private function resolveOrderFromSession(\Stripe\Checkout\Session $s): ?Order
    {
        $repo = $this->em->getRepository(Order::class);

        $oid = $s->metadata['order_id'] ?? null;
        if ($oid && ($o = $repo->find((int) $oid))) {
            return $o;
        }

        $ref = $s->client_reference_id ?? null;
        if ($ref && ($o = $repo->findOneBy(['number' => (string) $ref]))) {
            return $o;
        }

        return null;
    }

    private function resolveOrderByMetadata(?string $orderId, ?string $ref = null): ?Order
    {
        $repo = $this->em->getRepository(Order::class);

        if ($orderId && ($o = $repo->find((int) $orderId))) {
            return $o;
        }

        // fallback (peu fiable) : numéro de commande si tu le mets dans ref (à éviter)
        if ($ref && ($o = $repo->findOneBy(['number' => (string) $ref]))) {
            return $o;
        }

        return null;
    }

    private function markPaid(Order $order, ?string $paymentIntentId, int $amountCts, string $currency): void
    {
        $old = (string) $order->getStatus();

        // ✅ idempotence: pas de double update / double dispatch
        if ($old === 'paid') {
            $this->logger?->info('[StripeEventProcessor] Order already paid, skip', [
                'orderId' => $order->getId(),
                'number'  => $order->getNumber(),
            ]);
            return;
        }

        $order->setStatus('paid');

        $paymentRepo = $this->em->getRepository(Payment::class);
        $payment = $paymentRepo->findOneBy(['orders' => $order]) ?? new Payment();

        $payment
            ->setOrders($order)
            ->setStatus('succeeded')
            ->setPaidAt(new \DateTimeImmutable())
            ->setAmount($amountCts)                 // cents ✅
            ->setCurrency(strtoupper($currency))    // EUR
            ->setTransactionId($paymentIntentId);

        $this->em->persist($payment);

        $this->dispatchPaid = true;
        $this->dispatchOrderId = (int) $order->getId();
    }

    private function markPaymentFailed(Order $order, string $reason): void
    {
        $old = (string) $order->getStatus();

        // ✅ évite spam si multiples tentatives échouées
        if (in_array($old, ['cancelled', 'failed'], true)) {
            $this->logger?->info('[StripeEventProcessor] Order already cancelled/failed, skip', [
                'orderId' => $order->getId(),
                'number'  => $order->getNumber(),
                'old'     => $old,
            ]);
            return;
        }

        $order->setStatus('cancelled');

        $paymentRepo = $this->em->getRepository(Payment::class);
        $payment = $paymentRepo->findOneBy(['orders' => $order]) ?? new Payment();

        $payment
            ->setOrders($order)
            ->setStatus('failed')
            ->setPaidAt(null);

        $this->em->persist($payment);

        $this->dispatchFailed = true;
        $this->dispatchOrderId = (int) $order->getId();

        $this->logger?->info('[StripeEventProcessor] Payment failed', [
            'orderId' => $order->getId(),
            'number'  => $order->getNumber(),
            'reason'  => $reason,
        ]);
    }
}

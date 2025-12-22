<?php

namespace App\Service\Checkout;

use App\Entity\Order;
use App\Entity\Payment;
use App\Message\Email\SendOrderEmail;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Traite les webhooks Stripe et met à jour la commande.
 * Envoi email via Messenger (SendOrderEmail) uniquement à la 1ère transition vers "paid".
 */
final class StripeEventProcessor
{
    private bool $shouldDispatchPaidEmail = false;
    private ?int $paidEmailOrderId = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ?\App\Service\OrderEmailService $emails = null, // legacy (optionnel)
        private readonly bool $webhooksEnabled = true,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?MessageBusInterface $bus = null, // ✅ interface + nullable safe
    ) {}

    public function handle(\Stripe\Event $event): void
    {
        if (!$this->webhooksEnabled) {
            $this->logger?->info('[StripeEventProcessor] Webhooks disabled, skipping.', [
                'type' => $event->type ?? null,
            ]);
            return;
        }

        // reset flags par event
        $this->shouldDispatchPaidEmail = false;
        $this->paidEmailOrderId = null;

        $this->logger?->info('[StripeEventProcessor] Handling event', [
            'type' => $event->type ?? null,
        ]);

        switch ($event->type) {
            case 'checkout.session.completed':
                /** @var \Stripe\Checkout\Session $s */
                $s = $event->data->object;

                $this->logger?->info('[StripeEventProcessor] checkout.session.completed received', [
                    'session_id'        => $s->id ?? null,
                    'payment_status'    => $s->payment_status ?? null,
                    'client_reference'  => $s->client_reference_id ?? null,
                    'metadata_order_id' => $s->metadata['order_id'] ?? null,
                ]);

                $order = $this->resolveOrderFromSession($s);

                if (!$order) {
                    $this->logger?->error('[StripeEventProcessor] Order not found for session', [
                        'session_id'       => $s->id ?? null,
                        'client_reference' => $s->client_reference_id ?? null,
                        'metadata'         => $s->metadata ?? [],
                    ]);
                    break;
                }

                if (($s->payment_status ?? null) === 'paid') {
                    $this->markPaid(
                        $order,
                        $s->payment_intent ?? null,
                        (int) ($s->amount_total ?? 0),
                        (string) ($s->currency ?? 'eur')
                    );
                } else {
                    $this->markPaymentFailed($order, 'unpaid on checkout.session.completed');
                }
                break;

            case 'payment_intent.succeeded':
                /** @var \Stripe\PaymentIntent $pi */
                $pi = $event->data->object;

                $this->logger?->info('[StripeEventProcessor] payment_intent.succeeded received', [
                    'pi_id'    => $pi->id ?? null,
                    'order_id' => $pi->metadata['order_id'] ?? null,
                    'amount'   => $pi->amount_received ?? $pi->amount ?? null,
                    'currency' => $pi->currency ?? null,
                ]);

                $order = $this->resolveOrderByMetadata(
                    $pi->metadata['order_id'] ?? null,
                    $pi->client_secret ?? null // ⚠️ on gardera ça pour l’instant, à supprimer plus tard
                );

                if (!$order) {
                    $this->logger?->error('[StripeEventProcessor] Order not found for payment_intent', [
                        'pi_id'    => $pi->id ?? null,
                        'order_id' => $pi->metadata['order_id'] ?? null,
                    ]);
                    break;
                }

                $this->markPaid(
                    $order,
                    $pi->id,
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
                    $this->logger?->error('[StripeEventProcessor] Order not found for failed payment_intent', [
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
                    'type' => $event->type ?? null,
                ]);
                return;
        }

        // ✅ flush d’abord (DB cohérente)…
        $this->em->flush();

        // …puis dispatch le mail (1 fois)
        if ($this->shouldDispatchPaidEmail && $this->paidEmailOrderId) {
            if ($this->bus) {
                $this->logger?->info('[StripeEventProcessor] Dispatching SendOrderEmail(paid) via Messenger', [
                    'orderId' => $this->paidEmailOrderId,
                ]);
                $this->bus->dispatch(new SendOrderEmail($this->paidEmailOrderId, 'paid'));
            } else {
                $this->logger?->warning('[StripeEventProcessor] MessageBus is NULL, cannot dispatch SendOrderEmail', [
                    'orderId' => $this->paidEmailOrderId,
                ]);
            }
        }

        $this->logger?->info('[StripeEventProcessor] Flush done for event', [
            'type' => $event->type ?? null,
        ]);
    }

    private function resolveOrderFromSession(\Stripe\Checkout\Session $s): ?Order
    {
        $repo = $this->em->getRepository(Order::class);

        $oid = $s->metadata['order_id'] ?? null;
        if ($oid) {
            $o = $repo->find((int) $oid);
            if ($o) {
                return $o;
            }
        }

        $ref = $s->client_reference_id ?? null;
        if ($ref) {
            $o = $repo->findOneBy(['number' => (string) $ref]);
            if ($o) {
                return $o;
            }
        }

        return null;
    }

    private function resolveOrderByMetadata(?string $orderId, ?string $ref = null): ?Order
    {
        $repo = $this->em->getRepository(Order::class);

        if ($orderId) {
            $o = $repo->find((int) $orderId);
            if ($o) {
                return $o;
            }
        }

        if ($ref) {
            $o = $repo->findOneBy(['number' => (string) $ref]);
            if ($o) {
                return $o;
            }
        }

        return null;
    }

    private function markPaid(Order $order, ?string $paymentIntentId, int $amountCts, string $currency): void
    {
        $old         = (string) $order->getStatus();
        $alreadyPaid = ($old === 'paid');

        if ($alreadyPaid) {
            $this->logger?->info('[StripeEventProcessor] Order already paid: skipping status/payment update + email ✅', [
                'orderId'   => $order->getId(),
                'number'    => $order->getNumber(),
                'oldStatus' => $old,
                'amountCts' => $amountCts,
                'currency'  => $currency,
            ]);
            return;
        }

        $this->logger?->info('[StripeEventProcessor] Marking order as paid', [
            'orderId'   => $order->getId(),
            'number'    => $order->getNumber(),
            'oldStatus' => $old,
            'amountCts' => $amountCts,
            'currency'  => $currency,
        ]);

        $order->setStatus('paid');

        $paymentRepo = $this->em->getRepository(Payment::class);
        $payment     = $paymentRepo->findOneBy(['orders' => $order]) ?? new Payment();

        $payment
            ->setOrders($order)
            ->setStatus('succeeded')
            ->setPaidAt(new \DateTimeImmutable())
            ->setAmount($amountCts)              // centimes ✅
            ->setCurrency(strtoupper($currency)) // "EUR"
            ->setTransactionId($paymentIntentId);

        $this->em->persist($payment);

        // ✅ on marque l’intention d’envoyer l’email après flush
        $this->shouldDispatchPaidEmail = true;
        $this->paidEmailOrderId = (int) $order->getId();
    }

    private function markPaymentFailed(Order $order, string $reason): void
    {
        $old = (string) $order->getStatus();

        $this->logger?->info('[StripeEventProcessor] Marking order as cancelled (payment failed)', [
            'orderId'   => $order->getId(),
            'number'    => $order->getNumber(),
            'oldStatus' => $old,
            'reason'    => $reason,
        ]);

        $order->setStatus('cancelled');

        $paymentRepo = $this->em->getRepository(Payment::class);
        $payment     = $paymentRepo->findOneBy(['orders' => $order]) ?? new Payment();

        $payment
            ->setOrders($order)
            ->setStatus('failed')
            ->setPaidAt(null);

        $this->em->persist($payment);

        // legacy pipeline (à remplacer plus tard par message "failed")
        $this->emails?->sendOnStatusChange($order, $old, 'cancelled');
    }
}

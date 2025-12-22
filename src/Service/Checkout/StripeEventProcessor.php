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
        private readonly MessageBusInterface $bus, // ✅ non-nullable
    ) {}

    public function handle(\Stripe\Event $event): void
    {
        if (!$this->webhooksEnabled) {
            $this->logger?->info('[StripeEventProcessor] Webhooks disabled, skipping.', ['type' => $event->type ?? null]);
            return;
        }

        // reset flags par event
        $this->dispatchPaid = false;
        $this->dispatchFailed = false;
        $this->dispatchOrderId = null;

        $this->logger?->info('[StripeEventProcessor] Handling event', ['type' => $event->type ?? null]);

        switch ($event->type) {
            case 'checkout.session.completed':
                $s = $event->data->object;
                $order = $this->resolveOrderFromSession($s);
                if (!$order) {
                    $this->logger?->error('[StripeEventProcessor] Order not found for session');
                    break;
                }

                if (($s->payment_status ?? null) === 'paid') {
                    $this->markPaid($order, $s->payment_intent ?? null, (int)($s->amount_total ?? 0), (string)($s->currency ?? 'eur'));
                } else {
                    $this->markPaymentFailed($order, 'unpaid on checkout.session.completed');
                }
                break;

            case 'payment_intent.succeeded':
                $pi = $event->data->object;
                $order = $this->resolveOrderByMetadata($pi->metadata['order_id'] ?? null, $pi->client_secret ?? null);
                if (!$order) {
                    $this->logger?->error('[StripeEventProcessor] Order not found for payment_intent');
                    break;
                }

                $this->markPaid($order, $pi->id ?? null, (int)($pi->amount_received ?? $pi->amount ?? 0), (string)($pi->currency ?? 'eur'));
                break;

            case 'payment_intent.payment_failed':
                $pi = $event->data->object;
                $order = $this->resolveOrderByMetadata($pi->metadata['order_id'] ?? null, $pi->client_secret ?? null);
                if (!$order) {
                    $this->logger?->error('[StripeEventProcessor] Order not found for failed payment_intent');
                    break;
                }

                $reason = $pi->last_payment_error->message ?? 'payment_failed';
                $this->markPaymentFailed($order, (string)$reason);
                break;

            default:
                $this->logger?->info('[StripeEventProcessor] Event ignored', ['type' => $event->type ?? null]);
                return;
        }

        // ✅ DB d’abord
        $this->em->flush();

        // ✅ puis dispatch 1 fois
        if ($this->dispatchOrderId) {
            if ($this->dispatchPaid) {
                $this->logger?->info('[StripeEventProcessor] Dispatch SendOrderEmail(paid)', ['orderId' => $this->dispatchOrderId]);
                $this->bus->dispatch(new SendOrderEmail($this->dispatchOrderId, 'paid'));
            } elseif ($this->dispatchFailed) {
                $this->logger?->info('[StripeEventProcessor] Dispatch SendOrderEmail(failed)', ['orderId' => $this->dispatchOrderId]);
                $this->bus->dispatch(new SendOrderEmail($this->dispatchOrderId, 'failed'));
            }
        }

        $this->logger?->info('[StripeEventProcessor] Flush done for event', ['type' => $event->type ?? null]);
    }

    private function resolveOrderFromSession(\Stripe\Checkout\Session $s): ?Order
    {
        $repo = $this->em->getRepository(Order::class);

        $oid = $s->metadata['order_id'] ?? null;
        if ($oid && ($o = $repo->find((int)$oid))) return $o;

        $ref = $s->client_reference_id ?? null;
        if ($ref && ($o = $repo->findOneBy(['number' => (string)$ref]))) return $o;

        return null;
    }

    private function resolveOrderByMetadata(?string $orderId, ?string $ref = null): ?Order
    {
        $repo = $this->em->getRepository(Order::class);

        if ($orderId && ($o = $repo->find((int)$orderId))) return $o;
        if ($ref && ($o = $repo->findOneBy(['number' => (string)$ref]))) return $o;

        return null;
    }

    private function markPaid(Order $order, ?string $paymentIntentId, int $amountCts, string $currency): void
    {
        $old = (string)$order->getStatus();
        if ($old === 'paid') {
            $this->logger?->info('[StripeEventProcessor] Order already paid, skip', ['orderId' => $order->getId()]);
            return;
        }

        $order->setStatus('paid');

        $paymentRepo = $this->em->getRepository(Payment::class);
        $payment = $paymentRepo->findOneBy(['orders' => $order]) ?? new Payment();

        $payment
            ->setOrders($order)
            ->setStatus('succeeded')
            ->setPaidAt(new \DateTimeImmutable())
            ->setAmount($amountCts)              // centimes ✅
            ->setCurrency(strtoupper($currency)) // EUR
            ->setTransactionId($paymentIntentId);

        $this->em->persist($payment);

        $this->dispatchPaid = true;
        $this->dispatchOrderId = (int)$order->getId();
    }

    private function markPaymentFailed(Order $order, string $reason): void
    {
        $order->setStatus('cancelled');

        $paymentRepo = $this->em->getRepository(Payment::class);
        $payment = $paymentRepo->findOneBy(['orders' => $order]) ?? new Payment();

        $payment->setOrders($order)->setStatus('failed')->setPaidAt(null);
        $this->em->persist($payment);

        $this->dispatchFailed = true;
        $this->dispatchOrderId = (int)$order->getId();

        $this->logger?->info('[StripeEventProcessor] Payment failed', ['orderId' => $order->getId(), 'reason' => $reason]);
    }
}

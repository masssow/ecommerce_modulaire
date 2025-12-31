<?php

namespace App\MessageHandler\Email;

use App\Message\Email\SendOrderEmail;
use App\Repository\OrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;
use Throwable;

#[AsMessageHandler]
final class SendOrderEmailHandler
{
    public function __construct(
        private OrderRepository $orders,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    public function __invoke(SendOrderEmail $msg): void
    {
        $order = $this->orders->find($msg->orderId);

        // Destinataire de test prioritaire (ENV inline ou .env.prod.local)
        $envTestTo = $_ENV['APP_EMAIL_TEST_TO'] ?? getenv('APP_EMAIL_TEST_TO') ?: null;

        // Résoudre le destinataire client de façon robuste
        $customerTo = $envTestTo ?: ($msg->overrideTo ?: $this->resolveOrderEmail($order));

        if (!$order && !$customerTo) {
            $this->logger->warning('[SendOrderEmailHandler] Order not found AND no customer recipient', [
                'orderId' => $msg->orderId,
                'type' => $msg->type
            ]);
            return;
        }

        $fromEmail = $_ENV['APP_EMAIL_FROM']      ?? 'noreply@sandbox.massgrafik.com';
        $fromName  = $_ENV['APP_EMAIL_FROM_NAME'] ?? 'Keur Paris';

        // -------------------------
        // 1) EMAIL CLIENT
        // -------------------------
        $customerTemplate = match ($msg->type) {
            'paid'   => 'emails/order_paid.html.twig',
            'failed' => 'emails/order_failed.html.twig',
            default  => 'emails/order_paid.html.twig',
        };

        $displayOrderId = $order?->getId() ?? $msg->orderId;

        $customerSubject = match ($msg->type) {
            'paid'   => sprintf('Confirmation commande #%d — Paiement validé', (int) $displayOrderId),
            'failed' => sprintf('Commande #%d — Paiement refusé', (int) $displayOrderId),
            default  => 'Notification commande',
        };

        $total = 0;
        if ($order) {
            try {
                if (method_exists($order, 'getGrandTotal') && is_numeric($order->getGrandTotal())) {
                    $total = (int) $order->getGrandTotal();
                } elseif (method_exists($order, 'getTotalCents') && is_numeric($order->getTotalCents())) {
                    $total = (int) $order->getTotalCents();
                } elseif (method_exists($order, 'getTotal') && is_numeric($order->getTotal())) {
                    $total = (int) $order->getTotal(); // suppose déjà en cents
                }
            } catch (Throwable) {
                // ignore
            }
        }

        $this->logger->info('[SendOrderEmailHandler] Sending CUSTOMER mail', [
            'orderId'  => $msg->orderId,
            'type'     => $msg->type,
            'to'       => $customerTo,
            'template' => $customerTemplate
        ]);

        $customerEmail = (new TemplatedEmail())
            ->from(new Address($fromEmail, $fromName))
            ->to($customerTo)
            ->subject($customerSubject)
            ->htmlTemplate($customerTemplate)
            ->context([
                'order'   => $order,
                'user'    => $this->extractUserIfAny($order),
                'orderId' => $msg->orderId,
                'total'   => $total, // en cents
                'reason'  => $msg->type === 'failed' ? 'Paiement refusé' : null,
            ]);

        $this->mailer->send($customerEmail);

        $this->logger->info('[SendOrderEmailHandler] CUSTOMER sent ✅', [
            'orderId' => $msg->orderId,
            'to' => $customerTo
        ]);

        // -------------------------
        // 2) EMAIL ADMIN DÉDIÉ (MVP)
        // -> seulement sur paiement validé
        // -------------------------
        if ($msg->type !== 'paid') {
            return;
        }

        // Liste admins : "a@x.com,b@y.com"
        $adminList = $_ENV['APP_ORDER_ADMIN_EMAILS'] ?? getenv('APP_ORDER_ADMIN_EMAILS') ?: '';
        $adminEmails = $this->parseEmailList($adminList);

        // En environnement de test mail, on n’envoie pas aux admins réels (optionnel mais conseillé)
        if ($envTestTo) {
            $adminEmails = [$envTestTo];
        }

        if (!$adminEmails) {
            $this->logger->info('[SendOrderEmailHandler] No admin recipients configured, skip ADMIN mail.', [
                'orderId' => $msg->orderId
            ]);
            return;
        }

        $adminTemplate = 'emails/admin_order_paid.html.twig';
        $adminSubject  = sprintf('📦 [ADMIN] Nouvelle commande #%d — Préparation', (int) $displayOrderId);

        $this->logger->info('[SendOrderEmailHandler] Sending ADMIN mail', [
            'orderId' => $msg->orderId,
            'to'      => $adminEmails,
            'template' => $adminTemplate
        ]);

        $adminEmail = (new TemplatedEmail())
            ->from(new Address($fromEmail, $fromName))
            ->to(...array_map(fn(string $e) => new Address($e), $adminEmails))
            ->subject($adminSubject)
            ->htmlTemplate($adminTemplate)
            ->context([
                'order'   => $order,
                'orderId' => $msg->orderId,
                'total'   => $total, // cents
                'customerEmail' => $customerTo,
            ]);

        $this->mailer->send($adminEmail);

        $this->logger->info('[SendOrderEmailHandler] ADMIN sent ✅', [
            'orderId' => $msg->orderId,
            'to' => $adminEmails
        ]);
    }

    private function parseEmailList(string $raw): array
    {
        $parts = array_map('trim', preg_split('/[;,]+/', $raw) ?: []);
        $parts = array_filter($parts, fn($v) => is_string($v) && $v !== '');
        // dédoublonnage + ordre conservé
        $unique = [];
        foreach ($parts as $p) {
            $k = strtolower($p);
            if (!isset($unique[$k])) $unique[$k] = $p;
        }
        return array_values($unique);
    }

    private function resolveOrderEmail(object|null $order): ?string
    {
        if (!$order) return null;

        if (method_exists($order, 'getEmail')) {
            $email = $order->getEmail();
            if (is_string($email) && $email !== '') return $email;
        }

        if (method_exists($order, 'getCustomer')) {
            $customer = $order->getCustomer();
            if ($customer && method_exists($customer, 'getEmail')) {
                $email = $customer->getEmail();
                if (is_string($email) && $email !== '') return $email;
            }
            if ($customer && method_exists($customer, 'getUser')) {
                $user = $customer->getUser();
                if ($user && method_exists($user, 'getEmail')) {
                    $email = $user->getEmail();
                    if (is_string($email) && $email !== '') return $email;
                }
            }
        }

        if (method_exists($order, 'getUser')) {
            $user = $order->getUser();
            if ($user && method_exists($user, 'getEmail')) {
                $email = $user->getEmail();
                if (is_string($email) && $email !== '') return $email;
            }
        }

        foreach (['getBillingAddress', 'getShippingAddress'] as $addrGetter) {
            if (method_exists($order, $addrGetter)) {
                $addr = $order->{$addrGetter}();
                if ($addr) {
                    foreach (['getEmail', 'getContactEmail'] as $getter) {
                        if (method_exists($addr, $getter)) {
                            $email = $addr->{$getter}();
                            if (is_string($email) && $email !== '') return $email;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function extractUserIfAny(object|null $order): ?object
    {
        if (!$order) return null;

        foreach (['getUser', 'getCustomer'] as $getter) {
            if (method_exists($order, $getter)) {
                $obj = $order->{$getter}();
                if ($obj) {
                    if (method_exists($obj, 'getEmail') && !method_exists($obj, 'getUser')) {
                        return $obj;
                    }
                    if (method_exists($obj, 'getUser')) {
                        $user = $obj->getUser();
                        if ($user) return $user;
                    }
                }
            }
        }

        return null;
    }
}

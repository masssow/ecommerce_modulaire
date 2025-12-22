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

        // Résoudre le destinataire de façon robuste, sans appel à une méthode inexistante
        $to = $envTestTo ?: ($msg->overrideTo ?: $this->resolveOrderEmail($order));

        if (!$order && !$to) {
            $this->logger->warning('[SendOrderEmailHandler] Order not found AND no recipient', [
                'orderId' => $msg->orderId, 'type' => $msg->type
            ]);
            return;
        }

        $fromEmail = $_ENV['APP_EMAIL_FROM']      ?? 'noreply@sandbox.massgrafik.com';
        $fromName  = $_ENV['APP_EMAIL_FROM_NAME'] ?? 'Lacrose Shop';

        $template = match ($msg->type) {
            'paid'   => 'emails/order_paid.html.twig',
            'failed' => 'emails/order_failed.html.twig',
            default  => 'emails/order_paid.html.twig',
        };

        $displayOrderId = $order?->getId() ?? $msg->orderId;
        $subject = match ($msg->type) {
            'paid'   => sprintf('Confirmation commande #%d — Paiement validé', (int) $displayOrderId),
            'failed' => sprintf('Commande #%d — Paiement refusé', (int) $displayOrderId),
            default  => 'Notification commande',
        };

        // Total (tolérant : totalCents | total)
        $total = 0;
        if ($order) {
            try {
                if (method_exists($order, 'getTotalCents') && is_numeric($order->getTotalCents())) {
                    $total = (int) $order->getTotalCents();
                } elseif (method_exists($order, 'getTotal') && is_numeric($order->getTotal())) {
                    $total = (int) $order->getTotal(); // suppose déjà en cents dans ton modèle
                }
            } catch (Throwable $e) {
                // ignore, total restera 0
            }
        }

        $this->logger->info('[SendOrderEmailHandler] Sending', [
            'orderId'  => $msg->orderId,
            'type'     => $msg->type,
            'to'       => $to,
            'template' => $template
        ]);

        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, $fromName))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'order'   => $order,
                // essaie de passer un user si disponible, sinon null
                'user'    => $this->extractUserIfAny($order),
                'orderId' => $msg->orderId,
                'total'   => $total,                 // en cents si possible
                'reason'  => $msg->type === 'failed' ? 'Paiement refusé' : null,
            ]);

        $this->mailer->send($email);

        $this->logger->info('[SendOrderEmailHandler] Sent ✅', [
            'orderId' => $msg->orderId, 'to' => $to
        ]);
    }

    /**
     * Retourne l'email du client pour la commande, en testant plusieurs chemins possibles
     * sans provoquer d'erreur si les méthodes n'existent pas.
     */
    private function resolveOrderEmail(object|null $order): ?string
    {
        if (!$order) {
            return null;
        }

        // 1) Order->getEmail()
        if (method_exists($order, 'getEmail')) {
            $email = $order->getEmail();
            if (is_string($email) && $email !== '') {
                return $email;
            }
        }

        // 2) Order->getCustomer()?->getEmail()
        if (method_exists($order, 'getCustomer')) {
            $customer = $order->getCustomer();
            if ($customer && method_exists($customer, 'getEmail')) {
                $email = $customer->getEmail();
                if (is_string($email) && $email !== '') {
                    return $email;
                }
            }
            // 2b) Order->getCustomer()?->getUser()?->getEmail()
            if ($customer && method_exists($customer, 'getUser')) {
                $user = $customer->getUser();
                if ($user && method_exists($user, 'getEmail')) {
                    $email = $user->getEmail();
                    if (is_string($email) && $email !== '') {
                        return $email;
                    }
                }
            }
        }

        // 3) Order->getUser()?->getEmail() (si jamais ça existe dans ton modèle)
        if (method_exists($order, 'getUser')) {
            $user = $order->getUser();
            if ($user && method_exists($user, 'getEmail')) {
                $email = $user->getEmail();
                if (is_string($email) && $email !== '') {
                    return $email;
                }
            }
        }

        // 4) Billing/Shipping Address email si présent
        foreach (['getBillingAddress', 'getShippingAddress'] as $addrGetter) {
            if (method_exists($order, $addrGetter)) {
                $addr = $order->{$addrGetter}();
                if ($addr) {
                    foreach (['getEmail', 'getContactEmail'] as $getter) {
                        if (method_exists($addr, $getter)) {
                            $email = $addr->{$getter}();
                            if (is_string($email) && $email !== '') {
                                return $email;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Essaie de renvoyer un objet User lié à la commande si disponible,
     * sinon null (les templates tolèrent ce cas).
     */
    private function extractUserIfAny(object|null $order): ?object
    {
        if (!$order) return null;

        foreach (['getUser', 'getCustomer'] as $getter) {
            if (method_exists($order, $getter)) {
                $obj = $order->{$getter}();
                if ($obj) {
                    if (method_exists($obj, 'getEmail') && !method_exists($obj, 'getUser')) {
                        // c'est probablement déjà un User (Customer sans getUser)
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

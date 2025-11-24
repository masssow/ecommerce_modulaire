<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\DependencyInjection\Attribute\Autowire;


final class SimpleOrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(APP_EMAIL_FROM)%')]
        private readonly string $fromEmail,
        #[Autowire('%env(APP_EMAIL_FROM_NAME)%')]
        private readonly string $fromName,
        private readonly ?string $replyTo  = null,
    ) {}

    /** Envoie un e-mail “statut de commande” simple */
    public function sendStatusEmail(Order $order, string $status, ?string $to = null): void
    {
        $to = $to ?? $this->guessEmail($order);
        if (!$to) {
            throw new \RuntimeException("Destinataire introuvable pour la commande #" . $order->getId());
        }

        $subject = $this->subjectFor($status, $order);

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->htmlTemplate('emails/order_status_update.html.twig')
            ->context([
                'order'   => $order,
                'status'  => $status,
                'subject' => $subject,
            ]);

        if ($this->replyTo) {
            $email->replyTo($this->replyTo);
        }

        $this->mailer->send($email);
    }

    private function subjectFor(string $status, Order $order): string
    {
        return match ($status) {
            'paid'      => 'Paiement confirmé — Commande ' . $this->displayNumber($order),
            'preparing' => 'Votre commande est en préparation — ' . $this->displayNumber($order),
            'shipped'   => 'Votre commande a été expédiée — ' . $this->displayNumber($order),
            default     => 'Mise à jour de commande — ' . $this->displayNumber($order),
        };
    }

    private function displayNumber(Order $order): string
    {
        return (string)($order->getNumber() ?? $order->getId());
    }

    private function guessEmail(Order $order): ?string
    {
        if (method_exists($order, 'getCustomer') && $order->getCustomer()?->getUser()?->getEmail()) {
            return $order->getCustomer()->getUser()->getEmail();
        }
        return null;
    }
}

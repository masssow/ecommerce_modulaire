<?php

namespace App\Service;

use App\Entity\EmailLog;
use App\Entity\EmailTemplate;
use App\Entity\Order;
use App\Repository\EmailTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class OrderEmailService
{
    // {# ==================================  hors scope - fichier laissé pour référence ==================================
    #}
    public function __construct(
        private readonly Environment $twig,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $em,
        private readonly EmailTemplateRepository $templateRepo,
        private readonly string $projectDir,
        private readonly string $fromEmail = 'noreply@sandbox.massgrafik.com',
        private readonly string $fromName  = 'Lacrose Shop',
        private readonly ?string $replyTo = null
    ) {}

    /**
     * Construit le contexte Twig disponible dans les modèles.
     * Ajoute des fallbacks généreux pour récupérer l'email client selon ta structure Order.
     */
    public function buildContext(Order $order): array
    {
        $customerName = null;
        $customerEmail = null;

        // 1) Order->getUser()
        if (method_exists($order, 'getUser') && $order->getCustomer()) {
            $u = $order->getCustomer();
            $customerName  = trim(($u->getUser() ?? '').' '.($u->getUser() ?? '')) ?: null;
            $customerEmail = method_exists($u, 'getEmail') ? $u->getUser() : null;
        }

        // 2) sinon Order->getCustomer() (et peut-être Customer->getUser())
       
            $c = $order->getCustomer();

            // Customer porte directement l'email
                $cu = $c->getUser();
                $customerEmail = $cu->getEmail();
                $fname = method_exists($cu, 'getFirstname') ? ($cu->getFirstname() ?? '') : '';
                $lname = method_exists($cu, 'getLastname') ? ($cu->getLastname() ?? '') : '';
                $customerName = trim($fname.' '.$lname) ?: $customerName;
            
    

        // Total formaté (essaye plusieurs conventions)
        $totalFormatted = $this->formatOrderTotal($order);

        return [
            'order'           => $order,
            'order_number'    => method_exists($order, 'getId') ? $order->getId() : null,
            'order_total'     => $totalFormatted,
            'order_status'    => method_exists($order, 'getStatus') ? $order->getStatus() : null,
            'customer_name'   => $customerName,
            'customer_email'  => $customerEmail,
            'created_at'      => method_exists($order, 'getCreatedAt') ? $order->getCreatedAt() : null,
            'shipping_method' => method_exists($order, 'getShippingMethod') && $order->getShippingMethod()
                                ? (method_exists($order->getShippingMethod(), 'getName') ? $order->getShippingMethod()->getName() : null)
                                : null,
            'tracking_number' => method_exists($order, 'getTrackingNumber') ? $order->getShippingMethodCode() : null,
        ];
    }

    /**
     * Rendu du HTML d'un EmailTemplate via Twig::createTemplate
     */
    public function renderHtmlFromTemplate(EmailTemplate $tpl, array $context): string
    {
        $template = $this->twig->createTemplate($tpl->getContentHtml() ?? '');
        return $template->render($context);
    }

    /**
     * Rendu d'une chaîne (ex. le "subject") avec Twig (placeholders autorisés).
     */
    public function renderString(string $str, array $context): string
    {
        return $this->twig->createTemplate($str)->render($context);
    }

    /**
     * Envoi manuel (depuis le formulaire EasyAdmin).
     * Journalise dans EmailLog quoi qu'il arrive.
     */
    public function sendManual(
        Order $order,
        string $to,
        string $subject,
        string $bodyHtml,
        ?EmailTemplate $template = null,
        bool $attachInvoice = false
    ): EmailLog {
        $log = $this->initLog($order, $to, $subject, $bodyHtml, $template);

        try {
            $email = $this->baseEmail($to, $subject, $bodyHtml);

            // Logo CID à partir du template (si disponible)
            $this->maybeEmbedLogoCid($email, $template);

            // Pièce jointe facture si demandé
            if ($attachInvoice) {
                $this->maybeAttachInvoice($email, $order);
            }

            $this->mailer->send($email);
            $log->setSuccess(true);
        } catch (\Throwable $e) {
            $log->setSuccess(false);
            $log->setErrorMessage($e->getMessage());
        }

        $this->em->persist($log);
        $this->em->flush();

        return $log;
    }

    /**
     * Envoi automatique lors d'un changement de statut.
     * - Cherche un EmailTemplate actif lié au $newStatus (relatedStatus)
     * - Rend le sujet et le HTML avec Twig
     * - Envoie à l'email du client résolu via buildContext()
     * Retourne null si aucun template actif trouvé.
     */
    public function sendOnStatusChange(Order $order, ?string $oldStatus, string $newStatus): ?EmailLog
    {
        $tpl = $this->templateRepo->findOneBy([
            'relatedStatus' => $newStatus,
            'isActive'      => true,
        ]);

        if (!$tpl) {
            return null; // rien à faire si pas de template pour ce statut
        }

        return $this->sendUsingTemplate($order, $tpl, null, true);
    }

    /**
     * Envoi basé sur un EmailTemplate (utile pour "envoyer à nouveau" ou l'auto).
     * $overrideTo permet de forcer un destinataire, sinon on prend l'email client du contexte.
     */
    public function sendUsingTemplate(
        Order $order,
        EmailTemplate $template,
        ?string $overrideTo = null,
        bool $attachInvoice = false
    ): EmailLog {
        $context = $this->buildContext($order);

        // Sujet ET corps supportent Twig (placeholders autorisés)
        $subject = $this->renderString($template->getSubject() ?? 'Votre commande', $context);
        $html    = $this->renderHtmlFromTemplate($template, $context);

        $to = $overrideTo ?: ($context['customer_email'] ?? null);
        if (!$to) {
            // si on ne trouve pas de destinataire, on log une erreur mais on journalise quand même
            $log = $this->initLog($order, '(destinataire introuvable)', $subject, $html, $template);
            $log->setSuccess(false);
            $log->setErrorMessage('Aucun destinataire (customer_email) trouvé pour la commande #'.$order->getId().'.');
            $this->em->persist($log);
            $this->em->flush();
            return $log;
        }

        $log = $this->initLog($order, $to, $subject, $html, $template);

        try {
            $email = $this->baseEmail($to, $subject, $html);
            $this->maybeEmbedLogoCid($email, $template);

            if ($attachInvoice) {
                $this->maybeAttachInvoice($email, $order);
            }

            $this->mailer->send($email);
            $log->setSuccess(true);
        } catch (\Throwable $e) {
            $log->setSuccess(false);
            $log->setErrorMessage($e->getMessage());
        }

        $this->em->persist($log);
        $this->em->flush();

        return $log;
    }

    /* =========================
       Helpers privés
       ========================= */

    private function initLog(
        Order $order,
        string $to,
        string $subject,
        string $bodyHtml,
        ?EmailTemplate $template
    ): EmailLog {
        $log = new EmailLog();
        $log->setOrderRef($order);
        $log->setRecipient($to);
        $log->setSubject($subject);
        $log->setBodyHtml($bodyHtml);
        $log->setTemplate($template);
        // sentAt est fixé dans le constructeur d'EmailLog
        return $log;
    }

    private function baseEmail(string $to, string $subject, string $html): Email
    {
        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($to)
            ->subject($subject)
            ->html($html);

        if ($this->replyTo) {
            $email->replyTo($this->replyTo);
        }

        return $email;
    }

    /**
     * Si un logo est uploadé sur le template, l'embarque en CID "logo_cid".
     * Dans ton HTML utilise : <img src="cid:logo_cid" alt="Logo">
     */
    private function maybeEmbedLogoCid(Email $email, ?EmailTemplate $template): void
    {
        if (!$template || !$template->getLogoName()) {
            return;
        }
        $path = $this->projectDir.'/public/uploads/email_templates/'.$template->getLogoName();
        if (is_file($path)) {
            $email->embedFromPath($path, 'logo_cid');
        }
    }

    /**
     * Attache la facture PDF si un chemin valide est trouvable.
     * - Priorité à Order::getInvoicePdfPath()
     * - Fallbacks courants : /var/invoices/invoice-{id}.pdf, /public/var/invoices/…
     */
    private function maybeAttachInvoice(Email $email, Order $order): void
    {
        $path = $this->resolveInvoicePath($order);
        if ($path && is_file($path)) {
            $email->attachFromPath(
                $path,
                'facture-'.$this->safeId($order).'.pdf',
                'application/pdf'
            );
        }
    }

    private function resolveInvoicePath(Order $order): ?string
    {
        if (method_exists($order, 'getInvoicePdfPath') && $order->getInvoicePdfPath()) {
            $p = $order->getInvoicePdfPath();
            if (is_string($p) && is_file($p)) {
                return $p;
            }
            // s'il renvoie un chemin relatif, tente depuis projectDir
            if (is_string($p) && is_file($this->projectDir.'/'.ltrim($p, '/'))) {
                return $this->projectDir.'/'.ltrim($p, '/');
            }
        }

        $candidates = [
            $this->projectDir.'/var/invoices/invoice-'.$this->safeId($order).'.pdf',
            $this->projectDir.'/public/var/invoices/invoice-'.$this->safeId($order).'.pdf',
            $this->projectDir.'/public/invoices/invoice-'.$this->safeId($order).'.pdf',
        ];

        foreach ($candidates as $c) {
            if (is_file($c)) {
                return $c;
            }
        }

        return null;
    }

    private function safeId(Order $order): string
    {
        // getId() est souvent un int ; on caste en string par sécurité
        return (string) (method_exists($order, 'getId') ? $order->getId() : uniqid('order_', true));
    }

    /**
     * Essaie plusieurs conventions pour lire le total (en centimes, en décimal…)
     * et renvoie une chaîne formatée ("1 234,56 €").
     */
    private function formatOrderTotal(Order $order): ?string
    {
        $amount = null;

        // total en centimes ?
        foreach (['getTotal', 'getTotalCents', 'getTotalAmount'] as $m) {
            if (method_exists($order, $m)) {
                $val = $order->{$m}();
                if (is_numeric($val)) {
                    // heuristique : si > 100000 on suppose des centimes
                    if ($val > 1000 || str_contains($m, 'Cents') || str_contains($m, 'Amount')) {
                        $amount = ((float) $val) / 100.0;
                    } else {
                        $amount = (float) $val;
                    }
                    break;
                }
            }
        }

        if ($amount === null) {
            return null;
        }

        return number_format($amount, 2, ',', ' ').' €';
    }
}

<?php

declare(strict_types=1);

namespace App\Controller\Stripe;

use App\Service\Checkout\StripeEventProcessor;
use Psr\Log\LoggerInterface;
use Stripe\Event as StripeEvent;
use Stripe\Webhook as StripeWebhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebhookController
{
    public function __construct(
        private readonly StripeEventProcessor $processor,
        private readonly LoggerInterface $logger,
        private readonly ?string $webhookSecret = null, // nullable pour éviter un fatal si non injecté
    ) {}

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $sig     = $request->headers->get('Stripe-Signature');
        $payload = $request->getContent();

        // Log de diagnostic au plus tôt
        $this->logger->info('[Stripe] Webhook hit', [
            'has_signature_header' => $sig !== null,
            'payload_len'          => \strlen($payload ?? ''),
            'secret_len'           => $this->webhookSecret ? \strlen($this->webhookSecret) : 0,
            'unsafe_mode'          => ($_ENV['STRIPE_ALLOW_UNSAFE'] ?? getenv('STRIPE_ALLOW_UNSAFE')) === '1',
        ]);

        try {
            // Mode "smoke test" (désactive la vérification de signature) :
            if (($_ENV['STRIPE_ALLOW_UNSAFE'] ?? getenv('STRIPE_ALLOW_UNSAFE')) === '1') {
                $decoded = \json_decode($payload ?: '{}', true, 512, \JSON_THROW_ON_ERROR);
                /** @var StripeEvent $event */
                $event = StripeEvent::constructFrom($decoded);
            } else {
                if (empty($this->webhookSecret)) {
                    $this->logger->error('[Stripe] Missing webhook secret (server misconfigured)');
                    return new Response('server misconfigured', 500);
                }
                // Vérification de signature officielle Stripe
                $event = StripeWebhook::constructEvent($payload, $sig, $this->webhookSecret);
            }
        } catch (\Throwable $e) {
            // Signature invalide OU JSON invalide en mode "unsafe"
            $this->logger->error('[Stripe] Webhook signature/payload error: ' . $e->getMessage());
            return new Response('invalid signature', 400);
        }

        try {
            $this->processor->handle($event);
        } catch (\Throwable $e) {
            $this->logger->error('[Stripe] Webhook processing failed: ' . $e->getMessage());
            return new Response('error', 500);
        }

        return new Response('ok', 200);
    }
}

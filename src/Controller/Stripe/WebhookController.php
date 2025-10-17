<?php

namespace App\Controller\Stripe;

use App\Service\StripeEventProcessor;
use Psr\Log\LoggerInterface;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class WebhookController
{
    public function __construct(
        private readonly StripeEventProcessor $processor,
        private readonly LoggerInterface $logger,
        private readonly string $webhookSecret,
    ) {}

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $sig     = $request->headers->get('Stripe-Signature');
        $payload = $request->getContent();

        try {
            $event = Webhook::constructEvent($payload, $sig, $this->webhookSecret);
        } catch (\Throwable $e) {
            $this->logger->error('Stripe webhook signature error: ' . $e->getMessage());
            return new Response('invalid signature', 400);
        }

        try {
            $this->processor->handle($event);
        } catch (\Throwable $e) {
            $this->logger->error('Stripe webhook processing failed: ' . $e->getMessage());
            return new Response('error', 500);
        }

        return new Response('ok', 200);
    }
}

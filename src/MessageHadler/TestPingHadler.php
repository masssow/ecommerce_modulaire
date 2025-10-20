<?php

namespace App\MessageHandler;

use App\Message\TestPing;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class TestPingHandler
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(TestPing $message): void
    {
        $this->logger->info('[TestPingHandler] Received: ' . $message->getPayload());
    }
}

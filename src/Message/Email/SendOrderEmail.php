<?php
namespace App\Message\Email;

final class SendOrderEmail
{
    public function __construct(
        public int $orderId,
        public string $type,              // 'paid' | 'failed'
        public ?string $overrideTo = null // pour tests
    ) {}
}

<?php
namespace App\Payment\Event;

final class PaymentFailed
{
    public function __construct(public int $orderId, public ?string $reason = null) {}
}

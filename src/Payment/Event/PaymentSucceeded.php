<?php
namespace App\Payment\Event;

final class PaymentSucceeded
{
    public function __construct(public int $orderId) {}
}

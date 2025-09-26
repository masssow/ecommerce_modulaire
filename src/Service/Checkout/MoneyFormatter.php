<?php

namespace App\Service\Checkout;

final class MoneyFormatter
{
    public function eurCents(int $amountCts): string
    {
        return number_format($amountCts / 100, 2, ',', ' ') . ' €';
    }
}

<?php

namespace App\Service\Checkout;

use App\Service\CartManager;

/** Cas d’usage : vider le panier proprement. */
final class CartClearer
{
    public function __construct(private readonly CartManager $cart) {}

    public function clear(): void
    {
        $this->cart->clear();
    }
}

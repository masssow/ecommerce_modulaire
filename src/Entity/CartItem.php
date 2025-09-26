<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Quantité d'articles */
    #[ORM\Column]
    private int $quantity = 1;

    /**
     * Prix unitaire TTC en centimes (ex: 1234 = 12,34 €)
     */
    #[ORM\Column(type: 'integer')]
    private int $unitPrice = 0;

    #[ORM\ManyToOne(inversedBy: 'cartItems')]
    private ?Cart $cart = null;

    #[ORM\ManyToOne(inversedBy: 'cartItems')]
    private ?ProductVariant $productVariant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = max(0, $quantity);
        return $this;
    }

    /**
     * Retourne le prix unitaire TTC en centimes.
     */
    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    /**
     * Définit le prix unitaire TTC en centimes.
     */
    public function setUnitPrice(int $unitPrice): self
    {
        $this->unitPrice = max(0, $unitPrice);
        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): self
    {
        $this->cart = $cart;
        return $this;
    }

    public function getProductVariant(): ?ProductVariant
    {
        return $this->productVariant;
    }

    public function setProductVariant(?ProductVariant $productVariant): self
    {
        $this->productVariant = $productVariant;
        return $this;
    }

    /**
     * Total de la ligne = quantité * prix unitaire (centimes)
     */
    public function getLineTotal(): int
    {
        return $this->quantity * $this->unitPrice;
    }
}

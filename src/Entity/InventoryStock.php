<?php

namespace App\Entity;

use App\Repository\InventoryStockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryStockRepository::class)]
class InventoryStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantity = 0;

    #[ORM\Column]
    private ?int $reserved = 0;

    #[ORM\OneToOne(inversedBy: 'inventoryStock', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?ProductVariant $productVariant = null;

    public function __toString(): string
    {
        // Adapte le rendu si tu veux plus d’infos
        $qty = $this->getQuantity();
        return $qty !== null ? sprintf('Stock: %d', $qty) : 'Stock: 0';
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getReserved(): ?int
    {
        return $this->reserved;
    }

    public function setReserved(int $reserved): static
    {
        $this->reserved = $reserved;

        return $this;
    }

    public function getProductVariant(): ?ProductVariant
    {
        return $this->productVariant;
    }

    public function setProductVariant(?ProductVariant $productVariant): static
    {
        $this->productVariant = $productVariant;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?int $unitPrice = null;

    #[ORM\Column]
    private ?int $totalPrice = null;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    private ?ProductVariant $productVariant = null;

    /**
     * @var Collection<int, ReturnItem>
     */
    #[ORM\OneToMany(targetEntity: ReturnItem::class, mappedBy: 'orderItem')]
    private Collection $returnItems;

    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    private ?Order $orders = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $currency = null;

    public function __construct()
    {
        $this->returnItems = new ArrayCollection();
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

    public function getUnitPrice(): ?int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): self    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getTotalPrice(): ?int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice):self
    {
        $this->totalPrice = $totalPrice;

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

    /**
     * @return Collection<int, ReturnItem>
     */
    public function getReturnItems(): Collection
    {
        return $this->returnItems;
    }

    public function addReturnItem(ReturnItem $returnItem): static
    {
        if (!$this->returnItems->contains($returnItem)) {
            $this->returnItems->add($returnItem);
            $returnItem->setOrderItem($this);
        }

        return $this;
    }

    public function removeReturnItem(ReturnItem $returnItem): static
    {
        if ($this->returnItems->removeElement($returnItem)) {
            // set the owning side to null (unless already changed)
            if ($returnItem->getOrderItem() === $this) {
                $returnItem->setOrderItem(null);
            }
        }

        return $this;
    }

    public function getOrders(): ?Order
    {
        return $this->orders;
    }

    public function setOrders(?Order $orders): static
    {
        $this->orders = $orders;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }
}

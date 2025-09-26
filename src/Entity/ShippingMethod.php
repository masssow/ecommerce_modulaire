<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ShippingMethodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShippingMethodRepository::class)]
#[ApiResource (
    normalizationContext: ['groups' => ['shipping_method:read']],
    denormalizationContext: ['groups' => ['shipping_method:write']]
)]
class ShippingMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $carrier = null;

    #[ORM\Column]
    private ?int $baseCost = 0;

    /**
     * @var Collection<int, Shipment>
     */
    #[ORM\OneToMany(targetEntity: Shipment::class, mappedBy: 'shippingMethod')]
    private Collection $shipments;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $freeShippingThreshold = null;

    public function __construct()
    {
        $this->shipments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(string $carrier): static
    {
        $this->carrier = $carrier;

        return $this;
    }

    public function getBaseCost(): ?float
    {
        return $this->baseCost;
    }

    public function setBaseCost(float $baseCost): self
    {
        $this->baseCost = $baseCost;

        return $this;
    }

    /**
     * @return Collection<int, Shipment>
     */
    public function getShipments(): Collection
    {
        return $this->shipments;
    }

    public function addShipment(Shipment $shipment): static
    {
        if (!$this->shipments->contains($shipment)) {
            $this->shipments->add($shipment);
            $shipment->setShippingMethod($this);
        }

        return $this;
    }

    public function removeShipment(Shipment $shipment): static
    {
        if ($this->shipments->removeElement($shipment)) {
            // set the owning side to null (unless already changed)
            if ($shipment->getShippingMethod() === $this) {
                $shipment->setShippingMethod(null);
            }
        }

        return $this;
    }

    public function getFreeShippingThreshold(): ?int
    {
        return $this->freeShippingThreshold;
    }

    public function setFreeShippingThreshold(?int $threshold): self
    {
        $this->freeShippingThreshold = $threshold;
        return $this;
    }
}

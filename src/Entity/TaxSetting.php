<?php

namespace App\Entity;

use App\Repository\TaxSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaxSettingRepository::class)]
class TaxSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $rate = null;

    #[ORM\Column(nullable: true)]
    private ?float $shippingFee = null;

    #[ORM\Column]
    private ?float $tva = null;

    #[ORM\Column(nullable: true)]
    private ?float $freeShippingThreshold = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function getShippingFee(): ?float
    {
        return $this->shippingFee;
    }

    public function setShippingFee(?float $shippingFee): static
    {
        $this->shippingFee = $shippingFee;

        return $this;
    }

    public function getTva(): ?float
    {
        return $this->tva;
    }

    public function setTva(float $tva): static
    {
        $this->tva = $tva;

        return $this;
    }

    public function getFreeShippingThreshold(): ?float
    {
        return $this->freeShippingThreshold;
    }

    public function setFreeShippingThreshold(?float $freeShippingThreshold): static
    {
        $this->freeShippingThreshold = $freeShippingThreshold;

        return $this;
    }
}

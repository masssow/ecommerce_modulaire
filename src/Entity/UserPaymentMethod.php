<?php

namespace App\Entity;

use App\Repository\UserPaymentMethodRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPaymentMethodRepository::class)]

#[ORM\Table(
    name: 'payment_instrument',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'uniq_user_fingerprint', columns: ['user_id', 'fingerprint'])
    ],
    indexes: [
        new ORM\Index(name: 'idx_user_default', columns: ['user_id', 'is_default'])
    ]
)]

class UserPaymentMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userPaymentMethods')]
    private ?User $user = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $gateway = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isDefault = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $last4 = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $expMonth = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $expYear = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $fingerprint = null;

    public function __construct()
    {
        // Valeur par défaut au new
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function setCreatedAtIfMissing(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getGateway(): ?string
    {
        return $this->gateway;
    }

    public function setGateway(?string $gateway): static
    {
        $this->gateway = $gateway;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(?bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getLast4(): ?string
    {
        return $this->last4;
    }

    public function setLast4(?string $last4): static
    {
        $this->last4 = $last4;

        return $this;
    }

    public function getExpMonth(): ?int
    {
        return $this->expMonth;
    }

    public function setExpMonth(?int $expMonth): static
    {
        $this->expMonth = $expMonth;

        return $this;
    }

    public function getExpYear(): ?int
    {
        return $this->expYear;
    }

    public function setExpYear(?int $expYear): static
    {
        $this->expYear = $expYear;

        return $this;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(?string $fingerprint): static
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }
}

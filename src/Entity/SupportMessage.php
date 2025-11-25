<?php

namespace App\Entity;

use App\Repository\SupportMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SupportMessageRepository::class)]
class SupportMessage
{
    public const TYPE_CONTACT           = 'CONTACT';
    public const TYPE_SELLER_APPLICATION = 'SELLER_APPLICATION';
    public const TYPE_RETURN_REQUEST    = 'RETURN_REQUEST';

    public const STATUS_NEW        = 'NEW';
    public const STATUS_INPROGRESS = 'IN_PROGRESS';
    public const STATUS_CLOSED     = 'CLOSED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $body = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $orderNumber = null;

    #[ORM\ManyToOne(inversedBy: 'supportMessages')]
    private ?Order $orderRequest = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $requestKind = null;

    #[ORM\Column(nullable: true)]
    private ?int $credit = null;

    #[ORM\Column(length: 100)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        // Valeurs par défaut
        $this->status    = self::STATUS_NEW;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getOrderRequest(): ?order
    {
        return $this->orderRequest;
    }

    public function setOrderRequest(?order $orderRequest): static
    {
        $this->orderRequest = $orderRequest;

        return $this;
    }

    public function getRequestKind(): ?string
    {
        return $this->requestKind;
    }

    public function setRequestKind(?string $requestKind): static
    {
        $this->requestKind = $requestKind;

        return $this;
    }

    public function getCredit(): ?int
    {
        return $this->credit;
    }

    public function setCredit(?int $credit): static
    {
        $this->credit = $credit;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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
}

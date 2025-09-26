<?php

namespace App\Entity;

use App\Repository\AdresseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AdresseRepository::class)]
#[ORM\Table(name: 'adresse')]
class Adresse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Optionnel : rattacher l'adresse au propriétaire (compte)
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'adresses')]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?User $user = null;

    // Alias lisible côté UI (ex: "Domicile", "Bureau") — optionnel
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $label = null;

    #[Assert\NotBlank(message: "L'adresse ne peut pas être vide")]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $line1 = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $line2 = null;

    #[Assert\NotBlank(message: "Veuillez renseigner le code postal")]
    #[ORM\Column(type: 'string', length: 20, nullable: false)]
    private ?string $postalCode = null;

    #[Assert\NotBlank(message: "Veuillez renseigner la ville")]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $city = null;

    #[Assert\NotBlank(message: "Veuillez renseigner le pays")]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $country = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isDefault = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // Optionnel : tag fonctionnel (ex: "livraison" / "facturation")
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $phone = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /* =================== Getters / Setters =================== */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getLine1(): ?string
    {
        return $this->line1;
    }

    public function setLine1(?string $line1): self
    {
        $this->line1 = $line1;
        return $this;
    }

    public function getLine2(): ?string
    {
        return $this->line2;
    }

    public function setLine2(?string $line2): self
    {
        $this->line2 = $line2;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(?bool $isDefault): self
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }
}

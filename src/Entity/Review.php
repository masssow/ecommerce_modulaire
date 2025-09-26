<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReviewRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\State\CreateReviewProcessor;
use App\State\VerifiedReviewProvider;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;


#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['product' => 'exact', 'isVerified' => 'exact'])]
#[ApiResource(
    normalizationContext: ['groups' => ['review:read']],
    denormalizationContext: ['groups' => ['review:write']],
    operations: [
        // ➜ toujours filtré côté serveur : uniquement les avis vérifiés
        new GetCollection(provider: VerifiedReviewProvider::class),
        new Post(processor: CreateReviewProcessor::class, security: 'is_granted(\"ROLE_USER\")'),
        new Get(
            security: 'object.isVerified() or is_granted("ROLE_ADMIN") or object.getCustomer() == user.getCustomer()'
        ),
        new Patch(security: 'is_granted("ROLE_ADMIN")'),
        new Delete(security: 'is_granted("ROLE_ADMIN")')
    ]
)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?bool $isVerified = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    private ?Customer $customer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

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

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }
}

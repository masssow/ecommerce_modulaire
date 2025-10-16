<?php

namespace App\Entity;

use App\Entity\ProductVariant;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Repository\FavoriteRepository;

#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
#[ORM\Table(name: 'favorite')]
#[ORM\UniqueConstraint(
    name: 'uniq_favorite_user_variant',
    columns: ['user_id', 'product_variant_id']
)]
class Favorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'favorites')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: ProductVariant::class, inversedBy: 'favorites')]
    #[ORM\JoinColumn(name: 'product_variant_id', nullable: false, onDelete: 'CASCADE')]
    private ?ProductVariant $productVariant = null;

    #[ORM\Column] private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

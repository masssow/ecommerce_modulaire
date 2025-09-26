<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PromotionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PromotionRepository::class)]
#[ApiResource (
    normalizationContext: ['groups' => ['promotion:read']],
    denormalizationContext: ['groups' => ['promotion:write']]
)]
class Promotion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $endsAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $enable = null;

    /**
     * @var Collection<int, PromotionRule>
     */
    #[ORM\OneToMany(targetEntity: PromotionRule::class, mappedBy: 'promotion')]
    private Collection $rule;

    /**
     * @var Collection<int, Coupon>
     */
    #[ORM\OneToMany(targetEntity: Coupon::class, mappedBy: 'promotion')]
    private Collection $coupon;

    public function __construct()
    {
        $this->rule = new ArrayCollection();
        $this->coupon = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function isEnable(): ?bool
    {
        return $this->enable;
    }

    public function setEnable(?bool $enable): static
    {
        $this->enable = $enable;

        return $this;
    }

    /**
     * @return Collection<int, PromotionRule>
     */
    public function getRule(): Collection
    {
        return $this->rule;
    }

    public function addRule(PromotionRule $rule): static
    {
        if (!$this->rule->contains($rule)) {
            $this->rule->add($rule);
            $rule->setPromotion($this);
        }

        return $this;
    }

    public function removeRule(PromotionRule $rule): static
    {
        if ($this->rule->removeElement($rule)) {
            // set the owning side to null (unless already changed)
            if ($rule->getPromotion() === $this) {
                $rule->setPromotion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Coupon>
     */
    public function getCoupon(): Collection
    {
        return $this->coupon;
    }

    public function addCoupon(Coupon $coupon): static
    {
        if (!$this->coupon->contains($coupon)) {
            $this->coupon->add($coupon);
            $coupon->setPromotion($this);
        }

        return $this;
    }

    public function removeCoupon(Coupon $coupon): static
    {
        if ($this->coupon->removeElement($coupon)) {
            // set the owning side to null (unless already changed)
            if ($coupon->getPromotion() === $this) {
                $coupon->setPromotion(null);
            }
        }

        return $this;
    }
}

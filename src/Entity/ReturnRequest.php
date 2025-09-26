<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ReturnRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReturnRequestRepository::class)]
#[ApiResource (
    normalizationContext: ['groups' => ['return_request:read']],
    denormalizationContext: ['groups' => ['return_request:write']]
)]
class ReturnRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $requestedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $refundedAt = null;

    #[ORM\ManyToOne(inversedBy: 'returnRequests')]
    private ?Order $orders = null;

    /**
     * @var Collection<int, ReturnItem>
     */
    #[ORM\OneToMany(targetEntity: ReturnItem::class, mappedBy: 'returnRequest')]
    private Collection $returnItems;

    public function __construct()
    {
        $this->returnItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRequestedAt(): ?\DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(\DateTimeImmutable $requestedAt): static
    {
        $this->requestedAt = $requestedAt;

        return $this;
    }

    public function getRefundedAt(): ?\DateTimeImmutable
    {
        return $this->refundedAt;
    }

    public function setRefundedAt(?\DateTimeImmutable $refundedAt): static
    {
        $this->refundedAt = $refundedAt;

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
            $returnItem->setReturnRequest($this);
        }

        return $this;
    }

    public function removeReturnItem(ReturnItem $returnItem): static
    {
        if ($this->returnItems->removeElement($returnItem)) {
            // set the owning side to null (unless already changed)
            if ($returnItem->getReturnRequest() === $this) {
                $returnItem->setReturnRequest(null);
            }
        }

        return $this;
    }
}

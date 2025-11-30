<?php

namespace App\Entity;

use App\Entity\Favorite; 
use App\Entity\InventoryStock;
use App\ValueObject\Money;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ProductVariantRepository::class)]
#[Vich\Uploadable]
class ProductVariant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'productVariants')]
    private ?Product $product = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

   
    #[ORM\Embedded(class: Money::class)]
    private Money $price;

    #[ORM\Column(nullable: true)]
    private ?array $Attributes = null;

    #[ORM\OneToOne(mappedBy: 'productVariant', cascade: ['persist', 'remove'])]
    private ?InventoryStock $inventoryStock = null;

    /**
     * @var Collection<int, Price>
     */
    #[ORM\OneToMany(targetEntity: Price::class, mappedBy: 'variant')]
    private Collection $prices;

    /**
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(targetEntity: CartItem::class, mappedBy: 'productVariant')]
    private Collection $cartItems;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'productVariant')]
    private Collection $orderItems;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[Vich\UploadableField(mapping: 'productVariant', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Favorite>
     */
    #[ORM\OneToMany(
        targetEntity: Favorite::class,
        mappedBy: 'productVariant',        // 👈 ICI camelCase correct
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $favorites;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function __toString(): string
    {
        $p = $this->getProduct()?->getName() ?? 'Produit';
        $v = $this->name ?? 'Variante';
        return "$p – $v";
    }


    public function __construct()
    {
        $this->price = new Money(0, 'EUR'); // valeur par défaut
        $this->prices = new ArrayCollection();
        $this->cartItems = new ArrayCollection();
        $this->orderItems = new ArrayCollection();
        $this->favorites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }
    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }
    public function setPrice(Money $price): self
    {
        $this->price = $price;
        return $this;
    }
    public function setPriceAmount(int $amount): self
    {
        // garde-fou si jamais $this->price est null (théoriquement initialisé dans __construct)
        if (!isset($this->price)) {
            $this->price = new Money(0, 'EUR');
        }
        $this->price = $this->price->withAmount($amount);
        return $this;
    }

    public function setPriceCurrency(string $currency): self
    {
        if (!isset($this->price)) {
            $this->price = new Money(0, strtoupper($currency ?: 'EUR'));
            return $this;
        }
        $this->price = $this->price->withCurrency(strtoupper($currency));
        return $this;
    }
    public function getPriceAmount(): int
    {
        return $this->price->amount();
    }         // en centimes
    public function getPriceCurrency(): string
    {
        return $this->price->currency();
    }  // ex: "EUR"

    public function getAttributes(): ?array
    {
        return $this->Attributes;
    }
    public function setAttributes(?array $Attributes): static
    {
        $this->Attributes = $Attributes;
        return $this;
    }

    public function getInventoryStock(): ?InventoryStock
    {
        return $this->inventoryStock;
    }
    public function setInventoryStock(?InventoryStock $inventoryStock): static
    {
        if ($inventoryStock === null && $this->inventoryStock !== null) {
            $this->inventoryStock->setProductVariant(null);
        }
        if ($inventoryStock !== null && $inventoryStock->getProductVariant() !== $this) {
            $inventoryStock->setProductVariant($this);
        }
        $this->inventoryStock = $inventoryStock;
        return $this;
    }

    /** @return Collection<int, Price> */
    public function getPrices(): Collection
    {
        return $this->prices;
    }
    public function addPrice(Price $price): static
    {
        if (!$this->prices->contains($price)) {
            $this->prices->add($price);
            $price->setVariant($this);
        }
        return $this;
    }
    public function removePrice(Price $price): static
    {
        if ($this->prices->removeElement($price)) {
            if ($price->getVariant() === $this) {
                $price->setVariant(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, CartItem> */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }
    public function addCartItem(CartItem $cartItem): static
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
            $cartItem->setProductVariant($this);
        }
        return $this;
    }
    public function removeCartItem(CartItem $cartItem): static
    {
        if ($this->cartItems->removeElement($cartItem)) {
            if ($cartItem->getProductVariant() === $this) {
                $cartItem->setProductVariant(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, OrderItem> */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }
    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setProductVariant($this);
        }
        return $this;
    }
    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getProductVariant() === $this) {
                $orderItem->setProductVariant(null);
            }
        }
        return $this;
    }

    public function getStockQty(): int
    {
        return $this->inventoryStock?->getQuantity() ?? 0;
    }

    public function setStockQty(int $qty): self
    {
        $qty = max(0, $qty);
        if (!$this->inventoryStock) {
            $inv = new InventoryStock();
            $inv->setProductVariant($this);   // ⚠️ côté propriétaire dans InventoryStock
            $this->inventoryStock = $inv;     // côté inverse ici
        }
        $this->inventoryStock->setQuantity($qty);
        return $this;
    }
    
    public function getImageName(): ?string
    {
        return $this->imageName;
    }
    public function setImageName(?string $imageName): static
    {
        $this->imageName = $imageName;
        return $this;
    }

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setProductVariant($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getProductVariant() === $this) {
                $favorite->setProductVariant(null);
            }
        }

        return $this;
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
}

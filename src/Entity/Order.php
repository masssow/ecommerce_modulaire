<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Référence fonctionnelle */
    #[ORM\Column(length: 255)]
    private ?string $number = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?Customer $customer = null;

    /**
     * @var Collection<int, Shipment>
     */
    #[ORM\OneToMany(targetEntity: Shipment::class, mappedBy: 'orders')]
    private Collection $shipments;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'orders')]
    private Collection $payments;

    /**
     * @var Collection<int, ReturnRequest>
     */
    #[ORM\OneToMany(targetEntity: ReturnRequest::class, mappedBy: 'orders')]
    private Collection $returnRequests;

    /**
     * @var Collection<int, Dispute>
     */
    #[ORM\OneToMany(targetEntity: Dispute::class, mappedBy: 'orders')]
    private Collection $disputes;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'orders', cascade: ['persist'])]
    private Collection $orderItems;

    /** Sous-total TTC en centimes */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $subtotal = null;

    /** Frais de port TTC en centimes */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $shippingTotal = null;

    /** TVA en centimes */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $taxTotal = null;

    /** Total TTC en centimes (source de vérité côté Order) */
    #[ORM\Column(type: 'integer')]
    private int $grandTotal = 0;

    /** Devise ISO (ex: EUR) */
    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    /** Adresse de livraison choisie au moment du checkout */
    #[ORM\ManyToOne(targetEntity: Adresse::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Adresse $shippingAddress = null;

    /** Adresse de facturation choisie au moment du checkout */
    #[ORM\ManyToOne(targetEntity: Adresse::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Adresse $billingAddress = null;

    // ========= LIVRAISON CHOISIE (relation + snapshot) =========

    #[ORM\ManyToOne(targetEntity: ShippingMethod::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?ShippingMethod $shippingMethod = null;

    /** Nom du mode de livraison au moment de la commande (snapshot) */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $shippingMethodName = null;

    /** Code technique (snapshot) : ex "colissimo_home", "mondialrelay" */
    #[ORM\Column(length: 60, nullable: true)]
    private ?string $shippingMethodCode = null;

    // ========= PAIEMENT (relation + snapshot + Stripe/PayPal) =========

    #[ORM\ManyToOne(targetEntity: PaymentMethod::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?PaymentMethod $paymentMethod = null;

    /** Libellé du mode de paiement au moment de la commande (snapshot) */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $paymentMethodName = null;

    /** Passerelle utilisée: 'stripe' | 'paypal' | ... */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentGateway = null;

    /** Type de moyen: 'card', 'sepa_debit', 'paypal', ... (Stripe payment_method.type) */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $paymentMethodType = null;

    // ========= STRIPE =========

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $stripePmBrand = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $stripePmLast4 = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->shipments = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->returnRequests = new ArrayCollection();
        $this->disputes = new ArrayCollection();
        $this->orderItems = new ArrayCollection();
    }

    /* ==================== Getters / Setters ==================== */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }
    public function setNumber(string $number): static
    {
        $this->number = $number;
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

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }
    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;
        return $this;
    }

    /** @return Collection<int, Shipment> */
    public function getShipments(): Collection
    {
        return $this->shipments;
    }
    public function addShipment(Shipment $shipment): static
    {
        if (!$this->shipments->contains($shipment)) {
            $this->shipments->add($shipment);
            $shipment->setOrders($this);
        }
        return $this;
    }
    public function removeShipment(Shipment $shipment): static
    {
        if ($this->shipments->removeElement($shipment)) {
            if ($shipment->getOrders() === $this) {
                $shipment->setOrders(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Payment> */
    public function getPayments(): Collection
    {
        return $this->payments;
    }
    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setOrders($this);
        }
        return $this;
    }
    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getOrders() === $this) {
                $payment->setOrders(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, ReturnRequest> */
    public function getReturnRequests(): Collection
    {
        return $this->returnRequests;
    }
    public function addReturnRequest(ReturnRequest $returnRequest): static
    {
        if (!$this->returnRequests->contains($returnRequest)) {
            $this->returnRequests->add($returnRequest);
            $returnRequest->setOrders($this);
        }
        return $this;
    }
    public function removeReturnRequest(ReturnRequest $returnRequest): static
    {
        if ($this->returnRequests->removeElement($returnRequest)) {
            if ($returnRequest->getOrders() === $this) {
                $returnRequest->setOrders(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Dispute> */
    public function getDisputes(): Collection
    {
        return $this->disputes;
    }
    public function addDispute(Dispute $dispute): static
    {
        if (!$this->disputes->contains($dispute)) {
            $this->disputes->add($dispute);
            $dispute->setOrders($this);
        }
        return $this;
    }
    public function removeDispute(Dispute $dispute): static
    {
        if ($this->disputes->removeElement($dispute)) {
            if ($dispute->getOrders() === $this) {
                $dispute->setOrders(null);
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
            $orderItem->setOrders($this);
        }
        return $this;
    }
    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getOrders() === $this) {
                $orderItem->setOrders(null);
            }
        }
        return $this;
    }

    public function getSubtotal(): ?int
    {
        return $this->subtotal;
    }
    public function setSubtotal(?int $subtotal): static
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getShippingTotal(): ?int
    {
        return $this->shippingTotal;
    }
    public function setShippingTotal(?int $shippingTotal): static
    {
        $this->shippingTotal = $shippingTotal;
        return $this;
    }

    public function getTaxTotal(): ?int
    {
        return $this->taxTotal;
    }
    public function setTaxTotal(?int $taxTotal): static
    {
        $this->taxTotal = $taxTotal;
        return $this;
    }

    public function getGrandTotal(): int
    {
        return $this->grandTotal;
    }
    public function setGrandTotal(int $grandTotal): static
    {
        $this->grandTotal = $grandTotal;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getShippingAddress(): ?Adresse
    {
        return $this->shippingAddress;
    }
    public function setShippingAddress(?Adresse $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    public function getBillingAddress(): ?Adresse
    {
        return $this->billingAddress;
    }
    public function setBillingAddress(?Adresse $billingAddress): static
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function getShippingMethod(): ?ShippingMethod
    {
        return $this->shippingMethod;
    }
    public function setShippingMethod(?ShippingMethod $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;
        return $this;
    }

    public function getShippingMethodName(): ?string
    {
        return $this->shippingMethodName;
    }
    public function setShippingMethodName(?string $name): static
    {
        $this->shippingMethodName = $name;
        return $this;
    }

    public function getShippingMethodCode(): ?string
    {
        return $this->shippingMethodCode;
    }
    public function setShippingMethodCode(?string $code): static
    {
        $this->shippingMethodCode = $code;
        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }
    public function setPaymentMethod(?PaymentMethod $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentMethodName(): ?string
    {
        return $this->paymentMethodName;
    }
    public function setPaymentMethodName(?string $name): static
    {
        $this->paymentMethodName = $name;
        return $this;
    }

    public function getPaymentGateway(): ?string
    {
        return $this->paymentGateway;
    }
    public function setPaymentGateway(?string $gateway): static
    {
        $this->paymentGateway = $gateway;
        return $this;
    }

    public function getPaymentMethodType(): ?string
    {
        return $this->paymentMethodType;
    }
    public function setPaymentMethodType(?string $type): static
    {
        $this->paymentMethodType = $type;
        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }
    public function setStripeSessionId(?string $id): static
    {
        $this->stripeSessionId = $id;
        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }
    public function setStripePaymentIntentId(?string $id): static
    {
        $this->stripePaymentIntentId = $id;
        return $this;
    }

    public function getStripePmBrand(): ?string
    {
        return $this->stripePmBrand;
    }
    public function setStripePmBrand(?string $brand): static
    {
        $this->stripePmBrand = $brand;
        return $this;
    }

    public function getStripePmLast4(): ?string
    {
        return $this->stripePmLast4;
    }
    public function setStripePmLast4(?string $last4): static
    {
        $this->stripePmLast4 = $last4;
        return $this;
    }

    /** Helpers */
    public function getGrandTotalEuro(): string
    {
        return number_format($this->grandTotal / 100, 2, ',', ' ') . ' €';
    }
}

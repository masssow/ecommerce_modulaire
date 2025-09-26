<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\{
    Cart,
    CartItem,
    ProductVariant,
    InventoryStock,
    Customer,
    Order
};
use App\Services\{CartManager, CheckoutService};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @covers \App\Services\CheckoutService */
class CheckoutServiceTest extends TestCase
{
    private CheckoutService $checkout;
    private CartManager     $cartManager;
    private Cart            $cart;

    protected function setUp(): void
    {
        /* 1. Mock léger d’EntityManager (aucune opération réelle BD) */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturn(null);
        $em->method('flush')->willReturn(null);

        /* 2. Instance réelle de CartManager (classe final) */
        $this->cartManager = new CartManager($em);

        /* 3. Service à tester */
        $this->checkout = new CheckoutService($em, $this->cartManager);

        /* 4. Panier pour chaque test */
        $this->cart = (new Cart())->setCustomer(new Customer());
    }

    /** Helper : ajoute une ligne au panier avec stock suffisant */
    private function addLine(float $price, int $qty): void
    {
        $variant = (new ProductVariant())
            ->setCurrentPrice($price)
            ->setSlug(uniqid());

        $stock = (new InventoryStock())->setQuantity($qty + 5);
        $variant->setInventoryStock($stock);

        $item = (new CartItem())
            ->setProductVariant($variant)
            ->setQuantity($qty)
            ->setUnitPrice($price)
            ->setCart($this->cart);

        $this->cart->addCartItem($item);
    }

    public function testCheckoutSuccessCreatesOrder(): void
    {
        $this->addLine(10.0, 2);                    // total 20

        $order = $this->checkout->checkout($this->cart);

        self::assertInstanceOf(Order::class, $order);
        self::assertSame(20.0, $order->getTotal());
        self::assertCount(1, $order->getOrderItems());
        self::assertNotNull($this->cart->getAbandonedAt());
    }

    public function testEmptyCartThrows(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->checkout->checkout($this->cart);
    }

    public function testInsufficientStockThrows(): void
    {
        $variant = (new ProductVariant())
            ->setCurrentPrice(5)
            ->setSlug('prd');
        $variant->setInventoryStock((new InventoryStock())->setQuantity(1)); // stock < qty

        $item = (new CartItem())
            ->setProductVariant($variant)
            ->setQuantity(2)
            ->setUnitPrice(5)
            ->setCart($this->cart);

        $this->cart->addCartItem($item);

        $this->expectException(BadRequestHttpException::class);
        $this->checkout->checkout($this->cart);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Entity\{Cart, CartItem, ProductVariant, Customer};
use App\Services\CartManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Services\CartManager
 */
class CartManagerTest extends TestCase
{
    private CartManager $manager;

    protected function setUp(): void
    {
        // mock EntityManager so we don’t touch the DB
        $em = $this->createMock(EntityManagerInterface::class);
        // ignore persist / flush calls
        $em->method('persist')->willReturn(null);
        $em->method('flush')->willReturn(null);

        $this->manager = new CartManager($em);
    }

    /** helper */
    private function variant(float $price): ProductVariant
    {
        $v = new ProductVariant();
        $v->setCurrentPrice($price);
        return $v;
    }

    public function testAddItemCreatesNewItem(): void
    {
        $cart = new Cart();
        $variant = $this->variant(10.0);

        $item = $this->manager->addItem($cart, $variant, 1);

        self::assertCount(1, $cart->getCartItems());
        self::assertSame($variant, $item->getProductVariant());
        self::assertSame(1, $item->getQuantity());
        self::assertSame(10.0, $item->getUnitPrice());
    }

    public function testAddItemIncrementsQuantity(): void
    {
        $cart = new Cart();
        $variant = $this->variant(15);

        $this->manager->addItem($cart, $variant, 1);
        $item = $this->manager->addItem($cart, $variant, 2);

        self::assertSame(3, $item->getQuantity());
        self::assertCount(1, $cart->getCartItems()); // toujours un seul CartItem
    }

    public function testUpdateQuantityToZeroRemovesItem(): void
    {
        $cart    = new Cart();
        $variant = $this->variant(5);

        $item = $this->manager->addItem($cart, $variant, 2);
        $this->manager->updateQuantity($item, 0);

        self::assertCount(0, $cart->getCartItems());
    }

    public function testRecalculateTotal(): void
    {
        $cart = new Cart();
        $v1   = $this->variant(2.5);
        $v2   = $this->variant(7.5);

        $this->manager->addItem($cart, $v1, 2); // 5.0
        $this->manager->addItem($cart, $v2, 1); // 7.5

        $this->manager->recalculate($cart);

        self::assertSame(12.5, $cart->getTotal());
    }
}

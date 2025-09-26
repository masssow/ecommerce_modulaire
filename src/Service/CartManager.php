<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Customer;
use App\Entity\ProductVariant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartManager
{
    private const SESSION_KEY = 'cart_id';
    private ?Cart $currentCart = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly Security $security
    ) {}

    /**
     * Retourne le panier courant (depuis session ou base).
     */
    public function getCurrentCart(): Cart
    {
        if ($this->currentCart !== null) {
            return $this->currentCart;
        }

        $session = $this->requestStack->getSession();
        $cartId = $session->get(self::SESSION_KEY);

        if ($cartId) {
            $cart = $this->em->getRepository(Cart::class)->find($cartId);
            if ($cart) {
                return $this->currentCart = $cart;
            }
        }

        $cart = new Cart();
        $cart->setCreatedAt(new \DateTimeImmutable());
        $cart->setTotal(0);

        $customer = $this->getCustomer();
        if ($customer) {
            $cart->setCustomer($customer);
        }

        $this->em->persist($cart);
        $this->em->flush();

        $session->set(self::SESSION_KEY, $cart->getId());

        return $this->currentCart = $cart;
    }

    /**
     * Ajoute un produit au panier (ou augmente la quantité).
     */
    public function add(ProductVariant $variant, int $qty = 1): Cart
    {
        $cart = $this->getCurrentCart();
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProductVariant()->getId() === $variant->getId()) {
                $item->setQuantity($item->getQuantity() + $qty);
                $this->em->persist($item);
                $this->recalculate($cart);
                return $cart;
            }
        }

        $item = new CartItem();
        $item->setCart($cart)
             ->setProductVariant($variant)
             ->setQuantity($qty)
            ->setUnitPrice($variant->getPriceAmount());
        $this->em->persist($item);
        $cart->addCartItem($item);
        $this->recalculate($cart);

        return $cart;
    }

    /**
     * Met à jour un item (ou le supprime si qty = 0).
     */
    public function updateItem(int $itemId, int $qty): Cart
    {
        $cart = $this->getCurrentCart();

        /** @var CartItem|null $item */
        $item = $this->em->getRepository(CartItem::class)->find($itemId);

        if (!$item) {
            throw new NotFoundHttpException('Item not found');
        }

        if ($item->getCart()->getId() !== $cart->getId()) {
            throw new AccessDeniedHttpException('Item does not belong to the current cart');
        }

        if ($qty <= 0) {
            $cart->removeCartItem($item);
            $this->em->remove($item);
        } else {
            $item->setQuantity($qty);
            $this->em->persist($item);
        }

        $this->recalculate($cart);

        return $cart;
    }

    /**
     * Supprime un article du panier.
     */
    public function remove(CartItem $item): void
    {
        $cart = $this->getCurrentCart();
        $cart->removeCartItem($item);
        $this->em->remove($item);
        $this->recalculate($cart);
    }

    /**
     * Vide complètement le panier.
     */
    public function clear(): void
    {
        $cart = $this->getCurrentCart();
        foreach ($cart->getCartItems() as $item) {
            $cart->removeCartItem($item);
            $this->em->remove($item);
        }

        $this->recalculate($cart);
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
        $this->currentCart = null;
    }

    /**
     * Persiste manuellement le panier (optionnel).
     */
    public function save(Cart $cart): void
    {
        $this->em->persist($cart);
        $this->em->flush();
    }

    /**
     * Recalcule le total du panier.
     */
    private function recalculate(Cart $cart): void
    {
        $total = 0;
        foreach ($cart->getCartItems() as $item) {
            $total += $item->getQuantity() * $item->getUnitPrice();
        }

        $cart->setTotal($total);
        $this->em->persist($cart);
        $this->em->flush();
    }

    /**
     * Retourne le Customer lié à l’utilisateur connecté.
     */
    private function getCustomer(): ?Customer
    {
        $user = $this->security->getUser();
        if (!$user) {
            return null;
        }

        return $this->em->getRepository(Customer::class)->findOneBy(['user' => $user]);
    }
}

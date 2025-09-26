<?php

namespace App\Controller;

use App\Entity\{Cart, CartItem, ProductVariant};
use App\Service\SettingService;
use App\Service\CartManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartManager $cartManager,
        private readonly SettingService $settings,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/mon-panier', name: 'cart_page', methods: ['GET'])]
    public function page(SettingService $settings): Response
    {
        $cart = $this->cartManager->getCurrentCart();
        $items = $cart->getCartItems()->toArray();
        $taxRate     = (float) $settings->getTva();                 // % TVA
        $shippingFee = (float) $settings->getShippingFee();         // Frais de port (en euros)
        $subTotal    = ((int) $cart->getTotal()) / 100;  // Montant HT
        $tvaAmount = $subTotal * ($taxRate / 100);  // Montant TVA
        $grandTotal = $subTotal + $tvaAmount + $shippingFee;
        
        $placeholder = '/images/placeholder_150x150.png';
        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'taxRate'     => $taxRate,
            'shippingFee' => $shippingFee,
            'subTotal'    => $subTotal,
            'tvaAmount'   => $tvaAmount,
            'grandTotal'  => $grandTotal,
            'cartItems' => array_map(
                fn(CartItem $i) => [
            'id'         => $i->getId(),
            'variant'    => $i->getProductVariant()->getId(),
            'name'       => $i->getProductVariant()->getProduct()->getName(),
            'image'      => $i->getProductVariant()->getImageName(),
            '/uploads/productVariant/' . $i->getProductVariant()->getImageName(),
            'qty'        => $i->getQuantity(),
            'unit_price_eur' => $i->getUnitPrice() /  100,
                ],
                $items
            )
        ]);
    }

    #[Route('/panier', name: 'cart_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $cart = $this->cartManager->getCurrentCart();
        return $this->json($this->serializeCart($cart));
    }

    #[Route('/panier', name: 'cart_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $payload   = $this->parsePayload($request);
        $variantId = (int) ($payload['variant'] ?? 0);
        $qty       = max(1, (int) ($payload['qty'] ?? 1));

        /** @var ProductVariant|null $variant */
        $variant = $this->em->find(ProductVariant::class, $variantId);
        if (!$variant) {
            return $this->json(['error' => 'Variant not found'], 404);
        }

        $cart = $this->cartManager->add($variant, $qty);
        $this->cartManager->save($cart);

        return $this->json($this->serializeCart($cart));
    }

    #[Route('/panier/item/{id}', name: 'cart_update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $payload = $this->parsePayload($request);
        $qty     = (int) ($payload['qty'] ?? 0);

        $cart = $this->cartManager->updateItem($id, $qty);
        $this->cartManager->save($cart);

        return $this->json($this->serializeCart($cart));
    }

    #[Route('/panier/panel', name: 'cart_panel', methods: ['GET'])]
    public function panel(): JsonResponse
    {
        $cart = $this->cartManager->getCurrentCart();
        $items = $cart->getCartItems()->toArray();

        $html = $this->renderView('cart/_cart_panel.html.twig', [
            'cartItems' => array_map(
                fn(CartItem $i) => [
                    'id'         => $i->getId(),
                    'variant'    => $i->getProductVariant()->getId(),
                    'name'       => $i->getProductVariant()->getProduct()->getName(),
                    'imageName'  => $i->getProductVariant()->getImageName(),
                    'qty'        => $i->getQuantity(),
                    'unit_price_eur' => $i->getUnitPrice() /  100,
                ],
                $items
            ),
        ]);

        return $this->json(['html' => $html]);
    }

    // ------------------ Helpers ------------------

    private function parsePayload(Request $request): array
    {
        if ('json' === $request->getContentTypeFormat()) {
            return json_decode($request->getContent(), true) ?? [];
        }

        return $request->request->all();
    }

    private function serializeCart(Cart $cart): array
    {
        $items       = $cart->getCartItems()->toArray();
        $placeholder = '/images/placeholder_150x150.png';

        return [
            'total'     => $cart->getTotal(),
            'total_qty' => array_sum(array_map(fn(CartItem $i) => $i->getQuantity(), $items)),
            'items'     => array_map(
                static fn(CartItem $i) => [
                    'id'         => $i->getId(),
                    'variant'    => $i->getProductVariant()->getId(),
                    'name'       => $i->getProductVariant()->getProduct()->getName(),
                    'image'      => $i->getProductVariant()->getImageName()
                        ? '/uploads/productVariant/' . $i->getProductVariant()->getImageName()
                        : $placeholder,
                    'qty'        => $i->getQuantity(),
                    'unit_price_eur' => $i->getUnitPrice() /  100,
                ],
                $items
            ),
        ];
    }
}

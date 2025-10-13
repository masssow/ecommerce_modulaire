<?php

namespace App\Controller;

use App\Repository\ProductVariantRepository;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProductController extends AbstractController
{
    #[Route('/product/{id}', name: 'product_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        ProductVariantRepository $variantRepo,
        ProductRepository $productRepo,
        int $id
    ): Response {
        // 1) Tenter comme "variant id"
        $variant = $variantRepo->createQueryBuilder('v')
            ->leftJoin('v.product', 'p')->addSelect('p')
            ->where('v.id = :id')->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult();

        $product = $variant?->getProduct();

        // 2) Si pas de variant => essayer comme "product id"
        if (!$variant) {
            $product = $productRepo->createQueryBuilder('p')
                ->leftJoin('p.productVariants', 'v')->addSelect('v')
                ->where('p.id = :pid')->setParameter('pid', $id)
                ->getQuery()->getOneOrNullResult();

            if (!$product) {
                throw $this->createNotFoundException('Variante introuvable.');
            }

            // Prendre la 1ʳᵉ variante disponible du produit
            $variant = $product->getProductVariants()->first() ?: null;
            if (!$variant) {
                throw $this->createNotFoundException('Aucune variante disponible pour ce produit.');
            }
        }

        if (!$product) {
            $product = $variant->getProduct();
            if (!$product) {
                throw $this->createNotFoundException('Produit lié introuvable.');
            }
        }

        return $this->render('product/show.html.twig', [
            'variant'        => $variant,
            'product'        => $product,
            'currentVariant' => $variant,
            'variants'       => $product->getProductVariants(),
            'availableSizes' => ['XS', 'S', 'M', 'L', 'XL'],   // optionnel / mock
            'availableColors' => ['A', 'B', 'C'],             // optionnel / mock
        ]);
    }
}

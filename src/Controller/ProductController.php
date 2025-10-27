<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\FavoriteRepository;
use App\Repository\ProductVariantRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProductController extends AbstractController
{
    #[Route('/product/{id}', name: 'product_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        ProductVariantRepository $variantRepo,
        ProductRepository $productRepo,
        int $id,
        FavoriteRepository $favorites, UserRepository $userR
    ): Response {
        // 1) Tenter comme "variant id"
        $variant = $variantRepo->findOneWithProduct($id);

        $product = $variant?->getProduct();

        // 2) Si pas de variant => essayer comme "product id"
        if (!$variant) {
            $product = $productRepo->findOneWithVariants($id);
            if (!$product) {
                throw $this->createNotFoundException('Variante ou produit introuvable.');
            }
            $variant = $product->getProductVariants()->first() ?: null;
            if (!$variant) {
                throw $this->createNotFoundException('Aucune variante disponible pour ce produit.');
            }
        }

        $userRepo = $this->getUser();

        $favList = $favorites->findForUserWithJoins($userRepo);
        $favIds  = $favorites->getFavoriteVariantIdsForUser($userRepo);

        return $this->render('product/show.html.twig', [
            'variant'        => $variant,
            'product'        =>  $product ?? $variant->getProduct(),
            'currentVariant' => $variant,
            'variants'       => $product->getProductVariants(),
            'favorites'    => $favList,  // pour la section "Mes favoris"
            'favoritesIds' => $favIds,
            'availableSizes' => ['XS', 'S', 'M', 'L', 'XL'],   // optionnel / mock
            'availableColors' => ['A', 'B', 'C'],             // optionnel / mock
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\FavoriteRepository;
use App\Repository\ProductVariantRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProductController extends AbstractController
{
    /**
     * Affiche la fiche produit à partir du couple :
     *   /p/{productSlug}/{variantSlug}
     */
    #[Route(
        '/p/{productSlug}/{variantSlug}',
        name: 'product_show',
        methods: ['GET'],
        requirements: [
            'productSlug' => '[a-z0-9\-]+',
            'variantSlug' => '[a-z0-9\-]+',
        ]
    )]
    public function showVariant(
        ProductRepository        $productRepo,
        ProductVariantRepository $variantRepo,
        FavoriteRepository       $favorites,
        string                   $productSlug,
        string                   $variantSlug
    ): Response {
        // 1) Récupérer le produit + ses variantes
        $product = $productRepo->findOneWithVariantsBySlug($productSlug);

        if (!$product) {
            throw $this->createNotFoundException("Produit introuvable.");
        }

        // 2) Récupérer la variante correspondant au slug et liée à ce produit
        $variant = $variantRepo->findOneBy([
            'slug'    => $variantSlug,
            'product' => $product,
        ]);

        if (!$variant) {
            throw $this->createNotFoundException("Variante introuvable.");
        }

        // 3) Favoris utilisateur (si connecté)
        $user       = $this->getUser();
        $favoritesIds = $favorites->getFavoriteVariantIdsForUser($user);

        // 4) Produits liés (par exemple même sous-catégorie, exclure la variante courante)
        //    -> à adapter selon tes méthodes de repo existantes
        $related = $variantRepo->findRelatedForVariant($variant, 8);

        $relatedVm = array_map(function (ProductVariant $v) {
            $p = $v->getProduct();

            return [
                'id'            => $v->getId(),
                'name'          => $p ? $p->getName() : 'Produit',
                'priceAmount'   => $v->getPriceAmount(),
                'priceCurrency' => $v->getPriceCurrency() ?? 'EUR',
                'imageUrl'      => $v->getImageName()
                    ? '/uploads/productVariant/' . $v->getImageName()
                    : '/images/placeholder-image.png',
                'productSlug'   => $p?->getSlug(),
                'variantSlug'   => $v->getSlug() ?? (string) $v->getId(),
            ];
        }, $related);

        return $this->render('product/show.html.twig', [
            'product'         => $product,
            'currentVariant'  => $variant,
            'variants'        => $product->getProductVariants(),
            'favoritesIds'    => $favoritesIds,
            'relatedProducts' => $relatedVm,
        ]);
    }

    /**
     * Catalogue / boutique : liste paginée de ProductVariant.
     */
    #[Route('/boutique', name: 'product_index')]
    public function index(
        Request                 $request,
        CategoryRepository      $categories,
        ProductVariantRepository $variants,
        PaginatorInterface      $paginator
    ): Response {
        $page       = $request->query->getInt('page', 1);
        $searchTerm = trim((string) $request->query->get('q', ''));
        $categoryId = $request->query->get('category'); // ici on l’utilise comme sous-catégorie

        // QueryBuilder centralisée dans le repository
        $qb = $variants->createCatalogQb($searchTerm, $categoryId);

        // Pagination KNP : 5 variantes par page
        $productVariantsPagination = $paginator->paginate(
            $qb,
            $page,
            5
        );

        return $this->render('product/index.html.twig', [
            'productVariants'  => $productVariantsPagination,
            'categories'       => $categories->findAll(),
            'current_q'        => $searchTerm,
            'current_category' => $categoryId,
            // 'favoritesIds'   => ... (à brancher plus tard si besoin)
        ]);
    }
}

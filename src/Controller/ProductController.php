<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Repository\UserRepository;
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
    #[Route('/product/{id}', name: 'product_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        ProductVariantRepository $variantRepo,
        ProductRepository $productRepo,
        int $id,
        FavoriteRepository $favorites,
        UserRepository $userR
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
            /** @var ProductVariant|null $first */
            $first = $product->getProductVariants()->first() ?: null;
            if (!$first) {
                throw $this->createNotFoundException('Aucune variante disponible pour ce produit.');
            }
            $variant = $first;
        }

        $user = $this->getUser();

        $favList = $favorites->findForUserWithJoins($user);
        $favIds  = $favorites->getFavoriteVariantIdsForUser($user);
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
            ];
        }, $related);

        return $this->render('product/show.html.twig', [
            'variant'          => $variant,
            'product'          => $product ?? $variant->getProduct(),
            'currentVariant'   => $variant,
            'variants'         => $product->getProductVariants(),
            'favorites'        => $favList,
            'relatedProducts'  => $relatedVm,
            'favoritesIds'     => $favIds,
            'availableSizes'   => ['XS', 'S', 'M', 'L', 'XL'],   // mock / optionnel
            'availableColors'  => ['A', 'B', 'C'],               // mock / optionnel
        ]);
    }

    #[Route('/boutique', name: 'product_index')]
    public function index(
        Request $request,
        CategoryRepository $categories,
        ProductVariantRepository $variants,
        PaginatorInterface $paginator
    ): Response {
        $page          = $request->query->getInt('page', 1);
        $searchTerm    = trim((string) $request->query->get('q', ''));
        $categoryId    = $request->query->get('category'); // ici on l’utilise comme sous-catégorie

        // QueryBuilder centralisé dans le repository
        $qb = $variants->createCatalogQb($searchTerm, $categoryId);

        // Pagination KNP : 10 variantes par page
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
            // 'favoritesIds'   => ...
        ]);
    }
}

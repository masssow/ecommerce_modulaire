<?php

namespace App\Controller;

use App\Entity\SubCategory;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SubCategoryController extends AbstractController
{
    #[Route('/boutique/sous-categorie/{id}', name: 'sub_category_show', requirements: ['id' => '\d+'])]
    public function show(SubCategory $subCategory, ProductRepository $productRepository): Response
    {
        // 1) Produits de la sous-catégorie
        $products = $productRepository->findBySubCategory($subCategory);

        // 2) Garde-fou : si aucun produit sur la sous-catégorie => on affiche ceux de la catégorie
        $fallback = false;
        if (count($products) === 0 && $subCategory->getCategorie()) {
            $products = $productRepository->findByParentCategory($subCategory->getCategorie());
            $fallback = true;
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'subCategory' => $subCategory,
            'category' => $subCategory->getCategorie(),
            'fallbackToCategory' => $fallback,
        ]);
    }
}

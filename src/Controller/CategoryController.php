<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category')]
    public function index(CategoryRepository $categoryRepo): Response
    {
        $categories = $categoryRepo->findBy([], ['name' => 'ASC']);

        return $this->render('category/index.html.twig', [
            'categories' => $categories,

        ]);
    }

    #[Route('/category/{id}', name: 'category_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Category $category, ProductVariantRepository $variantRepo): Response
    {
        // Récupérer les variantes de produits de cette catégorie
        $variants = $variantRepo->findByCategory($category);

        return $this->render('category/show.html.twig', [
            'category'       => $category,
            'productVariants' => $variants,
            // passe aussi un tableau d'IDs de favoris si tu l’as déjà (optionnel)
            // 'favoritesIds' => $favoriteRepo->findVariantIdsForUser($this->getUser()),
        ]);
    }
}

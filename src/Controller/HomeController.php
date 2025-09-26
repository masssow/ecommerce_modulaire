<?php

namespace App\Controller;

use App\Entity\SubCategory;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(CategoryRepository $categoryRepository, ProductVariantRepository $productVariantRepository, SubCategoryRepository $subCategoryRepository): Response
    {
        $categories = $categoryRepository->findBy([], null, 4);
        $productVariants = $productVariantRepository->findBy([], null, 16);
        $subCategories = $subCategoryRepository->findBy([], null, 4);
        return $this->render('home/index.html.twig', [
            'categories' => $categories,
            'productVariants' => $productVariants,
            'subCategories' => $subCategories,
        ]);
    }
}

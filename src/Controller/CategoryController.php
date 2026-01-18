<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;


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
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
            'subCategories' => $category->getSubCategories(),
        ]);
    }

    #[Route('/category/{id}/products', name: 'category_products', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function products(
        Category $category,
        ProductVariantRepository $variantRepo,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $page = $request->query->getInt('page', 1);
        $search = (string) $request->query->get('q', '');
        $subId  = $request->query->getInt('sub', 0);

        $qb = $variantRepo->createByCategoryQb($category, $search);

        $productVariantsPagination = $paginator->paginate($qb, $page, 10);

        return $this->render('category/products.html.twig', [
            'category'        => $category,
            'productVariants' => $productVariantsPagination,
            'current_q'       => $search,
            'current_sub'     => $subId,
        ]);
    }
}

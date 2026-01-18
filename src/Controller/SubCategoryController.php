<?php

namespace App\Controller;

use App\Entity\SubCategory;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

final class SubCategoryController extends AbstractController
{

   #[Route('/boutique/sous-categorie/{id}', name: 'sub_category_show', requirements: ['id' => '\d+'])]
public function show(
    SubCategory $subCategory,
    Request $request,
    ProductRepository $productRepository,
    PaginatorInterface $paginator
): Response {
    $query = $productRepository->qbBySubCategory($subCategory); // QueryBuilder

    $pagination = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        12
    );

    $fallback = false;

    if ($pagination->getTotalItemCount() === 0 && $subCategory->getCategorie()) {
        $fallback = true;

        $query = $productRepository->qbByParentCategory($subCategory->getCategorie());

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            12
        );
        }

        return $this->render('product/index.html.twig', [
            'pagination' => $pagination,
            'subCategory' => $subCategory,
            'category' => $subCategory->getCategorie(),
            'fallbackToCategory' => $fallback,
        ]);
}

}
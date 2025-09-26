<?php


namespace App\Controller;

use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Route('/product/{id}', name: 'product_show')]
    public function show(ProductVariantRepository $productVariantRepository, int $id): Response
    {

        $productVariant = $productVariantRepository->createQueryBuilder('v')
            ->leftJoin('v.product', 'p')
            ->addSelect('p')
            ->where('v.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
        
        // $productVariant = $productVariantRepository->find($id);

        // dd($productVariant);

        // if (!$productVariant) {
        //     throw $this->createNotFoundException('Variante introuvable.');
        // }

        // $product = $productVariant->getProduct();
        // if (!$product instanceof \App\Entity\Product) {
        //     dump($productVariant, $product);
        //     throw $this->createNotFoundException('Produit lié manquant ou non chargé.');
        // }



        // $product = $productVariant->getProduct();



        return $this->render('product/show.html.twig', [
            'variant' => $productVariant,
            'product' => $productVariant,
        ]);
        
    }

    // #[Route('/product/{slug}/reviews', name: 'product_reviews')]
    // public function reviews(Product $product, Request $request, ReviewRepository $reviewRepository): Response
    // {
    //     $page = $request->query->getInt('page', 2);
    //     $limit = 5;
    //     $offset = ($page - 1) * $limit;

    //     $reviews = $reviewRepository->findBy(
    //         ['product' => $product],
    //         ['createdAt' => 'DESC'],
    //         $limit,
    //         $offset
    //     );

    //     return $this->render('product/_reviews.html.twig', [
    //         'reviews' => $reviews,
    //     ]);
    // }
}
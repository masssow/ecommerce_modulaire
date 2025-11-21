<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(): Response
    {
        return $this->render('search/index.html.twig', [
            'controller_name' => 'SearchController',
        ]);
    }


    #[Route('/search/suggest', name: 'product_search_suggest')]
    public function suggest(Request $request, ProductRepository $products): JsonResponse
    {
        $q = (string) $request->query->get('q', '');
        $categoryId = $request->query->get('category'); // optionnel

        // On évite d’exploser le serveur si l’utilisateur tape 1 lettre
        if (mb_strlen(trim($q)) < 4) {
            return $this->json(['items' => []]);
        }

        $rows = $products->suggestByName($q, $categoryId, 8);

        $items = array_map(function (array $row): array {
            return [
                'id'   => $row['id'],
                'name' => $row['name'],
                'url'  => $this->generateUrl('product_show', ['id' => $row['id']]),
            ];
        }, $rows);

        return $this->json([
            'items' => $items,
        ]);
    }
}

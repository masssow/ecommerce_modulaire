<?php
// src/Controller/FavoriteController.php
namespace App\Controller;

use App\Entity\Favorite;
use App\Entity\ProductVariant;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface as EM;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class FavoriteController extends AbstractController
{
    private function wantsJson(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept'), 'application/json');
    }

    /**
     * Page liste des favoris (HTML) + JSON si XHR/Accept: application/json
     */
    #[Route('/account/favorites', name: 'favorite_index', methods: ['GET'])]
    public function index(FavoriteRepository $repo, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();
        $favorites = $repo->findForUserWithJoins($user);

        if ($this->wantsJson($request)) {
            $items = array_map(function (Favorite $f) {
                $v = $f->getProductVariant();
                $p = $v ? $v->getProduct() : null;

                return [
                    'id' => $f->getId(),
                    // décommente si tu ajoutes un getter dans l'entité Favorite
                    // 'createdAt' => method_exists($f, 'getCreatedAt') && $f->getCreatedAt() ? $f->getCreatedAt()->format(DATE_ATOM) : null,
                    'variant' => $v ? [
                        'id'            => $v->getId(),
                        // Le variant n'a pas forcément "getName()", on renvoie un "label" basé sur le produit
                        'label'         => $p ? $p->getName() : null,
                        'priceAmount'   => method_exists($v, 'getPriceAmount') ? $v->getPriceAmount() : null,
                        'priceCurrency' => method_exists($v, 'getPriceCurrency') ? $v->getPriceCurrency() : null,
                        'image'         => (method_exists($v, 'getImageName') && $v->getImageName())
                            ? '/uploads/productVariant/' . $v->getImageName()
                            : null,
                        'product'       => $p ? [
                            'id'   => $p->getId(),
                            'name' => method_exists($p, 'getName') ? $p->getName() : null,
                            'slug' => method_exists($p, 'getSlug') ? $p->getSlug() : null,
                        ] : null,
                    ] : null,
                ];
            }, $favorites);

            return new JsonResponse([
                'ok'    => true,
                'count' => $repo->countForUser($user),
                'items' => $items,
            ]);
        }

        return $this->render('account/favorites/index.html.twig', [
            'favorites' => $favorites,
        ]);
    }

    /**
     * End-point léger pour récupérer uniquement la liste des IDs de variants favoris,
     * pratique pour initialiser les cœurs en JS si tu n’injectes pas favoritesIds côté serveur.
     */
    #[Route('/favorite/ids', name: 'favorite_ids', methods: ['GET'])]
    public function ids(FavoriteRepository $repo): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $user = $this->getUser();

        return new JsonResponse([
            'ok'  => true,
            'ids' => $repo->idsForUser($user), // int[]
            'count' => $repo->countForUser($user),
        ]);
    }

    /**
     * Ajoute/retire un favori (toujours JSON)
     */
    #[Route('/favorite/toggle/{id}', name: 'favorite_toggle', methods: ['POST'])]
    public function toggle(ProductVariant $variant, FavoriteRepository $repo, Request $request, EM $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $submitted = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('fav' . $variant->getId(), $submitted)) {
            return new JsonResponse(['ok' => false, 'error' => 'csrf'], 400);
        }

        $user = $this->getUser();
        $fav  = $repo->findOneBy(['user' => $user, 'productVariant' => $variant]);

        $active = false;
        $favoriteId = null;

        if ($fav) {
            $favoriteId = $fav->getId();
            $em->remove($fav);
            $active = false;
        } else {
            $fav = (new Favorite())
                ->setUser($user)
                ->setProductVariant($variant);
            $em->persist($fav);
            $active = true;
        }
        $em->flush();

        if (!$favoriteId) {
            $favoriteId = $fav->getId();
        }

        return new JsonResponse([
            'ok'         => true,
            'active'     => $active,
            'favoriteId' => $favoriteId,
            'variantId'  => $variant->getId(),
            'count'      => $repo->countForUser($user),
        ]);
    }

    /**
     * Supprimer un favori (JSON si XHR, sinon redirect+flash)
     */
    #[Route('/favorite/remove/{id}', name: 'favorite_remove', methods: ['POST'])]
    public function remove(Favorite $favorite, Request $request, FavoriteRepository $repo, EM $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        if ($favorite->getUser() !== $this->getUser()) {
            return new JsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
        }

        if (!$this->isCsrfTokenValid('fav_remove_' . $favorite->getId(), $request->request->get('_token'))) {
            return new JsonResponse(['ok' => false, 'error' => 'csrf'], 400);
        }

        $removedId = $favorite->getId();
        $em->remove($favorite);
        $em->flush();

        if ($this->wantsJson($request)) {
            return new JsonResponse([
                'ok'        => true,
                'removedId' => $removedId,
                'count'     => $repo->countForUser($this->getUser()),
            ]);
        }

        $this->addFlash('success', 'Article retiré des favoris.');
        return $this->redirectToRoute('favorite_index');
    }
}

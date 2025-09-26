<?php
// src/Controller/AccountController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'account_profile', methods: ['GET'])]
    public function profile(): Response
    {
        // Exige une session/auth
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->getUser();

        // ✅ Rendre une vue HTML (et non JSON)
        return $this->render('account/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/account/orders', name: 'account_orders', methods: ['GET'])]
    public function orders(OrderRepository $orders): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var User $user */
        $user = $this->getUser();

        // Récupère les commandes du client connecté (Order -> Customer -> User)
        $list = $orders->createQueryBuilder('o')
            ->innerJoin('o.customer', 'c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('account/orders.html.twig', [
            'orders' => $list,
        ]);
    }
}

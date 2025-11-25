<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReturnController extends AbstractController
{
    #[Route('/retour', name: 'app_return')]
    public function index(): Response
    {
        return $this->render('return/index.html.twig', [
            'controller_name' => 'ReturnController',
        ]);
    }
}

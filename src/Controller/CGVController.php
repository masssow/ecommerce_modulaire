<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CGVController extends AbstractController
{
    #[Route('/conditions-generales-de-vente', name: 'app_cgv')]
    public function index(): Response
    {
        return $this->render('cgv/index.html.twig', [
            'controller_name' => 'CGVController',
        ]);
    }
}

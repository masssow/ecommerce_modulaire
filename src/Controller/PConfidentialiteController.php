<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PConfidentialiteController extends AbstractController
{
    #[Route('/p-confidentialite', name: 'app_pconfidentialite')]
    public function index(): Response
    {
        return $this->render('p_confidentialite/index.html.twig', [
            'controller_name' => 'PConfidentialiteController',
        ]);
    }
}

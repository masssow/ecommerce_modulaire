<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MentionsLegalesController extends AbstractController
{
    #[Route('/politique-de-confidentialite', name: 'app_pconfidentialite')]
    public function confident(): Response
    {
        return $this->render('mentions-legales/confidentialite.html.twig', [
            'controller_name' => 'PConfidentialiteController',
        ]);
    }

    #[Route('/cookies', name: 'app_cookies')]
    public function cookies(): Response
    {
        return $this->render('mentions-legales/cookies.html.twig', [
            'controller_name' => 'CookiesController',
        ]);
    }

    #[Route('/conditions-generales-de-vente', name: 'app_cgv')]
    public function cgv(): Response
    {
        return $this->render('mentions-legales/cgv.html.twig', [
            'controller_name' => 'CGVController',
        ]);
    }

    #[Route('/conditions-generales-d-utilisation', name: 'app_cgu')]
    public function cgu(): Response
    {
        return $this->render('mentions-legales/cgu.html.twig', [
            'controller_name' => 'CGUController',
        ]);
    }
}

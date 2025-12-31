<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PagesLegalesController extends AbstractController
{
    #[Route('/Conditions-generales-d-usage', name: 'app_cgu')]
    public function cgu(): Response
    {
        return $this->render('pages_legales/cgu.html.twig', [
        ]);
    }

    #[Route('/conditions-generales-d-vente', name: 'app_cgv')]
    public function cgv(): Response
    {
        return $this->render('pages_legales/cgv.html.twig', [
            
        ]);
    }

    #[Route('/politique-des-cookies-et-confidentialite', name: 'app_cookies_policy')]
    public function cookies(): Response
    {
        return $this->render('pages_legales/cookies.html.twig', []);
    }

    #[Route('/politique-retours-et-remboursements', name: 'app_returns')]
    public function returns(): Response
    {
        return $this->render('pages_legales/returns.html.twig', []);
    }

}

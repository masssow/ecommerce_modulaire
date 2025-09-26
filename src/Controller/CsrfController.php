<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final class CsrfController
{
    #[Route('/csrf-token', name: 'app_csrf_token')]
    public function __invoke(TokenGeneratorInterface $csrf): JsonResponse
    {
        return new JsonResponse(['token' => $csrf->generateToken('authenticate')]);
    }    
    
}

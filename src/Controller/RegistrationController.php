<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\UserRegistrationService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/register', name: 'app_register_')]
final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly UserRegistrationService $registration, // ✅ service
    ) {}

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher, // (plus utilisé ici, on peut le retirer)
        Security $security
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('account_profile');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Honeypot: si rempli, on ignore la soumission
            if ($form->has('hp') && $form->get('hp')->getData()) {
                $this->addFlash('success', 'Merci ! Vérifiez votre e-mail pour finaliser la création du compte.');
                return $this->redirectToRoute('homepage');
            }

            // Normaliser l’email AVANT validation (on touche uniquement l’entité)
            if ($form->has('email') && null !== $form->get('email')->getData()) {
                $user->setEmail(strtolower(trim((string) $form->get('email')->getData())));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Données adresse depuis le form (un seul champ address)
            $addressData = [
                'address'    => (string) $form->get('addr_address')->getData(),
                'postalCode' => (string) $form->get('addr_postalCode')->getData(),
                'city'       => (string) $form->get('addr_city')->getData(),
                'country'    => (string) $form->get('addr_country')->getData(),
                'phone'      => $form->get('addr_phone')->getData(),
            ];

            // Déléguons toute la logique d’inscription au service
            $plain = (string) $form->get('plainPassword')->getData();
            $this->registration->register($user, $plain, $addressData, createCustomer: true);

            // Auto-login & redirect
            try {
                $security->login($user);
                $this->addFlash('success', 'Bienvenue ! Votre compte a été créé.');
                return $this->redirectToRoute('account_profile');
            } catch (\Throwable) {
                $this->addFlash('success', 'Compte créé. Veuillez vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}

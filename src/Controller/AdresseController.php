<?php

namespace App\Controller;

use App\Entity\Adresse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/adresse', name: 'app_adresse_')]
final class AdresseController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Création d'une adresse depuis le checkout (ou ailleurs).
     * Attend au minimum: line1, city. 
     * Redirige vers la page checkout avec un flash.
     */
    #[Route('/adresse/add', name: 'add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Veuillez vous connecter pour ajouter une adresse.');
            return $this->redirectToRoute('app_login');
        }

        // CSRF
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('add_address', $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_checkout_index');
        }

        // Champs
        $context    = (string) $request->request->get('context', 'shipping'); // 'shipping' | 'billing'
        $line1      = trim((string) $request->request->get('line1', ''));
        $city       = trim((string) $request->request->get('city', ''));
        $line2      = $request->request->get('line2');
        $postalCode = $request->request->get('postalCode');
        $country    = $request->request->get('country');
        $phone      = $request->request->get('phone');

        if ($line1 === '' || $city === '') {
            $this->addFlash('danger', 'Veuillez renseigner au minimum l’adresse (ligne 1) et la ville.');
            return $this->redirectToRoute('app_checkout_index');
        }

        // Création
        $addr = new Adresse();
        $addr->setLine1($line1);
        $addr->setLine2($line2);
        $addr->setPostalCode($postalCode);
        $addr->setCity($city);
        $addr->setCountry($country);
        $addr->setPhone($phone);

        // Rattachement: si Adresse possède setUser (d’après ton projet)
        if (method_exists($addr, 'setUser')) {
            $addr->setUser($user);
        }
        // Si dans TON modèle l’adresse est rattachée au Customer (setCustomer),
        // dé-commente et adapte ce bloc :
        // $customer = $this->em->getRepository(\App\Entity\Customer::class)->findOneBy(['user' => $user]);
        // if ($customer && method_exists($addr, 'setCustomer')) {
        //     $addr->setCustomer($customer);
        // }

        $this->em->persist($addr);
        $this->em->flush();

        $this->addFlash('success', 'Adresse ajoutée.');

        // Redirection vers le checkout (tu peux garder/retirer le fragment)
        return $this->redirectToRoute('app_checkout_index', [
            '_fragment' => $context === 'billing' ? 'billing-choice' : null,
        ]);
    }

    /**
     * Suppression d'une adresse appartenant à l'utilisateur connecté.
     * A appeler via un <form method="post"> avec CSRF.
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Adresse $adresse, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Veuillez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        // CSRF
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('delete_address_' . $adresse->getId(), $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_checkout_index');
        }

        // Contrôle d'appartenance simple : via user (ou customer si c’est ton modèle)
        if (method_exists($adresse, 'getUser')) {
            if ($adresse->getUser() !== $user) {
                throw $this->createAccessDeniedException();
            }
        } else {
            // Variante si tu relies via Customer :
            // $customer = $this->em->getRepository(\App\Entity\Customer::class)->findOneBy(['user' => $user]);
            // if (!$customer || $adresse->getCustomer() !== $customer) {
            //     throw $this->createAccessDeniedException();
            // }
        }

        $this->em->remove($adresse);
        $this->em->flush();

        $this->addFlash('success', 'Adresse supprimée.');
        return $this->redirectToRoute('app_checkout_index');
    }
}

<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Adresse;
use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserRegistrationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    /**
     * @param User  $user           Entité déjà hydratée depuis le Form (email/prénom/nom)
     * @param string $plainPassword Mot de passe en clair depuis le Form
     * @param array $addressData    ['address','postalCode','city','country','phone' (optional)]
     * @param bool  $createCustomer Créer aussi l'entité Customer liée au User
     *
     * @throws \Throwable  En cas d'erreur Doctrine, la transaction est rollback
     */
    public function register(
        User $user,
        string $plainPassword,
        array $addressData,
        bool $createCustomer = true
    ): User {
        // 1) Normaliser l’e-mail (défense en profondeur, en plus du contrôleur/FormEvents)
        $user->setEmail(strtolower(trim((string) $user->getEmail())));

        // 2) Hash du mot de passe
        $user->setPassword(
            $this->hasher->hashPassword($user, $plainPassword)
        );

        // 3) Rôle par défaut (optionnel si getRoles() ajoute déjà ROLE_USER)
        $user->setRoles(['ROLE_USER']);

        // 4) Construction de l’adresse liée à l’utilisateur
        $addr = (new Adresse())
            ->setLine1(trim((string) ($addressData['address'] ?? '')))
            ->setLine2(null)
            ->setPostalCode(trim((string) ($addressData['postalCode'] ?? '')))
            ->setCity(trim((string) ($addressData['city'] ?? '')))
            ->setCountry(trim((string) ($addressData['country'] ?? '')))
            ->setPhone(
                isset($addressData['phone']) && $addressData['phone'] !== ''
                    ? trim((string) $addressData['phone'])
                    : null
            );

        if (method_exists($addr, 'setUser')) {
            $addr->setUser($user);
        }

        // 5) Customer si attendu par ton modèle
        $customer = null;
        if ($createCustomer) {
            $customer = new Customer();
            if (method_exists($customer, 'setUser')) {
                $customer->setUser($user);
            }
        }

        // 6) Transaction pour atomiser la création
        $this->em->beginTransaction();
        try {
            $this->em->persist($user);
            if ($customer) {
                $this->em->persist($customer);
            }
            $this->em->persist($addr);

            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }

        return $user;
    }
}

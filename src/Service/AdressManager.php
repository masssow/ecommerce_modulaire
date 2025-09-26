<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Adresse;
use Doctrine\ORM\EntityManagerInterface;

class AdressManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addAdresse(User $user, array $data): Adresse
    {
        $adresse = new Adresse();
        $adresse->setUser($user);

        $this->hydrateAdresse($adresse, $data);

        $this->entityManager->persist($adresse);
        $this->entityManager->flush();

        return $adresse;
    }

    public function updateAdresse(User $user, Adresse $adresse, array $data): Adresse
    {
        if ($adresse->getUser() !== $user) {
            throw new \Exception("Unauthorized: cette adresse n'appartient pas à cet utilisateur.");
        }

        $this->hydrateAdresse($adresse, $data);

        $this->entityManager->persist($adresse);
        $this->entityManager->flush();

        return $adresse;
    }

    public function deleteAdresse(User $user, Adresse $adresse): void
    {
        if ($adresse->getUser() !== $user) {
            throw new \Exception("Unauthorized: cette adresse n'appartient pas à cet utilisateur.");
        }

        $this->entityManager->remove($adresse);
        $this->entityManager->flush();
    }

    private function hydrateAdresse(Adresse $adresse, array $data): void
    {
        if (isset($data['label'])) {
            $adresse->setLabel($data['label']);
        }

        if (isset($data['line1'])) {
            $adresse->setLine1($data['line1']);
        }

        if (isset($data['line2'])) {
            $adresse->setLine2($data['line2']);
        }

        if (isset($data['postalCode'])) {
            $adresse->setPostalCode($data['postalCode']);
        }

        if (isset($data['city'])) {
            $adresse->setCity($data['city']);
        }

        if (isset($data['country'])) {
            $adresse->setCountry($data['country']);
        }

        if (isset($data['phone'])) {
            $adresse->setPhone($data['phone']);
        }

        if (isset($data['type'])) {
            $adresse->setType($data['type']); // billing / shipping
        }

        if (isset($data['isDefault'])) {
            $adresse->setIsDefault($data['isDefault']);

            if ($data['isDefault']) {
                $this->unsetOtherDefaultAdresses($adresse);
            }
        }
    }

    private function unsetOtherDefaultAdresses(Adresse $currentAdresse): void
    {
        $user = $currentAdresse->getUser();
        $type = $currentAdresse->getType();

        $repo = $this->entityManager->getRepository(Adresse::class);
        $otherAdresses = $repo->findBy([
            'user' => $user,
            'type' => $type,
            'isDefault' => true,
        ]);

        foreach ($otherAdresses as $adresse) {
            if ($adresse !== $currentAdresse) {
                $adresse->setIsDefault(false);
                $this->entityManager->persist($adresse);
            }
        }
    }
}

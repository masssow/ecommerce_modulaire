<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Adresse;
use App\Services\AdressManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Processor exécuté sur POST /api/mon-compte/adresses
 * (ou /api/me/addresses … selon l'uriTemplate choisi).
 *
 * 1. Associe automatiquement l'adresse au user connecté.
 * 2. Garantit l'unicité de l'adresse par défaut (isDefault).
 * 3. Persiste et retourne l'entité.
 */
final class AdressePostProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security               $security,
        private readonly AdressManager          $adressManager, // réutilise ta logique métier
    ) {}

    /**
     * @param Adresse $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Adresse
    {
        // Récupère l'utilisateur courant
        $user = $this->security->getUser();
        if (!$user) {
            // Normalement impossible car l'opération est sécurisée ROLE_USER
            throw new \LogicException('Utilisateur non authentifié.');
        }

        // Utilise AdressManager pour bénéficier de la logique (isDefault, etc.)
        $adresse = $this->adressManager->addAdresse($user, [
            'label'       => $data->getLabel(),
            'line1'       => $data->getLine1(),
            'line2'       => $data->getLine2(),
            'city'        => $data->getCity(),
            'postalCode'  => $data->getPostalCode(),
            'country'     => $data->getCountry(),
            'isDefault'   => $data->isDefault(),
            // si tu as un champ 'type':
            // 'type'        => $data->getType(),
        ]);

        // L'AdressManager flush déjà, mais on garde un flush de sûreté
        $this->em->flush();

        return $adresse;
    }
}

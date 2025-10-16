<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserPaymentMethod;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<UserPaymentMethod>
 */
class UserPaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPaymentMethod::class);
    }

    /**
     * Retourne le wallet de l'utilisateur (par défaut en premier, puis plus récents).
     * @return UserPaymentMethod[]
     */
    public function findWalletForUser(User $user, ?string $gateway = null): array
    {
        $qb = $this->createQueryBuilder('pm')
            ->andWhere('pm.user = :user')
            ->setParameter('user', $user)
            ->orderBy('pm.isDefault', 'DESC')
            ->addOrderBy('pm.createdAt', 'DESC');

        if ($gateway !== null) {
            $qb->andWhere('pm.gateway = :gateway')
                ->setParameter('gateway', $gateway);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Moyen de paiement par défaut de l'utilisateur (s'il existe).
     */
    public function findDefaultForUser(User $user): ?UserPaymentMethod
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.user = :user')
            ->andWhere('pm.isDefault = :def')
            ->setParameter('user', $user)
            ->setParameter('def', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Favorite;
use App\Entity\ProductVariant;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Favorite>
 */
class FavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favorite::class);
    }

    /** @return int[] */
    public function idsForUser(User $user): array
    {
        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.productVariant) AS vid')
            ->andWhere('f.user = :u')
            ->setParameter('u', $user)
            ->getQuery()
            ->getScalarResult();

        // $rows = [ ['vid' => '12'], ['vid' => '34'] ... ]
        return array_map('intval', array_column($rows, 'vid'));
    }

    /**
     * Liste des favoris de l'utilisateur avec joins (variant + product).
     * Null-safe : retourne [] si $user est null.
     *
     * @return Favorite[]
     */
    public function findForUserWithJoins(?User $user): array
    {
        if (!$user) {
            return [];
        }

        return $this->createQueryBuilder('f')
            ->addSelect('v', 'p')
            ->join('f.productVariant', 'v')
            ->join('v.product', 'p')
            ->andWhere('f.user = :u')->setParameter('u', $user)
            ->getQuery()->getResult();
    }

    /**
     * Ids des variantes en favoris (pour un rendu rapide côté Twig/JS).
     * Null-safe : retourne [] si $user est null.
     *
     * @return int[]
     */
    public function getFavoriteVariantIdsForUser(?User $user): array
    {
        if (!$user) {
            return [];
        }

        $rows = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.productVariant) AS id')
            ->andWhere('f.user = :u')->setParameter('u', $user)
            ->getQuery()->getScalarResult();

        return array_map(static fn(array $r) => (int) $r['id'], $rows);
    }
   

    public function countForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.user = :u')->setParameter('u', $user)
            ->getQuery()->getSingleScalarResult();
    }
//    /**
//     * @return Favorite[] Returns an array of Favorite objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Favorite
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

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


    public function existsFor(User $user, ProductVariant $variant): bool
    {
        return (bool) $this->createQueryBuilder('f')
            ->select('1')
            ->andWhere('f.user = :u')->andWhere('f.productVariant = :v')
            ->setParameter('u', $user)
            ->setParameter('v', $variant)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }
    /** @return Favorite[] */
    public function findForUserWithJoins(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->addSelect('pv', 'p')
            ->join('f.productVariant', 'pv')
            ->join('pv.product', 'p')
            ->andWhere('f.user = :u')->setParameter('u', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()->getResult();
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

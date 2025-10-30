<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }


    public function findOneWithVariants(int $productId): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.productVariants', 'v')->addSelect('v')
            ->andWhere('p.id = :pid')->setParameter('pid', $productId)
            ->getQuery()->getOneOrNullResult();
    }

    public function findByCategory(Category $cat): array
{
    return $this->createQueryBuilder('p')
        ->join('p.subCategory', 'sc')
        ->join('sc.categorie', 'c') // <-- bien 'categorie'
        ->andWhere('c = :cat')
        ->setParameter('cat', $cat)
        ->getQuery()
        ->getResult();
}
    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

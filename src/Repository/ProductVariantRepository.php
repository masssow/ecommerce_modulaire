<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\ProductVariant;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<ProductVariant>
 */
class ProductVariantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductVariant::class);
    }

    public function findOneWithProduct(int $variantId): ?ProductVariant
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.product', 'p')->addSelect('p')
            ->andWhere('v.id = :id')->setParameter('id', $variantId)
            ->getQuery()->getOneOrNullResult();
    }

    public function findRelatedForVariant(ProductVariant $variant, int $limit = 8): array
    {
        $product   = $variant->getProduct();
        $subCat    = $product?->getSubCategory();

        $qb = $this->createQueryBuilder('v')
            ->addSelect('p', 'sc')
            ->join('v.product', 'p')
            ->leftJoin('p.subCategory', 'sc')
            ->where('p != :product')
            ->setParameter('product', $product)
            ->orderBy('v.id', 'DESC')
            ->setMaxResults($limit);

        if ($subCat) {
            $qb->andWhere('sc = :subCat')
                ->setParameter('subCat', $subCat);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByCategory(Category $category, int $limit = 24): array
    {
        // VERSION A (Product -> subCategory -> category)
        return $this->createQueryBuilder('v')
            ->innerJoin('v.product', 'p')
            ->innerJoin('p.subCategory', 'sc')
            ->innerJoin('sc.categorie', 'c')
            ->andWhere('c = :cat')
            ->setParameter('cat', $category)
            ->orderBy('v.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        /* --- VERSION B (si Product a directement "category") ---
        return $this->createQueryBuilder('v')
            ->innerJoin('v.product', 'p')
            ->andWhere('p.category = :cat')
            ->setParameter('cat', $category)
            ->orderBy('v.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        */
    }


    //  Favorite-related methods helper?s
    // public function idsForUser(User $user): array
    // {
    //     return array_map('intval', $this->createQueryBuilder('f')
    //         ->select('IDENTITY(f.productVariant) AS id')
    //         ->where('f.user = :u')->setParameter('u', $user)
    //         ->getQuery()->getSingleColumnResult());
    // }

    //    /**
    //     * @return ProductVariant[] Returns an array of ProductVariant objects
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

    //    public function findOneBySomeField($value): ?ProductVariant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

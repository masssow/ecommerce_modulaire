<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\ProductVariant;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * Charge une variante avec son produit (et éventuellement sous-catégorie).
     */
    public function findOneWithProduct(int $id): ?ProductVariant
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.product', 'p')->addSelect('p')
            ->leftJoin('p.subCategory', 'sc')->addSelect('sc')
            ->andWhere('v.id = :id')->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Variantes "liées" pour suggestion (par ex même sous-catégorie).
     *
     * @return ProductVariant[]
     */
    public function findRelatedForVariant(ProductVariant $variant, int $limit = 8): array
    {
        $product     = $variant->getProduct();
        $subCategory = $product?->getSubCategory();

        $qb = $this->createQueryBuilder('v')
            ->join('v.product', 'p')->addSelect('p')
            ->andWhere('v != :current')
            ->setParameter('current', $variant)
            ->setMaxResults($limit);

        if ($subCategory) {
            $qb
                ->join('p.subCategory', 'sc')
                ->andWhere('sc = :subCat')
                ->setParameter('subCat', $subCategory);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * QueryBuilder pour la boutique (catalogue) :
     * - recherche par mot-clé (sur product.name/description)
     * - filtre optionnel par "categoryId" (ici on considère que c’est la sous-catégorie).
     */
    public function createCatalogQb(?string $searchTerm = null, $categoryId = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')
            ->join('v.product', 'p')->addSelect('p')
            ->leftJoin('p.subCategory', 'sc')->addSelect('sc')
            ->orderBy('v.id', 'DESC');

        if ($searchTerm !== null && $searchTerm !== '') {
            $searchTerm = trim($searchTerm);
            $qb
                ->andWhere('LOWER(p.name) LIKE LOWER(:term) OR LOWER(p.description) LIKE LOWER(:term)')
                ->setParameter('term', '%' . $searchTerm . '%');
        }

        if ($categoryId) {
            // Ici on interprète "category" comme l’ID de la sous-catégorie
            $qb
                ->andWhere('sc.id = :subCatId')
                ->setParameter('subCatId', $categoryId);
        }

        return $qb;
    }

    /**
     * Catalogue filtré par Categorie (parent de SousCategorie).
     */
    public function createByCategoryQb(Category $category, ?string $searchTerm = null, ?int $subCategoryId = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')
            ->join('v.product', 'p')->addSelect('p')
            ->join('p.subCategory', 'sc')->addSelect('sc')
            ->join('sc.categorie', 'c') 
            ->andWhere('c = :cat')
            ->setParameter('cat', $category)
            ->orderBy('v.id', 'DESC');

        if ($searchTerm !== null && $searchTerm !== '') {
            $searchTerm = trim($searchTerm);
            $qb
                ->andWhere('LOWER(p.name) LIKE LOWER(:term) OR LOWER(p.description) LIKE LOWER(:term)')
                ->setParameter('term', '%' . $searchTerm . '%');
        }
        if ($subCategoryId) {
            $qb
                ->andWhere('sc.id = :subId')
                ->setParameter('subId', $subCategoryId);
        }

        return $qb;
    }

}

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

    /**
     * Recherche produits par mot-clé (name + éventuellement description)
     * et filtre optionnel par catégorie.
     *
     * @param string|null $term       Mot-clé tapé par l’utilisateur
     * @param int|string|null $categoryId  ID de catégorie (ou null)
     *
     * @return Product[]
     */
    public function searchByNameAndCategory(?string $term, $categoryId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC');

        // Filtre texte
        if ($term !== null && $term !== '') {
            $term = trim($term);

            // On cherche sur name (et description s'il y en a une)
            $qb
                ->andWhere('LOWER(p.name) LIKE LOWER(:term) OR LOWER(p.description) LIKE LOWER(:term)')
                ->setParameter('term', '%' . $term . '%');
        }

        // 🏷 Filtre catégorie
        if ($categoryId) {
            // si relation ManyToOne Product->Category
            $qb
                ->join('p.subCategory', 'sc')
                ->andWhere('sc.id = :catId')
                ->setParameter('catId', $categoryId);

            // SI dans ton modèle réel c’est plutôt subCategory :
            // ->join('p.subCategory', 'sc')
            // ->andWhere('sc.id = :catId')
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Suggestions rapides pour auto-complétion.
     * Retourne des lignes légères (id + name + éventuellement slug).
     *
     * @return array<int, array{id:int, name:string, slug:?string}>
     */
    public function suggestByName(?string $term, $categoryId = null, int $limit = 8): array
    {
        if ($term === null || trim($term) === '') {
            return [];
        }

        $term = trim($term);

        $qb = $this->createQueryBuilder('p')
            ->select('p.id, p.name, p.id')
            ->orderBy('p.name', 'ASC')
            ->setMaxResults($limit);

        $qb
            ->andWhere('LOWER(p.name) LIKE LOWER(:term)')
            ->setParameter('term', $term . '%'); // préfixe pour suggestion

        if ($categoryId) {
            $qb
                ->join('p.category', 'c')
                ->andWhere('c.id = :catId')
                ->setParameter('catId', $categoryId);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function findOneWithVariantsBySlug(string $slug): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.productVariants', 'v')->addSelect('v')
            ->leftJoin('p.subCategory', 's')->addSelect('s')
            ->leftJoin('s.categorie', 'c')->addSelect('c')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySubCategory(\App\Entity\SubCategory $subCategory): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.subCategory = :subCategory')
            ->setParameter('subCategory', $subCategory)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByParentCategory(\App\Entity\Category $category): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.subCategory', 'sc')
            ->andWhere('sc.categorie = :category')
            ->setParameter('category', $category)
            ->orderBy('p.id', 'DESC')
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

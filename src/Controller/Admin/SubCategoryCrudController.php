<?php

namespace App\Controller\Admin;

use App\Entity\SubCategory;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class SubCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SubCategory::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            yield TextField::new('name', 'Nom de la sous-catégorie'),
            yield TextEditorField::new('description'),
                AssociationField::new('Product', 'Produits'),
            yield TextField::new('slug', 'Slug'),

            yield TextField::new('imageFile')
                ->setFormType(VichImageType::class)
                ->onlyOnForms()
                ->setLabel('Image (Upload)'),
            yield ImageField::new('imageName')
                ->setBasePath('/uploads/subCategory')
                
                ->onlyOnIndex(),
        ];
    }
    
}

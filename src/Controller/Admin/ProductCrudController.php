<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\VichImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;


class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
             TextField::new('name'),
            AssociationField::new('subCategory', 'Sous-catégorie'),

            TextField::new('slug', 'Slug'),
            TextEditorField::new('description'),
            Field::new('imageFile')
                ->setFormType(VichImageType::class)
                ->onlyOnForms()
                ->setLabel('Image (Upload)'),
            ImageField::new('imageName')
                ->setBasePath('/uploads/product')
                ->onlyOnIndex(),
        ];
    }
    
}

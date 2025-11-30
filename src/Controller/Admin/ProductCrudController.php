<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Vich\UploaderBundle\Form\Type\VichImageType;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $slugField = SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')   // ⚠ toujours défini
            ->hideOnIndex();

        if ($pageName === Crud::PAGE_EDIT) {
            // En édition, on le verrouille, mais EasyAdmin a quand même besoin du targetFieldName
            $slugField->setFormTypeOption('disabled', true);
        }

        return [
            IdField::new('id')->onlyOnIndex(),

            TextField::new('name', 'Nom du produit'),

            AssociationField::new('subCategory', 'Sous-catégorie'),

            $slugField,

            TextEditorField::new('description', 'Description'),

            Field::new('imageFile', 'Image (upload)')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),

            ImageField::new('imageName', 'Image')
                ->setBasePath('/uploads/product')
                ->onlyOnIndex(),
        ];
    }
}

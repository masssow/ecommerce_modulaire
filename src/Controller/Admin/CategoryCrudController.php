<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            yield TextField::new('name', 'Nom de la catégorie'),
            yield TextEditorField::new('description'),
             AssociationField::new('subCategory', 'Sous-catégorie'),
            yield TextField::new('slug', 'Slug'),

            yield TextField::new('imageFile')
                ->setFormType(VichImageType::class)
                ->onlyOnForms()
                ->setLabel('Image (Upload)'),
            yield ImageField::new('imageName')
                ->setBasePath('/uploads/category')
                ->onlyOnIndex(),
        ];
    }
    
}

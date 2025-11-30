<?php

namespace App\Controller\Admin;

use App\Entity\ProductVariant;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Vich\UploaderBundle\Form\Type\VichImageType;

final class ProductVariantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductVariant::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $slugField = SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')   // ⚠ toujours défini
            ->hideOnIndex();

        if ($pageName === Crud::PAGE_EDIT) {
            $slugField->setFormTypeOption('disabled', true);
        }

        return [
            IdField::new('id')->onlyOnIndex(),

            TextField::new('name', 'Nom de la variante'),

            AssociationField::new('product', 'Produit')
                ->setRequired(true),

            $slugField,

            MoneyField::new('priceAmount', 'Prix')
                ->setStoredAsCents()
                ->setCurrency('EUR'),

            TextField::new('priceCurrency', 'Devise')
                ->setFormTypeOption('disabled', true)
                ->hideOnIndex(),

            ArrayField::new('Attributes', 'Attributs')->hideOnIndex(),

            IntegerField::new('stockQty', 'Stock')
                ->setFormTypeOption('attr', [
                    'min' => 0,
                    'step' => 1,
                    'inputmode' => 'numeric',
                ])
                ->setHelp('Raccourcis: 1, 5, 10 (vous pouvez saisir n’importe quelle quantité)'),

            AssociationField::new('prices', 'Historique des prix')->onlyOnDetail(),
            AssociationField::new('cartItems', 'Articles Panier')->onlyOnDetail(),
            AssociationField::new('orderItems', 'Articles Commande')->onlyOnDetail(),

            Field::new('imageFile', 'Image (upload)')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),

            ImageField::new('imageName', 'Image')
                ->setBasePath('/uploads/productVariant')
                ->onlyOnIndex(),
        ];
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\ProductVariant;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

final class ProductVariantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductVariant::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            // ID uniquement en index (facultatif)
            IdField::new('id')->onlyOnIndex(),

            // Produit parent
            AssociationField::new('product', 'Produit')
                ->setRequired(true),

            // Slug (si utile à éditer)
            TextField::new('slug', 'Slug')->hideOnIndex(),

            // 💶 Prix en CENTIMES (MoneyField sait gérer setStoredAsCents)
            // -> nécessite getPriceAmount() (+ idéalement setPriceAmount()) côté entité
            MoneyField::new('priceAmount', 'Prix')
                ->setStoredAsCents()
                // si ta version EasyAdmin supporte la devise depuis une propriété :
                // ->setCurrencyPropertyPath('priceCurrency')
                ->setCurrency('EUR'),

            // Devise (lecture seule par défaut — enlève 'disabled' si tu ajoutes setPriceCurrency())
            TextField::new('priceCurrency', 'Devise')
                ->setFormTypeOption('disabled', true)
                ->hideOnIndex(),

            // Attributs libres
            ArrayField::new('Attributes', 'Attributs')->hideOnIndex(),

            IntegerField::new('stockQty', 'Stock')
            ->setFormTypeOption('attr', ['min' => 0, 'step' => 1, 'inputmode' => 'numeric'])
            ->setHelp('Raccourcis: 1, 5, 10 (vous pouvez saisir n’importe quelle quantité)'),
           
            AssociationField::new('prices', 'Historique des prix')->onlyOnDetail(),
            AssociationField::new('cartItems', 'Articles Panier')->onlyOnDetail(),
            AssociationField::new('orderItems', 'Articles Commande')->onlyOnDetail(),

            // Upload d’image via Vich
            Field::new('imageFile', 'Image (upload)')
                ->setFormType(VichImageType::class)
                ->onlyOnForms(),

            // Aperçu image en index
            ImageField::new('imageName', 'Image')
                ->setBasePath('/uploads/productVariant')
                ->onlyOnIndex(),
        ];
    }
}

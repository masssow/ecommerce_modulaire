<?php

namespace App\Controller\Admin;

use App\Entity\ShippingMethod;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ShippingMethodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ShippingMethod::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Mode d’expédition')
            ->setEntityLabelInPlural('Modes d’expédition')
            ->setDefaultSort(['name' => 'ASC', 'id' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->setSearchFields(['name', 'code', 'carrier', 'carrierName', 'carrierCode']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        if (property_exists(ShippingMethod::class, 'enabled')) {
            $filters->add('enabled');
        }
        if (property_exists(ShippingMethod::class, 'name')) {
            $filters->add('name');
        }
        if (property_exists(ShippingMethod::class, 'code')) {
            $filters->add('code');
        }
        foreach (['carrier', 'carrierName', 'carrierCode'] as $p) {
            if (property_exists(ShippingMethod::class, $p)) {
                $filters->add($p);
            }
        }
        if (property_exists(ShippingMethod::class, 'basePrice')) {
            $filters->add('basePrice');
        } elseif (property_exists(ShippingMethod::class, 'baseCost')) {
            $filters->add('baseCost');
        }
        if (property_exists(ShippingMethod::class, 'freeShippingThreshold')) {
            $filters->add('freeShippingThreshold');
        }
        if (property_exists(ShippingMethod::class, 'estimatedDays')) {
            $filters->add('estimatedDays');
        }

        return $filters;
    }

    public function configureFields(string $pageName): iterable
    {
        // Champs communs
        $id          = IdField::new('id')->onlyOnIndex();
        $name        = TextField::new('name', 'Nom');
        $code        = TextField::new('code', 'Code technique');

        // Transporteur : on supporte plusieurs variantes de nommage
        $carrier      = TextField::new('carrier', 'Transporteur');
        $carrierName  = TextField::new('carrierName', 'Transporteur');
        $carrierCode  = TextField::new('carrierCode', 'Code transporteur');

        // Tarif de base (CENTIMES) : basePrice OU baseCost
        $basePriceField = MoneyField::new('basePrice', 'Tarif de base')
            ->setStoredAsCents()->setNumDecimals(2)->setCurrency('EUR')
            ->setFormTypeOption('required', true);

        $baseCostField = MoneyField::new('baseCost', 'Tarif de base')
            ->setStoredAsCents()->setNumDecimals(2)->setCurrency('EUR')
            ->setFormTypeOption('required', true);

        // Seuil franco (CENTIMES) — optionnel
        $freeThreshold = MoneyField::new('freeShippingThreshold', 'Seuil franco')
            ->setStoredAsCents()->setNumDecimals(2)->setCurrency('EUR')
            ->setHelp('Au-dessus de ce montant TTC, la livraison devient gratuite.');

        $enabled       = BooleanField::new('enabled', 'Actif');
        $estimatedDays = IntegerField::new('estimatedDays', 'Délais estimés (jours)');
        $description   = TextEditorField::new('description', 'Description')->hideOnIndex();

        $createdAt = DateTimeField::new('createdAt', 'Créé le')
            ->setFormTypeOption('disabled', true)
            ->onlyOnDetail();
        $updatedAt = DateTimeField::new('updatedAt', 'MAJ le')
            ->setFormTypeOption('disabled', true)
            ->onlyOnDetail();

        // Helper pour n’ajouter un champ que si la propriété existe dans l’entité
        $addIf = function (array &$bag, string $prop, $field): void {
            if (property_exists(ShippingMethod::class, $prop)) {
                $bag[] = $field;
            }
        };

        // Helper pour ajouter le bon champ "tarif de base"
        $addBaseAmount = function (array &$bag) use ($basePriceField, $baseCostField): void {
            if (property_exists(ShippingMethod::class, 'basePrice')) {
                $bag[] = $basePriceField;
            } elseif (property_exists(ShippingMethod::class, 'baseCost')) {
                $bag[] = $baseCostField;
            }
        };

        // Helper pour ajouter les champs "transporteur"
        $addCarrier = function (array &$bag) use ($carrier, $carrierName, $carrierCode): void {
            if (property_exists(ShippingMethod::class, 'carrier')) {
                $bag[] = $carrier;
            } else {
                if (property_exists(ShippingMethod::class, 'carrierName')) {
                    $bag[] = $carrierName;
                }
                if (property_exists(ShippingMethod::class, 'carrierCode')) {
                    $bag[] = $carrierCode;
                }
            }
        };

        // ===== Pages =====
        if (Crud::PAGE_INDEX === $pageName) {
            $fields = [$id];
            $addIf($fields, 'name', $name);
            $addIf($fields, 'code', $code);
            $addCarrier($fields);
            $addBaseAmount($fields);
            $addIf($fields, 'freeShippingThreshold', $freeThreshold);
            $addIf($fields, 'estimatedDays', $estimatedDays);
            $addIf($fields, 'enabled', $enabled);
            return $fields;
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            $fields = [];
            $addIf($fields, 'name', $name);
            $addIf($fields, 'code', $code);
            $addCarrier($fields);
            $addBaseAmount($fields);
            $addIf($fields, 'freeShippingThreshold', $freeThreshold);
            $addIf($fields, 'estimatedDays', $estimatedDays);
            $addIf($fields, 'enabled', $enabled);
            $addIf($fields, 'description', $description);
            $addIf($fields, 'createdAt', $createdAt);
            $addIf($fields, 'updatedAt', $updatedAt);
            return $fields;
        }

        if (Crud::PAGE_EDIT === $pageName) {
            $fields = [];
            $addIf($fields, 'name', $name);
            $addIf($fields, 'code', $code);
            $addCarrier($fields);
            $addBaseAmount($fields); // requis
            $addIf($fields, 'freeShippingThreshold', $freeThreshold);
            $addIf($fields, 'estimatedDays', $estimatedDays);
            $addIf($fields, 'enabled', $enabled);
            $addIf($fields, 'description', $description);
            return $fields;
        }

        // PAGE_NEW
        $fields = [];
        $addIf($fields, 'name', $name);
        $addIf($fields, 'code', $code);
        $addCarrier($fields);
        $addBaseAmount($fields); // requis
        $addIf($fields, 'freeShippingThreshold', $freeThreshold);
        $addIf($fields, 'estimatedDays', $estimatedDays);
        $addIf($fields, 'enabled', $enabled);
        $addIf($fields, 'description', $description);
        return $fields;
    }
}

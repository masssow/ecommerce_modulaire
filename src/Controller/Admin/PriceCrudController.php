<?php

namespace App\Controller\Admin;

use App\Entity\Price;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PriceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Price::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            yield MoneyField::new('amount', 'Montant')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
        ];
    }
    
}

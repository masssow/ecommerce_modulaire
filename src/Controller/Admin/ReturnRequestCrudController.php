<?php

namespace App\Controller\Admin;

use App\Entity\ReturnRequest;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ReturnRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ReturnRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de retour')
            ->setEntityLabelInPlural('Demandes de retour')
            ->setDefaultSort(['requestedAt' => 'DESC'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // ID uniquement sur la liste
        yield IdField::new('id')
            ->onlyOnIndex();

        // Commande liée
        yield AssociationField::new('orders', 'Commande');

        // Email client (via la commande → customer → user)
        // Nécessite que la relation orders->customer->user existe réellement
        yield TextField::new('orders.customer.user.email', 'Email client')
            ->onlyOnIndex();

        // Statut modifiable en back-office
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente'              => 'pending',
                'En cours de traitement'  => 'processing',
                'Acceptée'                => 'accepted',
                'Refusée'                 => 'rejected',
                'Remboursée'              => 'refunded',
            ])
            ->renderAsBadges([
                'pending'   => 'warning',
                'processing' => 'info',
                'accepted'  => 'success',
                'refunded'  => 'success',
                'rejected'  => 'danger',
            ]);

        // Date de demande : lecture seule dans les formulaires, mais visible sur liste & détail
        yield DateTimeField::new('requestedAt', 'Demandée le')
            ->setFormTypeOptions([
                'widget'   => 'single_text',
                'disabled' => true,   // on ne la modifie pas à la main
            ]);

        // Date remboursée : editable uniquement dans le formulaire
        yield DateTimeField::new('refundedAt', 'Remboursée le')
            ->setFormTypeOptions([
                'widget' => 'single_text',
            ])
            ->hideOnIndex(); // inutile en colonne sur la liste

        // Items concernés : visible uniquement dans la vue détail
        yield CollectionField::new('returnItems', 'Articles concernés')
            ->onlyOnDetail();
    }
}

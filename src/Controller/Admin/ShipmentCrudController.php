<?php

namespace App\Controller\Admin;

use App\Entity\Shipment;
use App\Entity\ReturnRequest;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class ShipmentCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGenerator $urlGen,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Shipment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        // Tri robuste (champ présent → sinon fallback id)
        $sort = ['id' => 'DESC'];
        if (property_exists(Shipment::class, 'shippedAt')) {
            $sort = ['shippedAt' => 'DESC'];
        } elseif (property_exists(Shipment::class, 'createdAt')) {
            $sort = ['createdAt' => 'DESC'];
        }

        return $crud
            ->setEntityLabelInSingular('Expédition')
            ->setEntityLabelInPlural('Expéditions')
            ->setDefaultSort($sort)
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined(true)
            ->setSearchFields(['trackingNumber', 'carrier', 'shippingMethodName', 'shippingMethodCode']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters->add('orders');
        foreach (['status', 'carrier', 'trackingNumber', 'shippedAt', 'deliveredAt', 'createdAt'] as $p) {
            if (property_exists(Shipment::class, $p)) {
                $filters->add($p);
            }
        }
        return $filters;
    }

    public function configureActions(Actions $actions): Actions
    {
        $createReturn = Action::new('createReturn', 'Créer un retour', 'fa fa-rotate-left')
            ->linkToCrudAction('createReturn')
            ->setCssClass('btn btn-secondary');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $createReturn)
            ->add(Crud::PAGE_DETAIL, $createReturn)
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, 'createReturn', Action::EDIT, Action::DELETE]);
    }

    public function configureFields(string $pageName): iterable
    {
        $addIf = function (array &$list, string $prop, $field): void {
            if (property_exists(Shipment::class, $prop)) {
                $list[] = $field;
            }
        };

        $id        = IdField::new('id')->onlyOnIndex();
        $order     = AssociationField::new('orders', 'Commande');

        $status    = ChoiceField::new('status', 'Statut')->setChoices([
            'En préparation' => 'pending',
            'Prête à expédier' => 'ready',
            'Expédiée' => 'shipped',
            'Livrée' => 'delivered',
            'Retour' => 'returned',
            'Annulée' => 'cancelled',
            'Perdue' => 'lost',
        ])->renderAsBadges();

        $carrier   = TextField::new('carrier', 'Transporteur');
        $tracking  = TextField::new('trackingNumber', 'N° de suivi');

        $shipCost  = MoneyField::new('shippingCost', 'Frais d’expédition')
            ->setStoredAsCents()->setNumDecimals(2)->setCurrency('EUR');
        $cost      = MoneyField::new('cost', 'Coût')
            ->setStoredAsCents()->setNumDecimals(2)->setCurrency('EUR');

        $methodName = TextField::new('shippingMethodName', 'Mode d’expédition')->setFormTypeOption('disabled', true);
        $methodCode = TextField::new('shippingMethodCode', 'Code expédition')->setFormTypeOption('disabled', true);

        $labelUrl  = UrlField::new('labelUrl', 'Étiquette')->hideOnIndex();

        // $createdAt   = DateTimeField::new('createdAt', 'Créée le')->setFormTypeOption('disabled', true);
        $shippedAt   = DateTimeField::new('shippedAt', 'Expédiée le');
        $deliveredAt = DateTimeField::new('deliveredAt', 'Livrée le');
        $updatedAt   = DateTimeField::new('updatedAt', 'MAJ le')->setFormTypeOption('disabled', true);

        if (Crud::PAGE_INDEX === $pageName) {
            $f = [$id, $order];
            $addIf($f, 'status', $status);
            $addIf($f, 'carrier', $carrier);
            $addIf($f, 'trackingNumber', $tracking);
            $addIf($f, 'shippingCost', $shipCost);
            if (!property_exists(Shipment::class, 'shippingCost')) {
                $addIf($f, 'cost', $cost);
            }
            $addIf($f, 'shippedAt', $shippedAt);
            $addIf($f, 'deliveredAt', $deliveredAt);
            return $f;
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            $f = [$order];
            $addIf($f, 'shippingMethodName', $methodName);
            $addIf($f, 'shippingMethodCode', $methodCode);
            $addIf($f, 'carrier', $carrier);
            $addIf($f, 'trackingNumber', $tracking);
            $addIf($f, 'status', $status);
            $addIf($f, 'shippingCost', $shipCost);
            if (!property_exists(Shipment::class, 'shippingCost')) {
                $addIf($f, 'cost', $cost);
            }
            $addIf($f, 'labelUrl', $labelUrl);
            // $addIf($f, 'createdAt', $createdAt);
            $addIf($f, 'shippedAt', $shippedAt);
            $addIf($f, 'deliveredAt', $deliveredAt);
            $addIf($f, 'updatedAt', $updatedAt);
            return $f;
        }

        if (Crud::PAGE_EDIT === $pageName) {
            $f = [$order->setFormTypeOption('disabled', true)];
            $addIf($f, 'carrier', $carrier);
            $addIf($f, 'trackingNumber', $tracking);
            $addIf($f, 'status', $status);
            $addIf($f, 'shippingCost', $shipCost);
            if (!property_exists(Shipment::class, 'shippingCost')) {
                $addIf($f, 'cost', $cost);
            }
            $addIf($f, 'shippedAt', $shippedAt);
            $addIf($f, 'deliveredAt', $deliveredAt);
            return $f;
        }

        // PAGE_NEW
        $f = [$order];
        $addIf($f, 'carrier', $carrier);
        $addIf($f, 'trackingNumber', $tracking);
        $addIf($f, 'status', $status);
        $addIf($f, 'shippingCost', $shipCost);
        if (!property_exists(Shipment::class, 'shippingCost')) {
            $addIf($f, 'cost', $cost);
        }
        $addIf($f, 'shippedAt', $shippedAt);
        $addIf($f, 'deliveredAt', $deliveredAt);
        return $f;
    }

    /**
     * Action personnalisée : crée un ReturnRequest à partir de l’expédition.
     */
    public function createReturn(AdminContext $context): Response
    {
        $eaEntity = $context->getEntity();
        $shipment = $eaEntity?->getInstance();

        if (!$shipment instanceof Shipment) {
            $this->addFlash('danger', "Expédition introuvable.");
            return $this->redirect($this->urlGen->setAction(Crud::PAGE_INDEX)->generateUrl());
        }

        /** @var Order|null $order */
        $order = $shipment->getOrders();
        if (!$order) {
            $this->addFlash('danger', "Cette expédition n’est rattachée à aucune commande.");
            return $this->redirect($this->urlGen->setAction(Crud::PAGE_DETAIL)->setEntityId($shipment->getId())->generateUrl());
        }

        // Option : éviter les doublons (si un retour existe déjà pour cette expédition)
        $existing = null;
        if (method_exists(ReturnRequest::class, 'getShipment')) {
            $existing = $this->em->getRepository(ReturnRequest::class)->findOneBy(['shipment' => $shipment]);
        }
        if ($existing) {
            $this->addFlash('info', "Un retour existe déjà pour cette expédition.");
            // Redirige vers le retour existant si CRUD dispo
            $url = class_exists(\App\Controller\Admin\ReturnRequestCrudController::class)
                ? $this->urlGen->unsetAll()
                ->setController(\App\Controller\Admin\ReturnRequestCrudController::class)
                ->setAction(Crud::PAGE_EDIT)
                ->setEntityId($existing->getId())
                ->generateUrl()
                : $this->urlGen->unsetAll()
                ->setController(self::class)
                ->setAction(Crud::PAGE_DETAIL)
                ->setEntityId($shipment->getId())
                ->generateUrl();
            return $this->redirect($url);
        }

        // Création du retour
        $rr = new ReturnRequest();
        $rr->setOrders($order);
        if (method_exists($rr, 'setShipment')) {
            // $rr->setShipment($shipment);
        }
        if (method_exists($rr, 'setStatus')) {
            $rr->setStatus('requested');
        }
        // if (method_exists($rr, 'setCreatedAt')) {
            // $rr->setCreatedAt(new \DateTimeImmutable());
        // }

        $this->em->persist($rr);
        $this->em->flush();

        $this->addFlash('success', "Demande de retour créée (#{$rr->getId()}).");

        // Redirection : vers le CRUD ReturnRequest si présent, sinon détail de l’expédition
        if (class_exists(\App\Controller\Admin\ReturnRequestCrudController::class)) {
            $url = $this->urlGen->unsetAll()
                ->setController(\App\Controller\Admin\ReturnRequestCrudController::class)
                ->setAction(Crud::PAGE_EDIT)
                ->setEntityId($rr->getId())
                ->generateUrl();
        } else {
            $url = $this->urlGen->unsetAll()
                ->setController(self::class)
                ->setAction(Crud::PAGE_DETAIL)
                ->setEntityId($shipment->getId())
                ->generateUrl();
        }

        return new RedirectResponse($url);
    }
}

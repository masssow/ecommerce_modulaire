<?php
// src/Controller/Admin/OrderCrudController.php
namespace App\Controller\Admin;

use App\Entity\Order;
use App\Entity\Customer;
use App\Entity\Adresse;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

final class OrderCrudController extends AbstractCrudController
{
    private const STATUS_CHOICES = [
        'Brouillon' => 'draft',
        'En attente de paiement' => 'pending_payment',
        'Payée' => 'paid',
        'Expédiée' => 'fulfilled',
        'Annulée' => 'cancelled',
        'Remboursée' => 'refunded',
    ];

    public function __construct(private AdminUrlGenerator $adminUrlGenerator) {}

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined(true)
            ->setPaginatorPageSize(25)
            ->setSearchFields(['number']); // recherche sur numéro
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('currency')
            ->add('createdAt')
            ->add('paymentGateway');
    }

    public function configureFields(string $pageName): iterable
    {
        // ===== Helpers d’affichage =====
        $formatUser = function (?Customer $c): string {
            $u = $c?->getUser();
            if ($u) {
                $fullname = trim(($u->getFirstname() ?? '') . ' ' . ($u->getLastname() ?? ''));
                $mail = $u->getEmail() ?? '';
                return trim($fullname . ' (' . $mail . ')');
            }
            return $c ? ('Customer #' . $c->getId()) : '—';
        };

        $formatAdresse = function (?Adresse $a): string {
            if (!$a) return '—';
            $s = $a->getLine1() ?? '';
            $z = $a->getPostalCode() ?? '';
            $c = $a->getCity() ?? '';
            $co = $a->getCountry() ?? '';
            $line = trim($s);
            $cityLine = trim(trim($z . ' ' . $c));
            $parts = array_filter([$line, $cityLine, $co], fn($v) => $v !== '');
            return $parts ? implode(', ', $parts) : '—';
        };

        // ===== Champs =====
        // (ID retiré de l’index comme demandé)

        // N° commande cliquable → Détail
        $number = TextField::new('number', 'N° commande')
            ->renderAsHtml()
            ->formatValue(function ($value, Order $o) {
                $url = $this->adminUrlGenerator
                    ->unsetAll()
                    ->setController(self::class)
                    ->setAction(Crud::PAGE_DETAIL)
                    ->setEntityId($o->getId())
                    ->generateUrl();
                return sprintf('<a href="%s">%s</a>', $url, htmlspecialchars((string) $o->getNumber(), ENT_QUOTES));
            })
            ->setFormTypeOption('disabled', true);

        $customer = AssociationField::new('customer', 'Client')
            ->setFormTypeOptions([
                'choice_label' => fn(?Customer $c) => $formatUser($c),
            ])
            ->formatValue(fn($value, Order $o) => $formatUser($o->getCustomer()))
            ->setFormTypeOption('disabled', true)
            ->setRequired(true);

        $billing = AssociationField::new('billingAddress', 'Adresse facturation')
            ->setFormTypeOptions([
                'choice_label' => fn(?Adresse $a) => $formatAdresse($a),
            ])
            ->formatValue(fn($value, Order $o) => $formatAdresse($o->getBillingAddress()))
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();

        $shipping = AssociationField::new('shippingAddress', 'Adresse expédition')
            ->setFormTypeOptions([
                'choice_label' => fn(?Adresse $a) => $formatAdresse($a),
            ])
            ->formatValue(fn($value, Order $o) => $formatAdresse($o->getShippingAddress()))
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();

        $currency = TextField::new('currency', 'Devise')
            ->setFormTypeOption('disabled', true);

        // Montants (centimes)
        $grandTotal = MoneyField::new('grandTotal', 'Total TTC')
            ->setCurrency('EUR') // → si dispo, utilise setCurrencyPropertyPath('currency')
            ->setStoredAsCents()->setNumDecimals(2)
            ->setFormTypeOption('disabled', true);

        $subtotal = MoneyField::new('subtotal', 'Sous-total')
            ->setCurrency('EUR')->setStoredAsCents()->setNumDecimals(2)
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();

        $taxTotal = MoneyField::new('taxTotal', 'Total taxes')
            ->setCurrency('EUR')->setStoredAsCents()->setNumDecimals(2)
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();

        $shippingTotal = MoneyField::new('shippingTotal', 'Frais de port')
            ->setCurrency('EUR')->setStoredAsCents()->setNumDecimals(2)
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();

        // Livraison
        $shipName = TextField::new('shippingMethodName', 'Méthode expédition')
            ->setFormTypeOption('disabled', true);

        $shipCode = TextField::new('shippingMethodCode', 'Code expédition')
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();

        // Paiement
        $payName = TextField::new('paymentMethodName', 'Méthode paiement')
            ->setFormTypeOption('disabled', true);

        $payGateway = TextField::new('paymentGateway', 'Passerelle')
            ->setFormTypeOption('disabled', true);

        $payType = TextField::new('paymentMethodType', 'Type paiement')
            ->setFormTypeOption('disabled', true)
            ->hideOnIndex();

        // Stripe (détail)
        $stripeSid = TextField::new('stripeSessionId', 'Stripe Session ID')
            ->setFormTypeOption('disabled', true)
            ->onlyOnDetail();

        $stripePi = TextField::new('stripePaymentIntentId', 'PaymentIntent ID')
            ->setFormTypeOption('disabled', true)
            ->onlyOnDetail();

        $pmBrand = TextField::new('stripePmBrand', 'Carte (marque)')
            ->setFormTypeOption('disabled', true)
            ->onlyOnDetail();

        $pmLast4 = TextField::new('stripePmLast4', 'Carte (last4)')
            ->setFormTypeOption('disabled', true)
            ->onlyOnDetail();

        $status = ChoiceField::new('status', 'Statut')
            ->setChoices(self::STATUS_CHOICES)
            ->allowMultipleChoices(false)
            ->renderAsBadges();

        $createdAt = DateTimeField::new('createdAt', 'Créée le')
            ->setFormTypeOption('disabled', true);

        if (Crud::PAGE_INDEX === $pageName) {
            // (ID retiré)
            return [
                $number,
                $customer,
                $grandTotal,
                $currency,
                $shipName,
                $payGateway,
                $status,
                $createdAt,
            ];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [
                $number,
                $customer,
                $billing,
                $shipping,
                $currency,
                $grandTotal,
                $subtotal,
                $taxTotal,
                $shippingTotal,
                $shipName,
                $shipCode,
                $payName,
                $payGateway,
                $payType,
                $stripeSid,
                $stripePi,
                $pmBrand,
                $pmLast4,
                $status,
                $createdAt,
            ];
        }

        if (Crud::PAGE_EDIT === $pageName) {
            // Seul le statut est modifiable
            return [
                $number,
                $customer,
                $billing,
                $shipping,
                $currency,
                $grandTotal,
                $subtotal,
                $taxTotal,
                $shippingTotal,
                $shipName,
                $shipCode,
                $payName,
                $payGateway,
                $payType,
                $status,
                $createdAt,
                $stripeSid,
                $stripePi,
                $pmBrand,
                $pmLast4,
            ];
        }

        // PAGE_NEW (rarement utilisée)
        return [
            $customer,
            $billing,
            $shipping,
            $currency,
            $grandTotal,
            $subtotal,
            $taxTotal,
            $shippingTotal,
            $shipName,
            $shipCode,
            $payName,
            $payGateway,
            $payType,
            $status,
        ];
    }
}

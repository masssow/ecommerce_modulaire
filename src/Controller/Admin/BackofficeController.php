<?php

namespace App\Controller\Admin;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Order;
use App\Entity\Price;
use App\Entity\Coupon;
use App\Entity\Adresse;
use App\Entity\Dispute;
use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\CartItem;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Shipment;
use App\Entity\OrderItem;
use App\Entity\Promotion;
use App\Entity\ReturnItem;
use App\Entity\TaxSetting;
use App\Entity\SubCategory;
use App\Entity\PaymentMethod;
use App\Entity\PromotionRule;
use App\Entity\ReturnRequest;
use App\Entity\InventoryStock;
use App\Entity\ProductVariant;
use App\Entity\ShippingMethod;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

#[AdminDashboard(routePath: '/administration', routeName: 'admin')]
class BackofficeController extends AbstractDashboardController
{
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator->setController(ProductCrudController::class)->generateUrl());
        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        // return $this->redirectToRoute('admin_user_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Ecommerce Modulaire');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Dashboard', 'fa fa-home');

        yield MenuItem::section('Ecommerce');

        yield MenuItem::linkToCrud('Catégories', 'fas fa-folder', Category::class);
        yield MenuItem::linkToCrud('Sous-catégories', 'fas fa-folder-open', SubCategory::class);
        yield MenuItem::linkToCrud('Produits', 'fas fa-box', Product::class);
        yield MenuItem::linkToCrud('Variantes', 'fas fa-tags', ProductVariant::class);
        yield MenuItem::linkToCrud('Stocks', 'fas fa-warehouse', InventoryStock::class);
        yield MenuItem::linkToCrud('Prix', 'fas fa-dollar-sign', Price::class);
        yield MenuItem::linkToCrud('Promotions', 'fas fa-gift', Promotion::class);
        yield MenuItem::linkToCrud('Règles promo', 'fas fa-cogs', PromotionRule::class);
        yield MenuItem::linkToCrud('Coupons', 'fas fa-ticket-alt', Coupon::class);
        yield MenuItem::linkToCrud('Réglages taux', 'fas fa-percentage', TaxSetting::class);

        yield MenuItem::section('Commandes & Paiements');
        yield MenuItem::linkToCrud('Commandes', 'fas fa-shopping-cart', Order::class);
        yield MenuItem::linkToCrud('Articles commande', 'fas fa-list', OrderItem::class);
        yield MenuItem::linkToCrud('Retours', 'fas fa-undo', ReturnRequest::class);
        yield MenuItem::linkToCrud('Articles retour', 'fas fa-undo-alt', ReturnItem::class);
        yield MenuItem::linkToCrud('Litiges', 'fas fa-exclamation-circle', Dispute::class);
        yield MenuItem::linkToCrud('Paiements', 'fas fa-credit-card', Payment::class);
        yield MenuItem::linkToCrud('Méthodes paiement', 'fas fa-university', PaymentMethod::class);

        yield MenuItem::section('Livraison');
        yield MenuItem::linkToCrud('Expéditions', 'fas fa-truck', Shipment::class);
        yield MenuItem::linkToCrud('Méthodes livraison', 'fas fa-shipping-fast', ShippingMethod::class);

        yield MenuItem::section('Clients & Utilisateurs');
        yield MenuItem::linkToCrud('Clients', 'fas fa-user', Customer::class);
        yield MenuItem::linkToCrud('Adresses', 'fas fa-address-book', Adresse::class);
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);

        yield MenuItem::section('Panier');
        yield MenuItem::linkToCrud('Paniers', 'fas fa-shopping-basket', Cart::class);
        yield MenuItem::linkToCrud('Articles panier', 'fas fa-list-ul', CartItem::class);

        yield MenuItem::linkToLogout('Déconnexion', 'fas fa-sign-out-alt');
    }
}

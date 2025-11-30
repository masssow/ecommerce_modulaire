<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;

// --- Entités du projet ---
use App\Entity\Category;
use App\Entity\SubCategory;
use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\Customer;
use App\Entity\User;
use App\Entity\Adresse;
use App\Entity\PaymentMethod;
use App\Entity\ShippingMethod;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\ReturnRequest;

class AppFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    public static function getGroups(): array
    {
        return ['sandbox'];
    }

    /* ---------------- Helpers ---------------- */

    private function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
    }

    private function slug(string $s): string
    {
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $s = preg_replace('~[^a-zA-Z0-9]+~', '-', $s);
        $s = trim($s, '-');
        return strtolower($s ?: 'n-a');
    }

    private function touchUpdatedAt(object $e): void
    {
        if (method_exists($e, 'setUpdatedAt')) {
            $e->setUpdatedAt(new \DateTimeImmutable());
        }
    }

    private function makeAdresse(FakerGenerator $faker, ?User $owner = null): Adresse
    {
        $a = new Adresse();
        if ($owner) {
            $a->setUser($owner);
        }
        $a->setLabel($faker->randomElement(['Domicile', 'Bureau', 'Autre']));
        $a->setLine1($faker->streetAddress());
        $a->setLine2($faker->optional(0.3)->secondaryAddress());
        $a->setPostalCode($faker->postcode());
        $a->setCity($faker->city());
        $a->setCountry('France');
        $a->setPhone($faker->optional(0.5)->numerify('06########'));
        $a->setType($faker->randomElement(['livraison', 'facturation']));
        $a->setIsDefault($faker->boolean(25));
        $a->setUpdatedAt(new \DateTimeImmutable());
        return $a;
    }

    public function load(ObjectManager $em): void
    {
        // Fixtures sandbox-only
        if ($this->params->get('kernel.environment') !== 'dev') {
            return;
        }

        /** @var FakerGenerator $faker */
        $faker = FakerFactory::create('fr_FR');

        /* ---------- 0) Users + Customers ---------- */
        $users = [];
        $customersByUser = [];

        // Admin
        $admin = new User();
        if (method_exists($admin, 'setEmail')) $admin->setEmail('admin@example.com');
        if (method_exists($admin, 'setFirstname')) $admin->setFirstname('Admin');
        if (method_exists($admin, 'setLastname')) $admin->setLastname('Sandbox');
        if (method_exists($admin, 'setRoles')) $admin->setRoles(['ROLE_ADMIN']);
        if (method_exists($admin, 'setPassword')) {
            $admin->setPassword($this->hasher->hashPassword($admin, 'admin1234'));
        }
        $em->persist($admin);

        // 4 clients + Customer (1:1 avec User)
        for ($i = 1; $i <= 4; $i++) {
            $u = new User();
            if (method_exists($u, 'setEmail')) $u->setEmail("client{$i}@example.com");
            if (method_exists($u, 'setFirstname')) $u->setFirstname($faker->firstName());
            if (method_exists($u, 'setLastname')) $u->setLastname($faker->lastName());
            if (method_exists($u, 'setRoles')) $u->setRoles(['ROLE_USER']);
            if (method_exists($u, 'setPassword')) {
                $u->setPassword($this->hasher->hashPassword($u, 'client1234'));
            }
            $em->persist($u);
            $users[] = $u;

            $c = new Customer();
            $c->setUser($u);
            $em->persist($c);

            $customersByUser[spl_object_id($u)] = $c;
        }

        /* ---------- 1) Upload dirs (on ne crée PAS d’images) ---------- */
        $base = \dirname(__DIR__, 2) . '/public/uploads';
        $catUpload  = $base . '/category';
        $subUpload  = $base . '/subCategory';
        $prodUpload = $base . '/product';
        $varUpload  = $base . '/productVariant';
        // on s'assure juste que les dossiers existent (au cas où)
        $this->ensureDir($catUpload);
        $this->ensureDir($subUpload);
        $this->ensureDir($prodUpload);
        $this->ensureDir($varUpload);

        // Pool d'images fournie par toi (on les UTILISE telles quelles)
        $poolParfum   = ['parfum1.jpg', 'parfum3.jpg', 'parfum4.jpg'];
        $poolCouture  = ['couture1.jpg', 'couture2.jpg'];
        $poolBeaute   = ['maquillage.jpg'];

        /* ---------- 2) Categories + SubCategories ---------- */
        $categoryNames = ['Beauté', 'Parfum', 'Couture Afro', 'Maison & Déco'];
        $subNames = [
            ['Soins visage', 'Maquillage', 'Accessoires'],
            ['Eaux de parfum', 'Eaux de toilette', 'Coffrets'],
            ['Mode Femme', 'Mode Homme', 'Enfants'],
            ['Décoration murale', 'Bougies', 'Textiles maison'],
        ];

        $categories = [];
        $subCategories = [];

        foreach ($categoryNames as $ci => $catName) {
            $cat = new Category();
            $cat->setName($catName);
            $cat->setSlug($this->slug($catName));
            $cat->setDescription($faker->optional(0.6)->sentence(15));
            $this->touchUpdatedAt($cat);
            // imageName de Category optionnelle : on peut la laisser vide
            $em->persist($cat);
            $categories[$ci] = $cat;

            $subCategories[$ci] = [];
            foreach ($subNames[$ci] as $si => $sname) {
                $sub = new SubCategory();
                $sub->setName($sname);
                $sub->setSlug($this->slug($sname));
                $sub->setCategorie($cat);
                $sub->setDescription($faker->optional(0.5)->sentence(12));
                $this->touchUpdatedAt($sub);
                // imageName de SubCategory optionnelle : on peut la laisser vide
                $em->persist($sub);
                $subCategories[$ci][$si] = $sub;
            }
        }

        /* ---------- 3) Products (4 par sous-catégorie) ---------- */
        $products = [];
        foreach ($subCategories as $ci => $subs) {
            foreach ($subs as $si => $sub) {
                $catName = $categories[$ci]->getName();

                // Choix d'image produit selon la catégorie (répétitions OK)
                $pickProductImage = function () use ($catName, $poolParfum, $poolCouture, $poolBeaute, $faker) {
                    return match ($catName) {
                        'Parfum'       => $faker->randomElement($poolParfum),
                        'Couture Afro' => $faker->randomElement($poolCouture),
                        'Beauté'       => $faker->randomElement($poolBeaute),
                        default        => $faker->randomElement(array_merge($poolParfum, $poolCouture, $poolBeaute)),
                    };
                };

                for ($k = 0; $k < 4; $k++) {
                    $p = new Product();

                    $baseName = match ($catName) {
                        'Beauté'       => $faker->randomElement(['Crème hydratante', 'Sérum éclat', 'Gommage doux', 'Masque purifiant', 'Maquillage set']),
                        'Parfum'       => $faker->randomElement(['Eau de parfum Ambre', 'Eau de toilette Citrus', 'Coffret Découverte', 'Musc Blanc']),
                        'Couture Afro' => $faker->randomElement(['Grand Boubou Ndadan Homme', 'Robe Ankara Femme', 'Robe swee princesse fille', 'Boubou garçon']),
                        default        => $faker->randomElement(['Déco murale', 'Bougie parfumée', 'Coussin motif', 'Tapis tissé']),
                    };
                    $name = $baseName . ' ' . $faker->unique()->numberBetween(100, 999);
                    $p->setName($name);
                    $p->setSlug($this->slug($name . '-' . $ci . '-' . $si . '-' . $k));
                    $p->setDescription($faker->optional(0.7)->sentence(20));
                    $p->setSubCategory($sub);

                    // ⬇️ On assigne un NOM DE FICHIER existant (pas de génération)
                    $p->setImageName($pickProductImage());

                    $this->touchUpdatedAt($p);

                    $em->persist($p);
                    $products[] = $p;
                }
            }
        }

        /* ---------- 4) ProductVariants (3 par produit) ---------- */
        $variants = [];
        foreach ($products as $idx => $product) {
            $catName = $product->getSubCategory()?->getCategorie()?->getName() ?? 'Beauté';

            $pickVariantImage = function () use ($catName, $poolParfum, $poolCouture, $poolBeaute, $faker) {
                return match ($catName) {
                    'Parfum'       => $faker->randomElement($poolParfum),
                    'Couture Afro' => $faker->randomElement($poolCouture),
                    'Beauté'       => $faker->randomElement($poolBeaute),
                    default        => $faker->randomElement(array_merge($poolParfum, $poolCouture, $poolBeaute)),
                };
            };

            for ($v = 0; $v < 3; $v++) {
                $variant = new ProductVariant();
                $variant->setProduct($product);

                /* ---------- Attributs selon catégorie ---------- */
                $variantAttrs = match ($catName) {

                    'Parfum' => [
                        'volume' => $faker->randomElement(['30ml', '50ml', '100ml']),
                    ],

                    'Couture Afro' => [
                        'taille' => $faker->randomElement(['S', 'M', 'L', 'XL']),
                        'couleur' => $faker->randomElement(['Rouge', 'Bleu', 'Vert', 'Jaune']),
                    ],

                    'Beauté' => [
                        'pack' => $faker->randomElement(['30ml', '50ml']),
                    ],

                    default => [
                        'option' => $faker->randomElement(['A', 'B', 'C']),
                    ]
                };

                /* ---------- Nom Variant lisible ---------- */
                $baseName = $product->getName();

                // Exemple : "Grand Boubou Ndadan Homme 456 – taille: L, couleur: Rouge"
                $variantName =
                    $baseName . ' – ' .
                    implode(', ', array_map(
                        fn($k, $v) => "$k: $v",
                        array_keys($variantAttrs),
                        $variantAttrs
                    ));

                $variant->setName($variantName);

                /* ---------- Slug propre ---------- */
                $variant->setSlug($this->slug($variantName));


                // Prix via Money
                $base = $faker->numberBetween(800, 15000);
                $delta = (int) round($base * $faker->randomFloat(2, 0.00, 0.20));
                $amount = $faker->boolean() ? $base + $delta : max(100, $base - $delta);
                $variant->setPriceAmount($amount)->setPriceCurrency('EUR');

                // Attributs
                $variant->setAttributes($variantAttrs);


                // Stock OneToOne
                $qty = $faker->numberBetween(5, 80);
                $variant->setStockQty($qty);

                // Reserved fix
                $inv = $variant->getInventoryStock();
                if ($inv && $inv->getReserved() === null) {
                    $inv->setReserved(0);
                }
                // ⬇️ Assigne un NOM DE FICHIER existant dans uploads/productVariant
                $variant->setImageName($pickVariantImage());

                $this->touchUpdatedAt($variant);

                $em->persist($variant);
                $variants[] = $variant;
            }
        }

        // 10 variantes en promotion = -20%
        shuffle($variants);
        $promoCount = min(10, count($variants));
        for ($i = 0; $i < $promoCount; $i++) {
            $va = $variants[$i];
            $va->setPriceAmount((int) max(1, round($va->getPriceAmount() * 0.8)));
            $this->touchUpdatedAt($va);
            $em->persist($va);
        }

        /* ---------- 5) PaymentMethod / ShippingMethod ---------- */
        $pms = [];
        foreach ([['Carte (Stripe)', 'stripe'], ['PayPal', 'paypal'], ['Paiement à la livraison', 'cod']] as [$n, $g]) {
            $pm = new PaymentMethod();
            $pm->setName($n);
            $pm->setGateway($g);
            $pm->setEnable(true);
            $em->persist($pm);
            $pms[] = $pm;
        }

        $sms = [];
        foreach (
            [
                ['Standard (3-5 j)', 'Colissimo', 499, 5000],
                ['Express (24-48h)', 'Chronopost', 999, null],
                ['Point Relais', 'Mondial Relay', 399, 4500],
            ] as [$n, $carrier, $baseCost, $free]
        ) {
            $sm = new ShippingMethod();
            $sm->setName($n);
            $sm->setCarrier($carrier);
            $sm->setBaseCost($baseCost); // centimes
            $sm->setFreeShippingThreshold($free);
            $em->persist($sm);
            $sms[] = $sm;
        }

        /* ---------- 6) Orders (6) + OrderItems ---------- */
        $orders = [];
        for ($i = 1; $i <= 6; $i++) {
            $order = new Order();
            $order->setNumber(sprintf('ORD-%s-%04d', (new \DateTime())->format('Ymd'), $i));
            $order->setStatus($faker->randomElement(['new', 'paid', 'shipped', 'completed']));
            $order->setCreatedAt(new \DateTimeImmutable('-' . mt_rand(0, 60) . ' days'));

            // Client -> Customer réutilisé
            $u = $users[($i - 1) % count($users)];
            $customer = $customersByUser[spl_object_id($u)];
            $order->setCustomer($customer);

            // Adresses (non nullable)
            $shipAddr = $this->makeAdresse($faker, $u);
            $billAddr = $this->makeAdresse($faker, $u);
            $em->persist($shipAddr);
            $em->persist($billAddr);
            $order->setShippingAddress($shipAddr);
            $order->setBillingAddress($billAddr);

            // Shipping / Payment + snapshots
            $sm = $sms[$i % count($sms)];
            $pm = $pms[$i % count($pms)];
            $order->setShippingMethod($sm);
            $order->setShippingMethodName($sm->getName());
            $order->setShippingMethodCode($this->slug($sm->getCarrier()));
            $order->setPaymentMethod($pm);
            $order->setPaymentMethodName($pm->getName());
            $order->setPaymentGateway($pm->getGateway());
            $order->setCurrency('EUR');

            // Items (2..4)
            $itemsCount = $faker->numberBetween(2, 4);
            $subtotal = 0;
            for ($k = 0; $k < $itemsCount; $k++) {
                $variant = $variants[$faker->numberBetween(0, count($variants) - 1)];
                $qty     = $faker->numberBetween(1, 3);
                $unit    = $variant->getPriceAmount(); // centimes

                $item = new OrderItem();
                $item->setOrders($order);
                $item->setProductVariant($variant);
                $item->setQuantity($qty);
                $item->setUnitPrice($unit);
                $item->setTotalPrice($unit * $qty);
                $item->setCurrency('EUR');

                $subtotal += $unit * $qty;
                $em->persist($item);
            }

            // Totaux
            $shippingFee = (int) $sm->getBaseCost();
            $tax         = (int) round($subtotal * 0.2); // TVA 20%
            $grand       = $subtotal + $shippingFee + $tax;

            $order->setSubtotal($subtotal);
            $order->setShippingTotal($shippingFee);
            $order->setTaxTotal($tax);
            $order->setGrandTotal($grand);

            $em->persist($order);
            $orders[] = $order;
        }

        /* ---------- 7) ReturnRequests (3) ---------- */
        for ($r = 0; $r < 3 && $r < count($orders); $r++) {
            $rr = new ReturnRequest();
            $rr->setOrders($orders[$r]);
            $rr->setStatus($faker->randomElement(['pending', 'approved', 'rejected']));
            $rr->setRequestedAt(new \DateTimeImmutable('-' . mt_rand(1, 30) . ' days'));
            if ($rr->getStatus() === 'approved') {
                $rr->setRefundedAt(new \DateTimeImmutable('-' . mt_rand(0, 5) . ' days'));
            }
            $em->persist($rr);
        }

        $em->flush();
    }
}

<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Customer;
use App\Entity\Payment;
use App\Entity\Adresse;
use App\Entity\ShippingMethod;
use App\Service\CartManager;
use App\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/checkout', name: 'app_checkout_')]
final class CheckoutController extends AbstractController
{
    private const SESSION_SM_ID = 'shipping_method_id';

    public function __construct(
        private readonly CartManager $cartManager,
        private readonly SettingService $settings,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Veuillez vous connecter pour continuer.');
            return $this->redirectToRoute('app_login');
        }

        $adresses = $this->em->getRepository(Adresse::class)->findBy(['user' => $user]);

        $cart = $this->cartManager->getCurrentCart();
        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('info', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_page');
        }

        // === MODES D’EXPÉDITION ACTIFS + SÉLECTION ===
        $methods  = $this->em->getRepository(ShippingMethod::class)->findBy(
            $this->hasProperty(ShippingMethod::class, 'enabled') ? ['enabled' => true] : [],
            ['name' => 'ASC']
        );
        $selected = $this->resolveSelectedMethod($request, $methods); // peut être null si aucun mode

        // --- Montants en CENTIMES pour l’affichage ---
        $taxRate = (float) $this->settings->getTva(); // ex: 20
        $rate    = 1 + ($taxRate / 100);

        $subTotalCts = (int) $cart->getTotal();

        // 💡 Calcul du port depuis le mode sélectionné (fallback: SettingService)
        [$shippingCts, $appliedMethod] = $this->computeShippingFor(
            subtotalCts: $subTotalCts,
            method: $selected
        );

        // Affichage en euros
        $subTotalTtc = round($subTotalCts / 100, 2);
        $shippingTtc = round($shippingCts / 100, 2);
        $subTotalHt  = round($subTotalTtc / $rate, 2);
        $subTva      = round($subTotalTtc - $subTotalHt, 2);
        $subTotalCts = (int) $cart->getTotal();
        $shippingHt  = round($shippingTtc / $rate, 2);
        $shippingTva = round($shippingTtc - $shippingHt, 2);
        $totalWithShipCts = $subTotalCts + $shippingCts;
        $totalHt     = round($subTotalHt + $shippingHt, 2);
        $totalTva    = round($subTva + $shippingTva, 2);
        $totalTtc    = round($subTotalTtc + $shippingTtc, 2);

        return $this->render('checkout/index.html.twig', [
            'cart'              => $cart,
            'addresses'         => $adresses,
            'taxRate'           => $taxRate,
            'shippingFee'       => $shippingCts,  // cts
            'shippingMethod'    => $appliedMethod, // peut être null
            'shippingMethods'   => $methods,
            'selectedMethodId'  => $appliedMethod?->getId(),
            'subTotalCts'       => $subTotalCts,          
            'totalWithShipCts'      =>  $totalWithShipCts,
            'totals'            => [
                'sub_ht'    => $subTotalHt,
                'sub_tva'   => $subTva,
                'ship_ht'   => $shippingHt,
                'ship_tva'  => $shippingTva,
                'total_ht'  => $totalHt,
                'total_tva' => $totalTva,
                'total_ttc' => $totalTtc,
            ],
        ]);
    }

    #[Route('/place', name: 'place', methods: ['POST'])]
    public function place(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Veuillez vous connecter pour continuer.');
            return $this->redirectToRoute('app_login');
        }

        $cart = $this->cartManager->getCurrentCart();
        if (!$cart || $cart->getCartItems()->isEmpty()) {
            $this->addFlash('info', 'Votre panier est vide.');
            return $this->redirectToRoute('cart_page');
        }

        /** @var Customer|null $customer */
        $customer = $this->em->getRepository(Customer::class)->findOneBy(['user' => $user]);
        if (!$customer) {
            $customer = new Customer();
            $customer->setUser($user);
            $this->em->persist($customer);
            $this->em->flush();
        }

        // Validation adresses stricte
        $billingSame = $request->request->get('billing_same_as_shipping', '1') === '1';
        if (
            !$this->isAddressBlockValid($request, 'shipping') ||
            (!$billingSame && !$this->isAddressBlockValid($request, 'billing'))
        ) {
            $this->addFlash('danger', 'Veuillez sélectionner ou saisir une adresse valide.');
            return $this->redirectToRoute('app_checkout_index');
        }

        // === ShippingMethod choisi (depuis POST ou session) ===
        $methods  = $this->em->getRepository(ShippingMethod::class)->findBy(
            $this->hasProperty(ShippingMethod::class, 'enabled') ? ['enabled' => true] : [],
            ['name' => 'ASC']
        );
        $selected = $this->resolveSelectedMethod($request, $methods); // persiste en session aussi

        $subTotalCts = (int) $cart->getTotal();
        [$shippingCts, $appliedMethod] = $this->computeShippingFor($subTotalCts, $selected);

        $taxCts   = 0;
        $grandCts = $subTotalCts + $shippingCts + $taxCts;

        // Commande (centimes)
        $order = (new Order())
            ->setNumber($this->generateNumber())
            ->setStatus('pending')
            ->setGrandTotal($grandCts)
            ->setSubtotal($subTotalCts)
            ->setShippingTotal($shippingCts)
            ->setTaxTotal($taxCts)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setCustomer($customer);

        // Snapshot du mode d’expédition sélectionné
        if ($appliedMethod) {
            $order->setShippingMethod($appliedMethod);
            if (method_exists($appliedMethod, 'getName')) {
                $order->setShippingMethodName($appliedMethod->getName());
            }
            // Code technique prioritaire: code, sinon carrierCode
            $code = null;
            if (method_exists($appliedMethod, 'getCode')) {
                $code = $appliedMethod->getCode();
            } elseif (method_exists($appliedMethod, 'getCarrierCode')) {
                $code = $appliedMethod->getCarrierCode();
            }
            $order->setShippingMethodCode($code);
        }

        // Adresses
        $shippingAddress = $this->resolveAdresseFromRequest($request, 'shipping', $user);
        $billingAddress  = $billingSame ? $shippingAddress : $this->resolveAdresseFromRequest($request, 'billing', $user);
        if (!$shippingAddress || !$billingAddress) {
            $this->addFlash('danger', 'Veuillez sélectionner des adresses valides.');
            return $this->redirectToRoute('app_checkout_index');
        }
        $order->setShippingAddress($shippingAddress);
        $order->setBillingAddress($billingAddress);

        $this->em->beginTransaction();
        try {
            $this->em->persist($order);

            $itemsCount = 0;
            foreach ($cart->getCartItems() as $ci) {
                $variant = $ci->getProductVariant();
                if (!$variant) {
                    throw new \RuntimeException('Article sans variante, impossible de créer la commande.');
                }

                $qty     = (int) $ci->getQuantity();
                $unitCts = (int) $ci->getUnitPrice(); // centimes

                if ($unitCts <= 0 || $qty <= 0) {
                    throw new \RuntimeException('Article invalide pour la commande.');
                }

                $item = (new OrderItem())
                    ->setOrders($order)
                    ->setProductVariant($variant)
                    ->setQuantity($qty)
                    ->setUnitPrice($unitCts)
                    ->setTotalPrice($unitCts * $qty);

                $this->em->persist($item);
                $order->getOrderItems()->add($item);
                $itemsCount++;
            }

            if ($itemsCount === 0) {
                throw new \RuntimeException('La commande ne contient aucune ligne.');
            }

            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            $this->logger->error('Order creation failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $this->addFlash('danger', 'Impossible de créer la commande : ' . $e->getMessage());
            return $this->redirectToRoute('app_checkout_index');
        }

        // Stripe Checkout Session
        try {
            $this->initStripe();

            $currency = strtolower($order->getCurrency() ?? 'EUR');

            $lineItems = [];
            foreach ($order->getOrderItems() as $it) {
                $name = (string) ($it->getProductVariant()?->getProduct()?->getName() ?? 'Article');
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => $currency,
                        'product_data' => ['name' => $name],
                        'unit_amount'  => $it->getUnitPrice(), // cts
                    ],
                    'quantity' => $it->getQuantity(),
                ];
            }

            if ($order->getShippingTotal() > 0) {
                $shipName = $order->getShippingMethodName() ?: 'Frais de port';
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => $currency,
                        'product_data' => ['name' => $shipName],
                        'unit_amount'  => $order->getShippingTotal(), // cts
                    ],
                    'quantity' => 1,
                ];
            }

            $successUrl = $this->generateUrl('app_checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL)
                . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl  = $this->generateUrl('app_checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $session = StripeSession::create([
                'mode'                => 'payment',
                'line_items'          => $lineItems,
                'success_url'         => $successUrl,
                'cancel_url'          => $cancelUrl,
                'client_reference_id' => $order->getNumber(),
                'metadata'            => [
                    'order_id' => (string) $order->getId(),
                    'number'   => (string) $order->getNumber(),
                ],
            ]);
            $order->setStripeSessionId($session->id);
            $this->em->flush();

            return $this->redirect($session->url, 303);
        } catch (ApiErrorException $e) {
            $this->addFlash('danger', 'Erreur Stripe : ' . $e->getMessage());
            return $this->redirectToRoute('app_checkout_index');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Impossible d’ouvrir la page de paiement.');
            return $this->redirectToRoute('app_checkout_index');
        }
    }

    #[Route('/confirmation/{id}', name: 'confirm', methods: ['GET'])]
    public function confirm(Order $order, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user || $order->getCustomer()?->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $sessionId = (string) $request->query->get('session_id', '');
        if ($sessionId === '') {
            return $this->render('checkout/confirm.html.twig', [
                'order'        => $order,
                'paid'         => false,
                'paymentError' => 'Identifiant de session Stripe manquant.',
            ]);
        }

        try {
            $this->initStripe();
            $cs = \Stripe\Checkout\Session::retrieve($sessionId);

            $isPaid      = ($cs->payment_status ?? null) === 'paid';
            $amountCts   = (int) ($cs->amount_total ?? 0);
            $expectedCts = (int) $order->getGrandTotal();
            $amountOk    = ($amountCts === $expectedCts);

            if ($isPaid && $amountOk) {
                if ($order->getStatus() !== 'paid') {
                    $order->setStatus('paid');

                    $payment = new Payment();
                    $payment
                        ->setAmount((int) $order->getGrandTotal()) // cts
                        ->setStatus('succeeded')
                        ->setPaidAt(new \DateTimeImmutable())
                        ->setOrders($order);

                    $this->em->persist($payment);
                    $this->em->flush();
                }

                $this->cartManager->clear();

                return $this->render('checkout/confirm.html.twig', [
                    'order' => $order,
                    'paid'  => true,
                ]);
            }

            return $this->render('checkout/confirm.html.twig', [
                'order'        => $order,
                'paid'         => false,
                'paymentError' => $isPaid ? 'Montant non concordant.' : "Le paiement n'est pas finalisé.",
            ]);
        } catch (ApiErrorException $e) {
            return $this->render('checkout/confirm.html.twig', [
                'order'        => $order,
                'paid'         => false,
                'paymentError' => 'Erreur Stripe : ' . $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            return $this->render('checkout/confirm.html.twig', [
                'order'        => $order,
                'paid'         => false,
                'paymentError' => 'Une erreur est survenue lors de la vérification du paiement.',
            ]);
        }
    }

    /* ===================== Helpers ===================== */

    private function initStripe(): void
    {
        $key = $_ENV['STRIPE_SECRET_KEY'] ?? $this->getParameter('stripe.secret_key') ?? null;
        if (!$key) {
            throw new \RuntimeException('Clé Stripe secrète manquante.');
        }
        Stripe::setApiKey($key);
    }

    private function eurToCents(float $eur): int
    {
        return (int) round($eur * 100);
    }

    private function generateNumber(): string
    {
        return sprintf('ORD-%s-%s', date('Ymd'), substr(strtoupper(bin2hex(random_bytes(3))), 0, 6));
    }

    private function isAddressBlockValid(Request $request, string $kind): bool
    {
        $id = (string) $request->request->get("{$kind}_address_id", '');
        if ($id !== '') {
            return true;
        }

        $line1 = trim((string) $request->request->get("{$kind}_line1", ''));
        $city  = trim((string) $request->request->get("{$kind}_city", ''));

        $context = (string) $request->request->get('context', '');
        if ($line1 === '' && $context === $kind) {
            $line1 = trim((string) $request->request->get('line1', ''));
            $city  = trim((string) $request->request->get('city', ''));
        }

        return $line1 !== '' && $city !== '';
    }

    private function resolveAdresseFromRequest(Request $request, string $kind, $user): ?Adresse
    {
        $id = $request->request->get("{$kind}_address_id");
        if (!empty($id)) {
            /** @var Adresse|null $addr */
            $addr = $this->em->getRepository(Adresse::class)->find((int) $id);
            if ($addr) {
                if (method_exists($addr, 'getUser') && $addr->getUser() !== $user) {
                    throw $this->createAccessDeniedException();
                }
                return $addr;
            }
        }

        $line1 = trim((string) $request->request->get("{$kind}_line1", ''));
        $city  = trim((string) $request->request->get("{$kind}_city", ''));

        $context = (string) $request->request->get('context', '');
        if ($line1 === '' && $context === $kind) {
            $line1 = trim((string) $request->request->get('line1', ''));
            $city  = trim((string) $request->request->get('city', ''));
        }

        if ($line1 === '' || $city === '') {
            return null;
        }

        $addr = new Adresse();
        $addr->setLine1($line1);
        $addr->setLine2($request->request->get("{$kind}_line2") ?? $request->request->get('line2'));
        $addr->setPostalCode($request->request->get("{$kind}_postalCode") ?? $request->request->get('postalCode'));
        $addr->setCity($city);
        $addr->setCountry($request->request->get("{$kind}_country") ?? $request->request->get('country'));
        $addr->setPhone($request->request->get("{$kind}_phone") ?? $request->request->get('phone'));

        if (method_exists($addr, 'setUser')) {
            $addr->setUser($user);
        }

        $this->em->persist($addr);
        $this->em->flush();

        return $addr;
    }

    /** Retourne [shippingCts, appliedMethod] avec fallback Settings si aucun mode. */
    private function computeShippingFor(int $subtotalCts, ?ShippingMethod $method): array
    {
        if ($method) {
            $base = $this->getBaseAmountCts($method);
            $threshold = $this->getFreeThresholdCts($method);
            $ship = ($threshold !== null && $subtotalCts >= $threshold) ? 0 : $base;
            return [$ship, $method];
        }

        // Fallback (compat réglages globaux)
        $shippingCts   = $this->eurToCents((float) ($this->settings->getShippingFee() ?? 0.0));
        $freeThreshEu  = $this->settings->getFreeShippingThreshold();
        $freeThreshCts = $freeThreshEu !== null ? $this->eurToCents((float) $freeThreshEu) : null;
        if ($freeThreshCts !== null && $subtotalCts >= $freeThreshCts) {
            $shippingCts = 0;
        }
        return [$shippingCts, null];
    }

    /** Base en cts : supporte basePrice ou baseCost. */
    private function getBaseAmountCts(ShippingMethod $m): int
    {
        if (method_exists($m, 'getBasePrice')) {
            return (int) $m->getBaseCost();
        }
        if (method_exists($m, 'getBaseCost')) {
            return (int) $m->getBaseCost();
        }
        return 0;
    }

    /** Seuil franco en cts (nullable). */
    private function getFreeThresholdCts(ShippingMethod $m): ?int
    {
        return method_exists($m, 'getFreeShippingThreshold') ? $m->getFreeShippingThreshold() : null;
    }

    /** Résout la sélection (GET ?sm=..., POST shipping_method_id, ou session), et persiste en session. */
    private function resolveSelectedMethod(Request $request, array $methods): ?ShippingMethod
    {
        if (empty($methods)) {
            return null;
        }

        $session = $request->getSession();

        // 1) POST (formulaire checkout)
        $postId = $request->request->getInt('shipping_method_id', 0);
        if ($postId > 0) {
            $found = $this->findMethodById($methods, $postId);
            if ($found) {
                $session?->set(self::SESSION_SM_ID, $postId);
                return $found;
            }
        }

        // 2) GET (?sm=ID) pour pré-sélection
        $getId = $request->query->getInt('sm', 0);
        if ($getId > 0) {
            $found = $this->findMethodById($methods, $getId);
            if ($found) {
                $session?->set(self::SESSION_SM_ID, $getId);
                return $found;
            }
        }

        // 3) Session
        $sid = (int) ($session?->get(self::SESSION_SM_ID) ?? 0);
        if ($sid > 0) {
            $found = $this->findMethodById($methods, $sid);
            if ($found) {
                return $found;
            }
        }

        // 4) Fallback: premier mode
        $first = $methods[0] ?? null;
        if ($first) {
            $session?->set(self::SESSION_SM_ID, $first->getId());
        }
        return $first;
    }

    private function findMethodById(array $methods, int $id): ?ShippingMethod
    {
        foreach ($methods as $m) {
            if ((int) $m->getId() === $id) {
                return $m;
            }
        }
        return null;
    }

    private function hasProperty(string $class, string $prop): bool
    {
        return property_exists($class, $prop);
    }
}


Sécurisation Stripe Checkout – état au 21/11/2025
✅ Audit sécurité Stripe Checkout (Front + Back)

Confirmé que le front ne manipule aucun montant sensible (prix/total non transmis depuis Twig/JS).

Confirmé que tous les montants Stripe sont recalculés côté backend à partir du panier (Money en centimes).

État : implémentation actuelle jugée sécurisée pour la gestion des montants.

# Spécifications techniques — Checkout Stripe & Commande

## Contexte

* **Stack** : Symfony 7 (PHP 8.2), Doctrine ORM, MySQL, Twig.
* **Modèle prix** : tous les montants métier sont en **centimes (int)**.
* **Frontend** : Twig affiche en euros via division par 100.

---

## Flux de paiement Stripe (mise à jour)

1. **Création de commande** (`POST /checkout/place`)

   * Calcule les totaux **en centimes** :

     * `Order.subtotal` = somme des `CartItem`.
     * `Order.shippingTotal` = frais de port (centimes), remise à 0 si seuil de gratuité atteint.
     * `Order.taxTotal` = 0 si prix catalogue TTC.
     * `Order.grandTotal` = `subtotal + shipping + tax`.
   * **Adresses obligatoires** : shipping & billing (validation stricte).
   * **Lignes** : crée des `OrderItem` (unitPrice/totalPrice en **centimes**, variant **non null**).
   * **Persistance** : `Order` + `OrderItem` dans une transaction ; logs détaillés en cas d’échec.
2. **Session Stripe**

   * `line_items[].price_data.unit_amount` en **centimes**.
   * Ajoute une ligne “Frais de port” si `shippingTotal > 0` (centimes).
   * `success_url = /checkout/success?session_id={CHECKOUT_SESSION_ID}` (aucune donnée sensible).
   * `cancel_url = /checkout/cancel`.
   * `client_reference_id = Order.number` (secours).
   * `metadata = { order_id, number }`.
   * `Order.stripeSessionId = $session->id` (stocké puis flush).
3. **Redirection succès** (`GET /checkout/success`)

   * Gérée par **CheckoutSuccessController** avec **CheckoutConfirmationService** :

     * Récupère la session via `session_id`.
     * Retrouve la commande (`stripeSessionId` puis fallback `client_reference_id`).
     * Vérifie `payment_status == 'paid'` **et** `amount_total == grandTotal` (centimes).
     * **Vide le panier** via un service dédié (pas de logique sensible dans le contrôleur).
     * Affiche une page de confirmation (`templates/checkout/success.html.twig`).
   * ⚠️ **Source de vérité recommandée** pour marquer “paid” : **webhook Stripe** (la page succès reste en lecture/vérif + clear cart).
4. **Annulation** (`GET /checkout/cancel`)

   * Flash info et redirection panier.

---

## Routes ajoutées / modifiées

* `POST /checkout/place` → `app_checkout_place`
* `GET  /checkout/success` → `app_checkout_success` (nouveau contrôleur **CheckoutSuccessController**)
* `GET  /checkout/cancel`  → `app_checkout_cancel` (CheckoutSuccessController)
* `GET  /account/orders`   → `account_orders` (liste des commandes du user)

---

## Services (nouveaux)

* `App\Service\Checkout\CheckoutConfirmationService`

  * Méthode `handleSuccessRedirect(string $sessionId, UserInterface $user): SuccessViewModel`
  * Rôles : lire la session Stripe, retrouver la commande, comparer les montants, **vider le panier**, fournir un VM pour Twig.
* `App\Service\Checkout\CartClearer`

  * Encapsule `CartManager::clear()` (testable, réutilisable).
* `App\Service\Checkout\MoneyFormatter`

  * `eurCents(int $cts): string` (ex. `1234` → `12,34 €`).

**DI Stripe :**

```yaml
# config/services.yaml
services:
  Stripe\StripeClient:
    arguments:
      - '%env(STRIPE_SECRET_KEY)%'     # ou $config: { api_key: '%env(STRIPE_SECRET_KEY)%' }
```

---

## Contrôleurs (résumé)

* **CheckoutController**

  * `index()` : affichage récap panier + adresses, calculs affichage (euros).
  * `place()` :

    * Patches appliqués :

      * **A** Totaux **centimes** + setters centimes dans `Order`.
      * **B** Ligne “Frais de port” Stripe **en centimes**.
      * **C** Validation d’adresse **stricte** (+ contrôle après résolution).
      * **D** **Logging** détaillé (PSR logger) avec message d’erreur dans flash en dev.
    * `success_url`/`cancel_url` pointent vers **CheckoutSuccessController**.
  * (Legacy) `confirm()` : garde-fou existant ; conseillé de le retirer une fois le **webhook** en place.
* **CheckoutSuccessController**

  * `success()` : utilise `CheckoutConfirmationService` et rend `checkout/success.html.twig`.
  * `cancel()` : flash + redirection panier.

---

## Entités & Invariants

* **Order**

  * `number` (format `ORD-YYYYMMDD-XXXXXX`).
  * `subtotal`, `shippingTotal`, `taxTotal`, `grandTotal` : **int (centimes)**.
  * `shippingAddress`, `billingAddress` : **NOT NULL**.
  * `stripeSessionId` : `string|null` (ajouté si absent).
* **OrderItem**

  * `unitPrice`, `totalPrice` : **int (centimes)**.
  * `currency` : `VARCHAR(3)` défaut `'EUR'` (**ajout DB effectué**).
  * `productVariant` : **NOT NULL**.
* **Payment** (si utilisé ici)

  * Recommandé : `amount` **int (centimes)** + `currency 'EUR'`.
* **Adresse ↔ Customer/User**

  * Décision prise : **Adresse → User** (on **supprime** la relation invalide `Customer#addresses` qui pointait vers un `Adresse#customer` inexistant).
  * Validation stricte des champs lors du checkout.

---

## Templates

* `templates/checkout/success.html.twig`

  * Variables utilisées : `paid`, `message`, `order` (ou `orderNumber`, `expectedTotal`, `status`).
  * Bouton “Voir mes commandes” → `{{ path('account_orders') }}`.
  * Fallbacks ajoutés pour éviter les erreurs Twig (ex. `|default`).
* `templates/account/orders.html.twig`

  * Liste simple des commandes du user connecté.

---

## Configuration

* `config/services.yaml`

  * Autowire/autoconfigure par défaut.
  * Enregistrement de `Stripe\StripeClient` via `STRIPE_SECRET_KEY`.
  * Suppression de l’ancienne injection manuelle `$stripePublicKey/$stripeCurrency` dans `CheckoutController`.
* **ENV requis**

  * `STRIPE_SECRET_KEY` (backend).
  * (Si besoin côté front) `STRIPE_PUBLIC_KEY` exposé via Twig globals.

---

## Migrations / Schéma

* **Ajouts/Corrections réalisées** :

  * Ajout colonne `currency VARCHAR(3) DEFAULT 'EUR'` sur `order_item` (et `payment` si mappé).
  * Suppression ou correction de la relation `Customer#addresses` (mapping invalide).
  * Vérif : `order.subtotal/shipping_total/tax_total/grand_total` en **INT**.
  * Vérif : `order_item.unit_price/total_price` en **INT**.
* Commandes utiles :

  * `symfony console doctrine:schema:validate`
  * `symfony console doctrine:migrations:diff`
  * `symfony console doctrine:migrations:migrate`

---

## Validation & Gestion d’erreurs

* **Guards** dans `place()` :

  * Au moins **1** `OrderItem`.
  * `productVariant` non nul pour chaque ligne.
  * `unitCts > 0` & `qty > 0`.
* **Adresses** : soit ID existant appartenant au user, soit `line1 + city` (strict).
* **Logs** (PSR) sur échec de transaction, message flash explicite en dev.
* **Stripe** : on ne passe **aucune donnée sensible** dans les URLs (uniquement `session_id`).

---

## Sécurité & Bonnes pratiques

* Le **webhook Stripe** doit rester la **source de vérité** pour passer une commande en `paid` + enregistrer `Payment` (la page succès ne fait qu’une vérif et vide le panier).
* Pas de `Stripe::setApiKey()` global dispersé ; on injecte **StripeClient**.
* Tous les montants exposés à Stripe sont des **centimes (int)**.
* `client_reference_id` et `metadata` permettent de recoller côté succès/webhook.

---

## TODO / pistes suivantes

* Implémenter le **webhook Stripe** (événements `checkout.session.completed` / `payment_intent.succeeded`) avec idempotence.
* Afficher détail de la commande post-paiement (page de détail `/account/orders/{number}`).
* Notif admin (EasyAdmin) sur `OrderPaid` (Event + Subscriber).
* Value Object `Money` et filtre Twig `money` pour éviter la division `/100` partout.
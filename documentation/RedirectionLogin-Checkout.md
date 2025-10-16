Datasets HTML pour relier Twig ↔ JS sans chercher le DOM.

1) Sécurité & redirection login
    - AppAuthenticator
        - Prend en compte un paramètre redirect (POST hidden + query) et vérifie qu’il est interne avant de rediriger.
        - Ordre de priorité :
        - redirect (POST → query → session),
        - TargetPathTrait (page protégée),
        - fallback app_home.

    - AppAuthenticator
        - Prend en compte un paramètre redirect (POST hidden + query) et vérifie qu’il est interne avant de rediriger.
        - Ordre de priorité :
        - redirect (POST → query → session),
        - TargetPathTrait (page protégée),
        - fallback app_home.

2) Cart (panier)
    - Routes JSON
        - GET /panier → état JSON du panier.
        - POST /panier → ajout d’un item (variant, qty).
        - PATCH /panier/item/{id} → mise à jour quantité.
        - GET /panier/panel → HTML du mini-panel (drawer) pour rechargement AJAX.

    - CartController
        - Sérialisation claire (centimes, image fallback).
        - panel() renvoie un fragment Twig pour rafraîchir le drawer après ajout/suppression.

    - Frontend (cart.js)
        - addToCart(variantId, qty) → POST /panier (JSON).
        - Mise à jour badge dynamique (data-notify / fallback <span id="cart-badge">).
        - Toast/message “Ajouté au panier” (non bloquant).
        - Reload du drawer via GET window.routes.panel.
        - Protection : bouton type="button" + dataset data-variant-id, data-qty-el.

    - Base layout
        - Injection d’un bloc routes JS :
<script>
  window.routes = {
    panel: "{{ path('cart_panel') }}",
    addToCart: "{{ path('cart_add') }}",
    showCart: "{{ path('cart_show') }}"
  };
</script>

3) Pages produits & home

    - ProductController
        - Route unique /product/{id} qui accepte id de variante ou id produit :
        - essai variant.id, sinon product.id + 1ʳᵉ variante.
        - Passe au Twig : product, variant, currentVariant, variants.

    - Twig product/show.html.twig
        - Normalisation des variables (currentVariant, p = currentVariant.product).
        - Bouton “Ajouter au panier” : data-variant-id="{{ currentVariant.id }}" + type="button".

4) Checkout (TVA, port, total)

    - CheckoutController
        - Récupère le taux TVA depuis SettingService/TaxSetting (getTva()).
        - Gère les ShippingMethod actives (fallback réglages globaux).
        - Méthode centrale computeShippingFor(subtotalCts, method) :
        - Base en centimes (supporte basePrice ou baseCost),
        - Franco (livraison offerte) via freeShippingThreshold.
        - Calcule HT/TVA/TTC en centimes côté serveur puis formate en € à l’affichage.
        - Persistance du mode d’expédition choisi en session (shipping_method_id).

    - Twig checkout/index.html.twig
        - Résumé commande avec attributs data :
        - data-subtotal-cents="{{ subTotalCts }}",
        - data-tax-rate="{{ taxRateVal }}".
        - Radios d’expédition avec :
        - data-price-cents="{{ sm.basePrice ?? sm.baseCost ?? 0 }}",
        - data-free-threshold-cents="{{ sm.freeShippingThreshold ?? 0 }}".
        - Affiche “— Livraison offerte” si seuil atteint.

    - checkout.js
        - Écoute change sur .js-shipping.
        - Recalcule en live : TVA + port + total TTC depuis les data-*.
        - Garde les id/classes existants du Twig (js-subtotal, os-tva, js-shipping, os-total, js-ship-note, etc.).

6) Bonnes pratiques appliquées
    - Tous les montants calculés côté serveur en centimes (précision, pas d’arrondis hasardeux).
    - Validation des redirections (anti open-redirect).
    - Séparation des responsabilités :
    - contrôleurs (métier & calcul),
    - Twig (affichage),
    - JS (UX dynamique).
    - Datasets HTML pour relier Twig ↔ JS sans chercher le DOM.
    - Placeholders d’images et gestion de onerror.
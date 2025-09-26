✅ Ce qui est déjà en place

- Authentification
- Panier & Checkout
- CartManager en session.
- CheckoutController
- Création Order + OrderItem dans une transaction Doctrine avec logs.
- Adresses obligatoires (shipping + billing).
- Session Stripe correctement configurée avec unit_amount en centimes.
- success_url / cancel_url sans fuite de données sensibles.
- Order.stripeSessionId stocké.
- CheckoutSuccessController + -     CheckoutConfirmationService.
- Vérification de la session Stripe (paid + amount_total == grandTotal).

Twig checkout/success.html.twig affichant la confirmation.

⚠️ Le webhook Stripe reste la source de vérité (reste à brancher pour Order.status = paid + créer Payment).

Entités

Order : montant en centimes, currency (EUR), adresses obligatoires, stripeSessionId.

OrderItem : quantity, unitPrice, totalPrice, currency.

Payment : prévu en centimes + currency EUR.

Adresse : attachée au User, relation invalide supprimée côté Customer.

Infra

Stripe\StripeClient injecté via services.yaml.

Log PSR en cas d’erreurs.
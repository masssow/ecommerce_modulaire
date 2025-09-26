=========================================⚠️ Points restants / améliorations à prévoir===========


Points restants / améliorations à prévoir

- Panier vidé via CartClearer.
Webhook Stripe

Implémenter un StripeWebhookController :

endpoint /stripe/webhook (protégé par la clé secrète webhook).

Événements à traiter : checkout.session.completed, payment_intent.succeeded, payment_intent.payment_failed.

Mise à jour Order.status et création Payment (centimes + currency).

Back-office (EasyAdmin)

CRUD Order avec affichage grandTotal en euros via MoneyFormatter.

CRUD Product/ProductVariant avec prix en centimes (édition → int en DB, affichage → euros).

CRUD Payment (lié à Order).

Notifications & mails

Email de confirmation après paiement validé (via Messenger ou EventSubscriber).

Notifications admin (nouvelle commande).

Tests

PHPUnit : unitaires pour CheckoutConfirmationService, CartManager, MoneyFormatter.

Tests fonctionnels sur /checkout/place, /checkout/success.




=======================Backlog / prochaines étapes souhaitées============================

- Gestion RGPD : consentements, politique de confidentialité, suppression compte.
- Vérification d’email (double opt-in) après inscrption
 Afficher détail de la commande post-paiement (page de détail /account/orders/{number}).

- Notif admin:  marquer l’Order paid, créer Payment, notifier l’admin. ( gestion directement depuis EasyAdmin) sur OrderPaid (Event + Subscriber).

- WebhookController et l’EventSubscriber OrderPaidSubscriber


- Value Object Money et filtre Twig money pour éviter la division /100 partout.


Page détail commande (/account/orders/{number}) + lien depuis la liste.

Notification EasyAdmin après paiement (event OrderPaid + subscriber).

Value Object Money + filtre Twig money (éviter /100 partout).

Shipping methods (choix transporteur & coût dynamique) stockés sur Order.

Mode de paiement (ex. paymentMethod sur Payment), statut, paymentIntentId.


============================================================
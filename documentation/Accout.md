1) Profil (affichage + modification)

Jalons

M1.1 Affichage : Nom, email, téléphone (si stocké), avatar (optionnel).

M1.2 Édition : formulaire (nom, téléphone), changement de mot de passe séparé.

M1.3 Confirmation UI (flash) + validation (CSRF, contraintes).

AC

Sauvegarde OK, affichage instantané des données mises à jour.

Mot de passe : champs (ancien, nouveau, confirmation), règles minimales.

Symfony/Twig

Routes : account_profile_show, account_profile_edit, account_password_edit.

Forms : ProfileType, ChangePasswordType.

Entité utilisée : User (déjà en place, mdp hashé via Symfony Security).

2) Adresses (CRUD + défauts)

Jalons

M2.1 Listing : adresses de l’utilisateur (livraison/facturation), badges Défaut.

M2.2 Ajout / édition / suppression (confirm).

M2.3 Sélection des défauts (livraison & facturation).

AC

Minimum 1 adresse possible ; suppression bloquée si utilisée dans commande en cours (optionnel).

Champs : prénom, nom, ligne1, ligne2, ville, CP, pays, téléphone, type (billing/shipping), is_default_shipping, is_default_billing.

Symfony/Twig

Suggestion d’entité (si non existante) Address:
id, user, firstName, lastName, line1, line2, city, postalCode, country, phone, type, isDefaultShipping, isDefaultBilling, createdAt.

Routes : account_addresses_index/create/edit/delete/set-default.

Intégration checkout plus tard : préremplir depuis Address par défaut.

3) Commandes & Retours (suivi + RMA)

Jalons

M3.1 Listing des commandes : N°, date, total, statut (payée, en préparation, expédiée, livrée), CTA “Détails”.

M3.2 Détail commande : items (images, titres), quantités, prix, tracking (transporteur + n°), adresses, paiements.

M3.3 Bouton “Retourner” visible uniquement si status == Livrée et dans la fenêtre (ex: 14 jours).
→ Lance un RMA : sélection des articles/quantités, motif, commentaire.

M3.4 Page “Mes retours” (optionnel) : statut (Demandé, Approuvé, Reçu, Remboursé/Refusé).

AC

Timeline claire (icônes) : Commandée → Payée → Expédiée → Livrée.

Règles retour configurables (p.ex. J+14).

Après demande de retour : confirmation, mail (optionnel), statut commande mis à jour (ex: “Retour en cours”).

Symfony/Twig

Entités existantes : Order, Shipment, Payment (OK).

Nouvelles entités proposées :

ReturnRequest: id, user, order, number, status, reason, comment, createdAt.

ReturnItem: id, returnRequest, orderItem, qty.

Routes : account_orders_index, account_orders_show, account_returns_create, account_returns_show.

Règles : calculer éligibilité retour (date de livraison + fenêtre).

4) Moyens de paiement (Stripe prioritaire)

Jalons

M4.1 Listing des cartes enregistrées (brand, last4, exp, badge Défaut).

M4.2 Ajouter une carte via Stripe SetupIntent (SCA), sans quitter l’espace compte.

M4.3 Définir par défaut / Supprimer (detach) une carte.
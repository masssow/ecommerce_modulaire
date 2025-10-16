DATE : 26/08/2025
reprise 25/09/2025
- mettre en place register use

15/10/2025 :
    - redirection login
    - Checkout (TVA, port, total)
    - Route unique /product/{id} qui accepte id de variante ou id produit
    - Tous les montants calculés côté serveur en centimes (précision, pas d’arrondis hasardeux).
    - Validation des redirections (anti open-redirect).
    - Datasets HTML pour relier Twig ↔ JS sans chercher le DOM.
CI/CD
1) Arborescence & routage

Chemin sandbox :
/home/u791166429/domains/massgrafik.com/public_html/sandbox/ecommerce_modulaire

Sous-domaine : sandbox.massgrafik.com → pointe sur public_html/sandbox/

.htaccess à la racine de sandbox/ (redirige vers Symfony public/) :

            RewriteEngine On
RewriteRule ^$ ecommerce_modulaire/public/ [L]
RewriteRule ^(.*)$ ecommerce_modulaire/public/$1 [L,QSA]

<IfModule mod_headers.c>
  Header set X-Robots-Tag "noindex, nofollow, noarchive"
</IfModule>

Composer : auto-détection (Hostinger : /home/<user>/bin/composer), sinon $PHP /usr/bin/composer

Exemples :

=> /opt/alt/php82/usr/bin/php bin/console cache:clear --env=dev
=> /opt/alt/php82/usr/bin/php bin/console doctrine:mig


Workflow GitHub Actions — Deploy to Sandbox

Déclencheur : push sur developpement (et manuel).

Clé SSH : stockée dans secrets GitHub (SSH_PRIVATE_KEY PEM OpenSSH multi-lignes), écrite telle quelle dans ~/.ssh/id_ci (pas d’agent pour éviter l’erreur libcrypto).

Étapes principales :

Checkout

Write SSH key (permissions 600)

Génère deploy-sandbox.sh et scp → /tmp/ sur le serveur

Exécute le script via ssh

deploy-sandbox.sh (résumé des 6 étapes server-side)

Composer : détection/installation si besoin

Code : git fetch --prune && git reset --hard origin/developpement

Composer install (PHP 8.2)

Node 20 + build (nvm, npm ci/install, npm run build)

Patch migrations (supprime garde-fou MySQL84Platform)


doctrine:migrations:sync-metadata-storage

tente migrate; si erreur tables existent déjà, fait
doctrine:migrations:version --add --all puis retente migrate

Cache Symfony : cache:clear & cache:warmup

(Résout les cas de schéma existant mais métadonnées de migration manquantes, et rend le déploiement robuste.)

7) Sécurité sandbox

Désindexation (X-Robots-Tag: noindex, nofollow) côté racine et public/.

Optionnel : Basic Auth sur sandbox.massgrafik.com (ne gêne pas le workflow CI car il passe en SSH).



# Lancer les migrations (dev)
/opt/alt/php82/usr/bin/php bin/console doctrine:migrations:migrate --no-interaction --env=dev

# Réinitialiser la base (sandbox)
 /opt/alt/php82/usr/bin/php bin/console doctrine:schema:drop --force --full-database --env=dev

# Réparer les métadonnées de migrations
/opt/alt/php82/usr/bin/php bin/console doctrine:migrations:sync-metadata-storage --env=dev
/opt/alt/php82/usr/bin/php bin/console doctrine:migrations:version --add --all --no-interaction --env=dev

# Cache
/opt/alt/php82/usr/bin/php bin/console cache:clear --env=dev && \
/opt/alt/php82/usr/bin/php bin/console cache:warmup --env=dev

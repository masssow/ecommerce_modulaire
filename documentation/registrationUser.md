Inscription utilisateur — Spécifications techniques
Contexte

Stack : Symfony 7 (PHP 8.2), Doctrine ORM, Twig.

Domain model : User, Adresse (ManyToOne vers User), Customer (OneToOne ou ManyToOne vers User selon ton modèle).

Objectif : créer un compte avec email, prénom, nom, mot de passe, et adresse postale (champ unique “Adresse” + CP, Ville, Pays, Téléphone).

Sécurité : CSRF actif, cookies durcis, honeypot anti-bot, email normalisé avant validation. (Pas de rate limiter sur /register par choix.)

Routes & contrôleur

Route : GET|POST /register
Nom : app_register_index
Contrôleur : App\Controller\RegistrationController::register()

Logique de la méthode register()

Déjà connecté ? → redirection account_profile.

Afficher le formulaire (FormType : RegistrationFormType).

handleRequest() puis, si soumis :

Honeypot : si le champ hp est rempli, on ignore la soumission (redirection silencieuse, pas de création).

Normaliser l’email avant isValid() : strtolower(trim(email)) → met à jour :

le champ du formulaire (pour refléter la valeur normalisée),

l’entité User (pour que les contraintes UniqueEntity s’appliquent sur la valeur normalisée).

Si valide :

Hash du mot de passe (PasswordHasher, algo auto/bcrypt/Argon2id).

Création Adresse :

Adresse.line1 = addr_address (champ unique multi-lignes),

autres champs : postalCode, city, country, phone,

lier l’adresse au User ($adresse->setUser($user)).

Création Customer (si attendu par le modèle) et liaison setUser($user).

Persist : User, Customer, Adresse ; flush().

Auto-login via Security::login($user).

Redirection account_profile + flash succès.

Sinon, afficher le formulaire avec erreurs.

Formulaire (RegistrationFormType)

Champs mappés (User) :

email (EmailType) — contraintes : NotBlank, Email, Length ≤ 180

firstName (TextType) — NotBlank, Length ≤ 100

lastName (TextType) — NotBlank, Length ≤ 100

plainPassword (RepeatedType/PasswordType, mapped=false) — NotBlank, Length ≥ 8

Champs non mappés (Adresse) :

addr_address (TextareaType) — NotBlank, Length ≤ 255

addr_postalCode (TextType) — NotBlank, Length ≤ 20

addr_city (TextType) — NotBlank, Length ≤ 100

addr_country (TextType) — NotBlank, Length ≤ 100

addr_phone (TextType, optional) — Length ≤ 30

Anti-bot :

hp (TextType, mapped=false, caché via classe CSS) — si rempli ⇒ soumission ignorée.

CSRF : activé automatiquement via {{ form_start() }} (champ _token).

Template Twig (templates/security/register.html.twig)

Mise en page Bootstrap 5 (mobile-first) ; utilisation de form-floating.

Formulaire centré (suppression de la colonne d’info gauche).

{{ form_rest(form) }} pour rendre CSRF/honeypot.

Affiche les erreurs globales et par champ.

Entités & contraintes
User

email : #[ORM\Column(length:180, unique:true)] + #[UniqueEntity(fields:['email'])]

firstName, lastName : #[ORM\Column(length:100, nullable:true|false)] selon choix

password : string hashé

roles : json ; garanti ROLE_USER dans getRoles() :

public function getRoles(): array
{
    return array_unique([...$this->roles, 'ROLE_USER']);
}

Adresse

line1, postalCode, city, country, phone|null

Relation ManyToOne → User (adresse appartienne au user).

Customer

Lien vers User (OneToOne ou autre selon ton modèle). Créé à l’inscription.

Migrations : s’assurer que les colonnes existent et que User.email est unique.

Sécurité (plateforme)
CSRF & Cookies (déjà en place)

CSRF activé pour les formulaires Symfony.

Cookies session durcis (recommandé) :

framework:
  csrf_protection: true
  session:
    cookie_secure: auto      # true en prod HTTPS
    cookie_httponly: true
    cookie_samesite: 'lax'   # 'strict' si UX ok

Mots de passe

Config hasher (recommandé) :

security:
  password_hashers:
    App\Entity\User:
      algorithm: auto   # (force Argon2id si disponible)

Accès aux routes

Autoriser PUBLIC_ACCESS sur /register, /login ; protéger le reste :

security:
  access_control:
    - { path: ^/register, roles: PUBLIC_ACCESS }
    - { path: ^/login,    roles: PUBLIC_ACCESS }
    - { path: ^/,         roles: IS_AUTHENTICATED_REMEMBERED }

Anti-bot

Honeypot en place, vérifié uniquement si le formulaire est soumis.

Rate limiter : non déployé sur /register (décision projet).

Recommandation : activer login throttling sur /login plus tard.

Normalisation email

Avant validation : email = strtolower(trim(email))
⇒ cohérence avec UniqueEntity et en BDD.

Flux (vue d’ensemble)
[GET /register] -> Form affiché (token CSRF + champ hp caché)

[POST /register]
  ├─ Vérifier honeypot si soumis → si rempli: ignorer (redir. accueil)
  ├─ Normaliser email (trim + lowercase) AVANT validation
  ├─ Validation Form (User + champs adresse unmapped)
  ├─ Hasher mot de passe
  ├─ Créer Adresse (line1=addr_address) & lier User
  ├─ Créer Customer & lier User (si nécessaire)
  ├─ Persist & flush
  ├─ Auto-login
  └─ Redirect -> account_profile (flash succès)

Critères d’acceptation (tests manuels)

Form & CSRF

Le HTML contient un input _token.

POST sans _token → rejeté (erreur CSRF).

Honeypot

Soumettre en remplissant hp via devtools → aucun compte créé, redirection silencieuse.

Validation & normalisation

Email avec espaces/majuscules → stocké en minuscules.

UniqueEntity : second essai avec la même adresse (ou variantes de casse) → erreur “email déjà utilisé”.

Mot de passe < 8 chars → erreur.

Persistance

User créé, Adresse créée liée au User, Customer créé.

Auto-login (session active), redirection account_profile.

Sécurité

Cookie session a HttpOnly, Secure (en HTTPS), SameSite=Lax/Strict.

Points non couverts (backlog)

Vérification d’email (double opt-in) via lien signé.

Rate limiter / login throttling sur /login.

CSP / headers (NelmioSecurityBundle) pour durcir XSS/Clickjacking.

2FA (TOTP) pour comptes admin / actions sensibles.

Gestion RGPD : consentements, politique de confidentialité, suppression compte.

Dépendances utilisées

symfony/form, symfony/validator, symfony/security-bundle,

symfony/password-hasher, symfony/twig-bundle,

Doctrine ORM.
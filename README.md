# 🎮 Cloud Quizz

**Cloud Quizz** est une plateforme e-commerce combinant **quiz interactifs**, **forums communautaires** et **boutique en ligne**. Chaque quiz dispose de son propre espace d'échange, et les utilisateurs peuvent enrichir leur expérience en achetant des quiz/jeux supplémentaires via la boutique intégrée.

---

## 🚀 Fonctionnalités

- **Quiz interactifs** : système de questions/réponses avec suivi du score en temps réel (AJAX)
- **Forum par quiz/jeu** : espace de discussion dédié à chaque quiz
- **Boutique en ligne** : achat de quiz/jeux additionnels, gestion du panier
- **Paiement en ligne** : intégration Stripe pour les transactions sécurisées
- **Comptes utilisateurs** : inscription, connexion, gestion de profil, réinitialisation de mot de passe, vérification d'email
- **Système de rôles** : utilisateur, utilisateur premium, modérateur, administrateur (hiérarchie de rôles)
- **Mode maintenance** : bascule automatique de l'application via un listener dédié
- **Espace de gestion** : back-office Admin et Modérateur

---

## 🛠️ Stack technique

| Domaine | Technologies |
|---|---|
| Framework backend | Symfony 6.4, PHP 8.2 |
| ORM / Base de données | Doctrine ORM, Doctrine Migrations |
| Templating | Twig |
| Paiement | Stripe (`stripe/stripe-php`) |
| Emailing | Symfony Mailer, Google Mailer (SMTP Gmail en dev) |
| Authentification | Symfony Security Bundle (authenticator personnalisé) |
| Gestion de fichiers | FM Elfinder Bundle, CKEditor |
| Frontend / Interactivité | JavaScript (AJAX Fetch API), Particles.js |
| Pagination | KnpPaginatorBundle |
| Environnements | Docker (docker-compose) |
| Tests / Qualité | PHPUnit, PHPStan, PHP-CS-Fixer |

---

## 📁 Structure du projet

```
CLOUD_QUIZZ/
├── bin/                        # Exécutables (console Symfony)
├── config/                     # Configuration Symfony (services, security, packages)
├── migrations/                 # Migrations de base de données Doctrine
├── public/
│   ├── bundles/, css/, images/
│   ├── javascript/
│   │   ├── quizz.js            # Logique du quiz (AJAX, score, progression)
│   │   └── error.js            # Effets visuels des pages d'erreur
│   ├── tarteaucitron/          # Gestion des cookies
│   ├── uploads/                # Fichiers uploadés
│   └── index.php               # Point d'entrée de l'application
├── src/
│   ├── Classes/
│   │   └── Panier.php          # Logique métier du panier
│   ├── Controller/
│   │   ├── Admin/, Moderator/
│   │   ├── ContactController.php
│   │   ├── ForumController.php
│   │   ├── PagesController.php
│   │   ├── PanierController.php
│   │   ├── PaymentController.php
│   │   ├── RegistrationController.php
│   │   ├── ResetPasswordController.php
│   │   └── SecurityController.php
│   ├── Entity/                 # Entités Doctrine
│   ├── EventListener/
│   │   └── MaintenanceListener.php  # Bascule en mode maintenance
│   ├── Form/                   # Formulaires Symfony
│   ├── Repository/             # Repositories Doctrine
│   └── Kernel.php
├── templates/
│   ├── pages/, jeux_quizz/, panier/, payment/
│   ├── registration/, reset_password/, security/, user/
│   ├── maintenance/
│   └── base.html.twig
├── tests/                      # Tests PHPUnit
├── translations/                # Fichiers de traduction
├── docker-compose.yaml
├── composer.json
└── symfony.lock
```

---

## ⚙️ Installation

### Prérequis

- PHP >= 8.2 avec extensions `ctype`, `iconv`
- [Composer](https://getcomposer.org/)
- MySQL 8.0 (ou via Docker)
- [Symfony CLI](https://symfony.com/download) (recommandé)
- Docker & Docker Compose (optionnel, pour un environnement conteneurisé)

### Étapes

```bash
# Cloner le dépôt
git clone https://github.com/<votre-utilisateur>/Cloud-Quizz.git
cd Cloud-Quizz

# Installer les dépendances PHP
composer install

# Configurer les variables d'environnement
cp .env .env.local
# puis renseigner vos propres valeurs (base de données, mailer, Stripe, etc.)

# Créer la base de données et exécuter les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Lancer le serveur de développement
symfony server:start
# ou
php -S 127.0.0.1:8000 -t public
```

### Avec Docker

```bash
docker-compose up -d
```

---

## 🔐 Variables d'environnement

Le projet utilise le système `.env` de Symfony (`.env`, `.env.local`, `.env.test`). Les valeurs sensibles (`DATABASE_URL`, `MAILER_DSN`, clés Stripe, `APP_SECRET`) **ne doivent jamais être commitées** et doivent être définies uniquement dans `.env.local` (non versionné) ou via des variables d'environnement serveur en production.

---

## 🧪 Tests

```bash
php bin/phpunit
```

Analyse statique du code :
```bash
vendor/bin/phpstan analyse
```

---

## License

© 2026 Louis-Hervé N'Goma — Tous droits réservés.

Ce projet est publié à titre de démonstration dans le cadre de mon portfolio. Le code peut être consulté librement, mais aucune réutilisation, modification ou distribution n'est autorisée sans mon accord écrit. Voir le fichier [LICENSE](./LICENSE) pour plus de détails.

---

## Auteur

**Louis-Hervé N'Goma** — Développeur Full Stack
GitHub : [@HNGA91](https://github.com/HNGA91)
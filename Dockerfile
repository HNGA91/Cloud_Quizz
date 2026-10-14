# =============================================================================
# Image de production de Cloud Quizz (Symfony 6.4 / PHP 8.2 / Apache)
# Render construit cette image à chaque git push, puis la démarre.
# =============================================================================

# 1. Image de base officielle : Debian + PHP 8.2 + Apache déjà configurés ensemble
FROM php:8.2-apache

# 2. Extensions PHP nécessaires au projet
#    - pdo_mysql : connexion à MySQL / TiDB via Doctrine
#    - intl      : symfony/intl (formats de dates, devises…)
#    - zip       : utilisé par Composer pour décompresser les paquets
#    - opcache   : cache du code PHP compilé (performances en production)
#    Les paquets "-dev" fournissent les bibliothèques système requises pour compiler
#    ces extensions ; "rm -rf /var/lib/apt/lists/*" allège l'image finale.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install pdo_mysql intl zip opcache \
    && rm -rf /var/lib/apt/lists/*

# 3. Apache : module de réécriture d'URL + notre configuration de site
#    (racine sur public/, port fourni par Render via la variable PORT)
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf

# 4. Réglages PHP de production (fichier fourni par l'image officielle)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# 5. Composer, copié depuis son image officielle (pas d'installation manuelle)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 6. Variables d'environnement par défaut de l'image
#    PORT=10000 : valeur par défaut de Render (il peut la remplacer au démarrage)
ENV APP_ENV=prod \
    APP_DEBUG=0 \
    PORT=10000 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# 7. Dépendances PHP d'abord, code ensuite : tant que composer.json/composer.lock
#    ne changent pas, Docker réutilise cette étape en cache → builds plus rapides.
#    --no-dev : sans les outils de développement (profiler, maker, phpunit…)
#    --no-scripts : les commandes Symfony seront lancées une fois le code copié
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# 8. Copie du code (filtré par .dockerignore : pas de .env.local, vendor, var…)
COPY . .

# 9. Finalisation :
#    - autoloader optimisé (classmap) pour la production
#    - assets:install : copie les assets publics des bundles dans public/bundles
#    - cache:warmup : prépare le cache Symfony pendant le build, pas au 1er visiteur
#    - chown : Apache tourne avec l'utilisateur www-data, qui doit pouvoir écrire
#      dans var/ (cache, logs)
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && php bin/console assets:install public --no-interaction \
    && php bin/console cache:warmup \
    && chown -R www-data:www-data var

# 10. Démarrage : Apache au premier plan (le conteneur vit tant qu'Apache tourne)
CMD ["apache2-foreground"]

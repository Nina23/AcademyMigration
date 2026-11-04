# Test-server image (docker-compose.test-server.yml, docker/test-server/README.md).
# Same PHP 8.4 / Apache base and the same PHP extensions as the dev image (docker/php/Dockerfile),
# but the code is baked into the image (no bind mount) and only the production dependencies are
# installed: composer install --no-dev from the committed composer.lock (Debugbar, Ignition,
# PHPUnit etc. are not in the image). No npm: the built assets in public/ come from git.
FROM php:8.4-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        xml \
        bcmath \
        curl \
        zip \
        gd \
        intl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Composer >= 2.7 for installs from the lock (UPGRADE_PLAN O5).
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
# Apache prefork limits that fit the 1 GB memory limit of the container (test server only).
COPY docker/test-server/mpm_prefork.conf /etc/apache2/mods-available/mpm_prefork.conf

WORKDIR /var/www/html

# Dependencies first (cached layer): the lock, the path repositories (packages/*/*) they point to.
COPY composer.json composer.lock ./
COPY packages ./packages
# TypiCMS/Laravel-Sidebar is a public VCS repository: clone it over HTTPS.
RUN git config --global url."https://github.com/".insteadOf "git@github.com:" \
    && composer config --global github-protocols https \
    && composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

# Application code (see Dockerfile.dockerignore for what is left out).
COPY . .

# Runtime directories (storage is a named volume; Docker copies this layout into it when the volume
# is created). They must exist before artisan runs (the view compiler needs its cache path).
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache

# Autoloader and package discovery for the --no-dev set. The discovery cache is generated here,
# so bootstrap/cache never holds the dev packages (O6: stale packages.php / services.php).
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && composer dump-autoload --no-dev --optimize --no-interaction --no-scripts \
    && php artisan package:discover --ansi \
    && test ! -d vendor/barryvdh/laravel-debugbar \
    && ! grep -q Debugbar bootstrap/cache/packages.php bootstrap/cache/services.php \
    && test -f public/js/admin.js && test -f public/js/public.js \
    && test -f public/css/admin.css && test -f public/css/public.css

# Owned by the Apache user: artisan runs as www-data too (UPGRADE_PLAN R24).
RUN chown -R www-data:www-data storage bootstrap/cache

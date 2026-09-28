FROM php:7.4-apache

RUN sed -i '/bullseye-security/d' /etc/apt/sources.list \
    && sed -i '/bullseye-updates/d' /etc/apt/sources.list \
    && sed -i 's|deb.debian.org/debian|archive.debian.org/debian|g' /etc/apt/sources.list \
    && echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until \
    && apt-get update \
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

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
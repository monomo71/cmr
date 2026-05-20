FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --ignore-platform-req=ext-gd

FROM php:8.3-apache

ARG APP_VERSION=1.0.1
LABEL org.opencontainers.image.title="CMR Invultool"
LABEL org.opencontainers.image.version=$APP_VERSION

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    curl \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libsqlite3-dev \
    pkg-config \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install gd pdo pdo_mysql pdo_sqlite \
  && a2enmod rewrite headers expires \
  && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
  && a2enconf servername \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html
COPY --from=vendor /app/vendor /var/www/html/vendor

RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' \
  /etc/apache2/sites-available/*.conf \
  /etc/apache2/apache2.conf \
  /etc/apache2/conf-available/*.conf

RUN mkdir -p /var/www/html/data /var/www/html/storage/generated \
  && chown -R www-data:www-data /var/www/html/data /var/www/html/storage/generated

EXPOSE 80

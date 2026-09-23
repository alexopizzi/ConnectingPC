# Immagine di sviluppo: replica le caratteristiche rilevanti della produzione Aruba
# (PHP 8.3, estensioni verificate il 2026-09-23). In produzione PHP gira in FastCGI.
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
      unzip git \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql intl gd zip exif opcache bcmath \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-connectingpc.ini
COPY docker/php/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php/entrypoint.sh /usr/local/bin/connectingpc-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/connectingpc-entrypoint \
 && chmod +x /usr/local/bin/connectingpc-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["connectingpc-entrypoint"]
CMD ["apache2-foreground"]

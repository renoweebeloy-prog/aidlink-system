# Paggamit og official PHP image nga naay Apache
FROM php:8.2-apache

# I-install ang PostgreSQL driver dependencies
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# I-enable ang mod_rewrite para sa routing
RUN a2enmod rewrite

# I-copy ang tanang files sa imong project padulong sa server
COPY . /var/www/html/

# Hatagi og sakto nga permissions ang folder
RUN chown -R www-data:www-data /var/www/html

# I-expose ang port 80 para makita sa internet
EXPOSE 80

# Paggamit og official PHP image nga naay Apache
FROM php:8.2-apache

# I-install ang PostgreSQL driver dependencies
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# I-enable ang mod_rewrite para sa routing
RUN a2enmod rewrite

# I-copy ang TANANG files padulong sa Docker container
COPY . /var/www/html/

# FIX: Hatagi og saktong PERMISSIONS aron mawala ang "403 Forbidden"
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# FIX: I-set ang 'public' folder isip main website folder (Document Root)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# I-expose ang port 80
EXPOSE 80

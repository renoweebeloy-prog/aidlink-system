# Paggamit og official PHP image nga naay Apache
FROM php:8.2-apache

# I-install ang PostgreSQL driver dependencies
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# I-enable ang mod_rewrite
RUN a2enmod rewrite

# I-copy ang TANANG files sa imong project
COPY . /var/www/html/

# I-SET ANG PUBLIC FOLDER NGA MAOY MAIN FOLDER (DOCUMENT ROOT)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Hatagi og sakto nga permissions ang folder
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

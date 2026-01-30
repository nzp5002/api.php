FROM php:8.2-apache

# Remove QUALQUER outro MPM e força prefork
RUN a2dismod mpm_event mpm_worker || true
RUN a2enmod mpm_prefork

# PHP + MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Apache configs
RUN a2enmod rewrite

# Railway usa PORT dinâmica
RUN sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf \
    /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE ${PORT}

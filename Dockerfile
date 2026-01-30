FROM php:8.2-apache

# Garante que só um MPM fique ativo
RUN a2dismod mpm_event mpm_worker || true
RUN a2enmod mpm_prefork

RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite

# Railway PORT
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE ${PORT}

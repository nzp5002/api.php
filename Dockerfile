FROM php:8.2-apache

# REMOVE QUALQUER MPM EXISTENTE
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load || true
RUN rm -f /etc/apache2/mods-enabled/mpm_*.conf || true

# ATIVA APENAS PREFORK
RUN a2dismod mpm_prefork

# PHP + MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Apache básico
RUN a2enmod rewrite

# Porta dinâmica do Railway
RUN sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf \
    /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE ${PORT}

FROM php:8.2-apache

# Ativa mysqli
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Ativa rewrite (opcional)
RUN a2enmod rewrite

EXPOSE 8080

CMD ["apache2-foreground"]

FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2dismod mpm_event && a2enmod mpm_prefork rewrite
COPY .. /var/www/html/
EXPOSE 80
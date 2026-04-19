FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN rm -f /var/www/html/index.html
COPY .. /var/www/html/
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html
EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]
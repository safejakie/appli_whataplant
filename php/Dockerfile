FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    libapache2-mod-php8.1 \
    && rm -rf /var/lib/apt/lists/*

COPY .. /var/www/html/
RUN rm -f /var/www/html/index.html.default
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

RUN a2enmod rewrite php8.1

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]
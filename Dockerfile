FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    libapache2-mod-php8.1 \
    python3.11 \
    python3-pip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Supprime la page par défaut Ubuntu
RUN rm -rf /var/www/html/*

# Copie tout le projet
COPY . /var/www/html/

# Configure Apache
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN a2enmod rewrite php8.1

# Installe les dépendances Python
RUN pip3 install -r /var/www/html/requirements.txt

# Configure supervisor
RUN echo "[supervisord]\nnodaemon=true\n\
[program:apache2]\ncommand=apache2ctl -D FOREGROUND\n\
[program:fastapi]\ncommand=uvicorn agent:app --host 0.0.0.0 --port 8000 --app-dir /var/www/html" \
> /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80 8000

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
FROM php:8.2-apache

# Installer les dépendances système et Python
RUN apt-get update && apt-get install -y \
    supervisor wget python3 python3-pip \
    && apt-get clean

# Copier et installer les dépendances Python
COPY requirements.txt /tmp/requirements.txt
RUN pip3 install -r /tmp/requirements.txt

# Copier les fichiers de l'application
COPY . /var/www/html/

# Supprimer index.html si nécessaire (ajuster selon les besoins) - COMMENTÉ POUR GARDER LA PAGE D'ACCUEIL
# RUN rm -f /var/www/html/index.html

# Changer les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Installer les extensions PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Configurer Apache pour écouter sur le port défini par Railway ($PORT, défaut 8080)
RUN echo "Listen \${PORT:-8080}" >> /etc/apache2/ports.conf

# Configurer Supervisor
RUN printf "[supervisord]\nnodaemon=true\n\
[program:apache2]\ncommand=apache2ctl -D FOREGROUND\n\
[program:fastapi]\ncommand=python3 -m uvicorn agent:app --host 0.0.0.0 --port 8000 --app-dir /var/www/html\n" \
> /etc/supervisor/conf.d/app.conf

# Exposer le port (Railway utilisera $PORT)
EXPOSE 8080

# Définir le port par défaut
ENV PORT=8080

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
FROM php:8.2-apache

# Installer les dépendances système et Python
RUN apt-get update && apt-get install -y \
    supervisor wget python3 python3-pip python3-venv \
    && apt-get clean

# Créer un environnement virtuel pour Python
RUN python3 -m venv /opt/venv

# Copier et installer les dépendances Python dans le venv
COPY requirements.txt /tmp/requirements.txt
RUN /opt/venv/bin/pip install -r /tmp/requirements.txt

# Copier les fichiers de l'application
COPY . /var/www/html/

# Rendre start.sh exécutable
RUN chmod +x /var/www/html/start.sh

# Supprimer index.html si nécessaire (ajuster selon les besoins) - COMMENTÉ POUR GARDER LA PAGE D'ACCUEIL
# RUN rm -f /var/www/html/index.html

# Changer les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Installer les extensions PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activer les modules Apache pour proxy
RUN a2enmod proxy proxy_http

# Configurer Apache pour écouter sur le port défini par Railway ($PORT, défaut 8080)
RUN echo "Listen \${PORT:-8080}" >> /etc/apache2/ports.conf

# Créer une config pour proxy les requêtes API vers FastAPI
RUN echo '<VirtualHost *:\${PORT:-8080}>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ProxyPass /api http://localhost:8000/api\n\
    ProxyPassReverse /api http://localhost:8000/api\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Exposer le port (Railway utilisera $PORT)
EXPOSE 8080

# Définir le port par défaut
ENV PORT=8080

CMD ["/var/www/html/start.sh"]
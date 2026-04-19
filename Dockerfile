FROM php:8.2-apache

# Installer pip et supervisor
RUN apt-get update && apt-get install -y \
    python3-pip \
    python3-full \
    supervisor \
    && apt-get clean

# Créer un environnement virtuel Python
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Installer les packages Python
RUN /opt/venv/bin/pip install fastapi "uvicorn[standard]" python-multipart pydantic requests opencv-python-headless numpy groq google-generativeai pillow python-dotenv wikipedia

# Installer extensions PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copier le projet
COPY . /var/www/html/
# Rendre start.sh exécutable
RUN chmod +x /var/www/html/start.sh

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Activer les modules Apache pour proxy
RUN a2enmod proxy proxy_http rewrite

# Désactiver les MPM conflictuels et garder mpm_prefork
RUN find /etc/apache2/mods-enabled -name "mpm_*.load" -delete
RUN find /etc/apache2/mods-enabled -name "mpm_*.conf" -delete
RUN a2enmod mpm_prefork

EXPOSE 80 8000

CMD ["/var/www/html/start.sh"]
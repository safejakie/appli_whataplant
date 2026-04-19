FROM php:8.2-apache

RUN apt-get update && apt-get install -y python3 python3-pip && apt-get clean

COPY requirements.txt /tmp/
RUN pip3 install --break-system-packages -r /tmp/requirements.txt uvicorn

# Copie tout le projet
COPY . /var/www/html/

# Supprime la page par défaut d'Apache
RUN rm -f /var/www/html/index.html

# Permet à index.html d'être chargé en priorité
RUN echo "DirectoryIndex index.html index.php" > /etc/apache2/conf-available/directory-index.conf \
    && a2enconf directory-index

# Active les modules
RUN a2enmod rewrite

CMD sh -c "apache2ctl -D FOREGROUND & cd /var/www/html && python3 -m uvicorn agent:app --host 0.0.0.0 --port 8000"
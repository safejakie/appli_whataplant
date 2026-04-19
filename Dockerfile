FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Installation Apache + PHP + Python
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    libapache2-mod-php8.1 \
    python3.11 \
    python3-pip \
    && rm -rf /var/lib/apt/lists/*

# Copie tout le projet
COPY . /var/www/html/

# Supprime la page par défaut d'Apache
RUN rm -f /var/www/html/index.html

# Évite l'avertissement Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN a2enmod rewrite php8.1

# Installation dépendances Python
COPY requirements.txt /tmp/requirements.txt
RUN pip3 install -r /tmp/requirements.txt uvicorn

# Lancement (Apache au premier plan + Python en arrière-plan)
RUN echo '#!/bin/bash\n\
apache2-foreground &\n\
cd /var/www/html\n\
python3 -m uvicorn agent:app --host 0.0.0.0 --port 8000\n\
wait' > /start.sh && chmod +x /start.sh

EXPOSE 80 8000

CMD ["/start.sh"]
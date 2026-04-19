FROM php:8.2-apache

RUN apt-get update && apt-get install -y python3 python3-pip && apt-get clean

COPY requirements.txt /tmp/
RUN pip3 install --break-system-packages -r /tmp/requirements.txt uvicorn

COPY . /var/www/html/

# Démarrer Apache en arrière-plan, puis Python
CMD sh -c "apache2ctl -D FOREGROUND & cd /var/www/html && python3 -m uvicorn agent:app --host 0.0.0.0 --port 8000"
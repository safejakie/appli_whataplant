FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    python3 python3-pip supervisor \
    && apt-get clean

RUN pip3 install --break-system-packages fastapi uvicorn[standard] python-multipart pydantic requests opencv-python-headless numpy groq google-generativeai pillow python-dotenv wikipediaapi

RUN rm -f /var/www/html/index.html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

RUN echo "[supervisord]\nnodaemon=true\n[program:apache2]\ncommand=apache2ctl -D FOREGROUND\nautorestart=true\n[program:fastapi]\ncommand=python3 -m uvicorn agent:app --host 0.0.0.0 --port 8000 --app-dir /var/www/html\nautorestart=true" > /etc/supervisor/conf.d/app.conf

EXPOSE 80 8000

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
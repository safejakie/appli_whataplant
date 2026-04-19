FROM php:8.2-apache

RUN a2dismod mpm_event || true \
    && a2enmod mpm_prefork || true

RUN apt-get update && apt-get install -y supervisor && apt-get clean

RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN pip install fastapi "uvicorn[standard]" python-multipart pydantic requests opencv-python-headless numpy groq google-generativeai pillow python-dotenv wikipedia-api || true

COPY . /var/www/html/
RUN rm -f /var/www/html/index.html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

RUN printf "[supervisord]\nnodaemon=true\n[program:apache2]\ncommand=apache2ctl -D FOREGROUND\n[program:fastapi]\ncommand=python3 -m uvicorn agent:app --host 0.0.0.0 --port 8000 --app-dir /var/www/html\n" \
> /etc/supervisor/conf.d/app.conf

EXPOSE 80 8000

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
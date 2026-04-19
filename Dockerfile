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
RUN /opt/venv/bin/pip install fastapi "uvicorn[standard]" python-multipart pydantic requests opencv-python-headless numpy groq google-generativeai pillow python-dotenv wikipedia-api

# Installer extensions PHP
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copier le projet
COPY . /var/www/html/
RUN rm -f /var/www/html/index.html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configurer supervisor
RUN printf "[supervisord]\nnodaemon=true\nuser=root\n\n\
[program:apache2]\ncommand=/usr/sbin/apache2ctl -D FOREGROUND\nstdout_logfile=/dev/stdout\nstdout_logfile_maxbytes=0\nstderr_logfile=/dev/stderr\nstderr_logfile_maxbytes=0\n\n\
[program:fastapi]\ncommand=/opt/venv/bin/uvicorn agent:app --host 0.0.0.0 --port 8000 --app-dir /var/www/html\nstdout_logfile=/dev/stdout\nstdout_logfile_maxbytes=0\nstderr_logfile=/dev/stderr\nstderr_logfile_maxbytes=0\n" \
> /etc/supervisor/conf.d/app.conf

EXPOSE 80 8000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
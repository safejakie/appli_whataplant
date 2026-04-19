FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    python3-pip \
    python3-full \
    nginx \
    && apt-get clean \
    && rm -f /etc/nginx/sites-enabled/default

# Installer extensions PHP MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

RUN /opt/venv/bin/pip install fastapi "uvicorn[standard]" python-multipart pydantic requests opencv-python-headless numpy groq google-generativeai pillow python-dotenv wikipedia

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

RUN cat > /etc/nginx/conf.d/default.conf << 'EOF'
server {
    listen 80;
    server_name _;
    root /var/www/html;
    index index.html index.php;

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
    }

    location / {
        try_files $uri $uri/ =404;
    }
}
EOF

EXPOSE 80

CMD bash -c "php-fpm -D && /opt/venv/bin/uvicorn agent:app --host 0.0.0.0 --port 8000 --app-dir /var/www/html & nginx -g 'daemon off;'"
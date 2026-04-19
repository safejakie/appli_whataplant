FROM php:8.2-fpm

# Installer pip
RUN apt-get update && apt-get install -y \
    python3-pip \
    python3-full \
    nginx \
    && apt-get clean

# Créer un environnement virtuel Python
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Installer les packages Python
RUN /opt/venv/bin/pip install fastapi "uvicorn[standard]" python-multipart pydantic requests opencv-python-headless numpy groq google-generativeai pillow python-dotenv wikipedia

# Copier le projet
COPY . /var/www/html/
RUN chmod +x /var/www/html/start.sh

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configurer Nginx pour servir les fichiers PHP et proxy FastAPI
RUN mkdir -p /etc/nginx/conf.d && cat > /etc/nginx/conf.d/default.conf << 'EOF'
server {
    listen 80 default_server;
    server_name _;
    root /var/www/html;
    
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
EOF

EXPOSE 80 8000

CMD ["/var/www/html/start.sh"]
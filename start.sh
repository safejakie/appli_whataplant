#!/bin/bash

# Lancer PHP-FPM en arrière-plan
php-fpm &

# Lancer FastAPI en arrière-plan
/opt/venv/bin/uvicorn agent:app --host 0.0.0.0 --port 8000 --app-dir /var/www/html > /var/log/fastapi.log 2>&1 &

# Lancer Nginx en foreground (processus principal)
nginx -g "daemon off;"
#!/bin/bash
# Lancer FastAPI en arrière-plan
/opt/venv/bin/uvicorn agent:app --host 0.0.0.0 --port 8000 --app-dir /var/www/html > /var/log/fastapi.log 2>&1 &
sleep 2

# Lancer Apache en foreground (processus principal)
apache2ctl -D FOREGROUND
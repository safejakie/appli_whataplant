#!/bin/bash
# Démarrer FastAPI en arrière-plan
/opt/venv/bin/python3 -m uvicorn agent:app --host 0.0.0.0 --port 8000 --app-dir /var/www/html &
# Attendre un peu pour que FastAPI démarre
sleep 5
# Démarrer Apache en foreground
apache2ctl -D FOREGROUND
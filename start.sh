#!/bin/bash
# Démarrer Apache en arrière-plan
apache2ctl -D FOREGROUND &
# Démarrer FastAPI en arrière-plan
/opt/venv/bin/python3 -m uvicorn agent:app --host 0.0.0.0 --port 8000 --app-dir /var/www/html &
# Attendre que les processus se terminent
wait
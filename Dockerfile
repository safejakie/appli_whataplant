FROM python:3.11-slim

WORKDIR /app

RUN pip install fastapi uvicorn[standard] python-multipart pydantic requests opencv-python-headless numpy groq google-generativeai pillow python-dotenv wikipedia-api

COPY . .

CMD ["uvicorn", "agent:app", "--host", "0.0.0.0", "--port", "8000"]
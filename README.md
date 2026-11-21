# Service Request Tracker

Monorepo containing the Laravel REST API backend and Next.js frontend for the Service Request Tracker technical assessment.

## Project structure

- `backend/` — Laravel REST API with SQLite
- `frontend/` — Next.js, React, TypeScript, and Tailwind CSS
- `screenshots/` — Local setup verification captures

## Run locally

Backend:

```bash
cd backend
php artisan serve --host=127.0.0.1 --port=8000
```

Frontend:

```bash
cd frontend
npm run dev
```

Frontend: http://localhost:3000  
Backend: http://127.0.0.1:8000

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

## Database (Phase 2)

The backend uses SQLite with Laravel migrations and Eloquent.

- `users` — application users, reused from Laravel's default migration.
- `service_requests` — owner, title, description, priority, status, and optimistic-concurrency version.
- `request_status_histories` — immutable audit rows for later status transitions.

Relationships:

- A user has many service requests and status history records.
- A service request belongs to its creator and has many history records.
- A history record belongs to both a service request and an acting user.

Backend database commands:

```bash
cd backend
php artisan migrate
php artisan db:seed
php artisan test
```

Development demo accounts:

- `user1@example.com` / `Password123!`
- `user2@example.com` / `Password123!`

These accounts are for local development only and use securely hashed passwords.

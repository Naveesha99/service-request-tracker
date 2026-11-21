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
php artisan serve --host=localhost --port=8000
```

Frontend:

```bash
cd frontend
npm run dev
```

Frontend: http://localhost:3000
Backend: http://localhost:8000

Use `localhost` consistently for both services. Do not mix `localhost` and `127.0.0.1`, because they are different cookie hosts.

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

## Authentication (Phase 3)

The backend uses Laravel Sanctum stateful SPA authentication. Authentication is based on the Laravel web session guard and HTTP-only cookies; no bearer tokens or browser storage are used.

Local development uses `http://localhost:3000` for the Next.js frontend and `http://localhost:8000` for Laravel. The configured stateful domains are `localhost:3000` and `localhost:8000`, CORS credentials are enabled, and `FRONTEND_URL` is limited to `http://localhost:3000`.

Session cookies are HTTP-only and use `SameSite=Lax` locally. `SESSION_SECURE_COOKIE=false` is appropriate only for local HTTP development. In production, serve both applications over HTTPS and set `SESSION_SECURE_COOKIE=true`, a restrictive `SESSION_DOMAIN`, `SESSION_SAME_SITE=lax` or `strict` where compatible, and the exact production frontend origin.

CSRF initialization:

```bash
curl -i -c /tmp/srt-cookies http://localhost:8000/sanctum/csrf-cookie
```

For browser/native Fetch authentication, the frontend calls `/sanctum/csrf-cookie` first, then includes `credentials: "include"`. The reusable frontend client and authentication helper are in `frontend/src/lib/api.ts` and `frontend/src/lib/auth.ts`.

API endpoints:

- `POST /api/auth/login` — validates credentials and creates a web session.
- `GET /api/auth/me` — returns the authenticated user's safe public fields; protected by `auth`.
- `POST /api/auth/logout` — logs out, invalidates the session, and regenerates the CSRF token; protected by `auth`.

Successful login regenerates the session. Logout invalidates the server-side session rather than merely deleting browser state. Passwords are checked through Laravel's authentication system. Safe user responses contain only `id`, `name`, and `email`. Service-request ownership will later be derived from `$request->user()` and enforced with authorization policies; client-supplied user IDs must not determine ownership.

Run authentication tests:

```bash
cd backend
php artisan test
```

Manual browser verification:

1. Start Laravel with `cd backend && php artisan serve --host=localhost --port=8000`.
2. Start Next.js with `cd frontend && npm run dev`.
3. In browser developer tools, verify the frontend is using `http://localhost:3000`.
4. Initialize CSRF with `GET http://localhost:8000/sanctum/csrf-cookie` or the frontend login helper.
5. Call `POST http://localhost:8000/api/auth/login` with `user1@example.com` and `Password123!`, using credentials included.
6. Call `GET http://localhost:8000/api/auth/me`; it should return Demo User One.
7. Call `POST http://localhost:8000/api/auth/logout` using credentials.
8. Call `/api/auth/me` again; it should return HTTP 401.
9. In Application/Storage > Cookies, verify the Laravel session cookie is marked HttpOnly and is not readable from document JavaScript.

## Service Request API (Phase 4)

All request endpoints require the authenticated web session and return JSON.

- `POST /api/requests` — create an owned request.
- `GET /api/requests` — list the authenticated user's requests.
- `GET /api/requests/{id}` — retrieve an owned request.
- `GET /api/requests/{id}/history` — retrieve read-only history for an owned request.

Creation payload:

```json
{
  "title": "Printer not working",
  "description": "Office printer stopped responding.",
  "priority": "HIGH"
}
```

The server sets `created_by`, `status=OPEN`, and `version=1`; client values for these fields are ignored.

Listing supports `search`, `status`, `priority`, `page`, and `per_page`. Search matches the title only. Status values are `OPEN`, `IN_PROGRESS`, and `RESOLVED`; priority values are `LOW`, `MEDIUM`, and `HIGH`. Filters are combined with AND logic. Results are newest first, default to 10 records per page, and allow at most 25 records per page.

Example:

```text
GET /api/requests?search=printer&status=OPEN&priority=HIGH&page=1&per_page=10
```

Ownership is enforced through the authenticated user's relationship. Unknown and non-owned request IDs return 404 rather than revealing that another user's request exists. Validation errors return 422, unauthenticated requests return 401, and successful creation returns 201.

Run service request tests:

```bash
cd backend
php artisan test --filter=ServiceRequestTest
```

Status updates, history insertion, editing, and deletion are not included in this phase.

## Status Workflow API (Phase 5)

The allowed workflow is:

```text
OPEN -> IN_PROGRESS -> RESOLVED
```

Resolved requests are terminal. Skipping stages, reopening requests, duplicate transitions, and moving backward are rejected.

Update a status:

```text
PATCH /api/requests/{id}/status
```

```json
{
  "status": "IN_PROGRESS",
  "expected_version": 1,
  "remark": "Technician assigned"
}
```

A successful transition returns HTTP 200, increments `version` exactly once, and creates one audit history row. The next transition uses the updated version:

```json
{
  "status": "RESOLVED",
  "expected_version": 2,
  "remark": "Printer repaired"
}
```

The endpoint uses a conditional database update containing the request ID, owner, status, and expected version. Failed or stale updates do not create history. Invalid transitions return HTTP 409 with `INVALID_TRANSITION`. Stale versions return HTTP 409 with `STALE_VERSION`.

The status update and history insertion occur in one database transaction. If history insertion fails, the status and version update is rolled back.

All status endpoints require the authenticated Sanctum web session. Non-owned and unknown request IDs return 404. SQLite is used locally; the implementation uses an atomic conditional update rather than relying only on an application version check.

Run workflow tests:

```bash
cd backend
php artisan test --filter=RequestStatusTest
```

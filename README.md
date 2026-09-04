# Slamin v4

Laravel backend (API) with a single active frontend in `frontend/`.

## Run backend

- `composer install`
- copy `.env` and configure DB
- `php artisan key:generate`
- `php artisan migrate`
- `php artisan serve`

## Run frontend

- `cd frontend`
- `npm install`
- `npm run dev`

## One-command local dev

From project root:

- `composer dev`

This starts Laravel server, queue worker, and frontend dev server together.

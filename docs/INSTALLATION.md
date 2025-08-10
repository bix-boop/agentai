# Installation Guide

## Plesk (Recommended)
1. Upload the project zip to your domain root in Plesk and extract.
2. Set document root to `backend/public`.
3. Browse to `/installer` and follow steps.
4. After completion, verify admin login and system status.

## Manual
- Backend
  - `cd backend && composer install`
  - `cp .env.example .env && php artisan key:generate`
  - `php artisan migrate --force && php artisan db:seed --force`
  - `php artisan storage:link`
- Frontend
  - `cd frontend && npm install && npm run build`
- Web Server
  - Point document root to `backend/public`.
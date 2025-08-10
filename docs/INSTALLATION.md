# Installation Guide

## Plesk (Recommended)
1. Upload the project zip to your domain root in Plesk and extract.
2. Set document root to `backend/public`.
3. Browse to `/installer` and follow steps.
4. Installer will:
   - Detect PHP CLI automatically (Plesk paths supported)
   - Install Composer deps (downloads composer.phar if composer is not available)
   - Build the frontend if Node.js is available; otherwise, you can upload a pre-built `frontend/build` and the installer will copy it
   - Run migrations, create admin, seed defaults, link storage, and cache config
5. After completion, verify admin login and system status.

## Manual
- Backend
  - `cd backend && php ../composer.phar install` (or `composer install` if available)
  - `cp .env.example .env && php artisan key:generate`
  - `php artisan migrate --force && php artisan db:seed --force`
  - `php artisan storage:link`
- Frontend
  - `cd frontend && npm install && npm run build`
  - Copy `frontend/build` to `backend/public/app`
- Web Server
  - Point document root to `backend/public`.
#!/usr/bin/env bash
set -euo pipefail

# Placeholder deployment script. Customize per environment.

# 1) Install backend deps
# composer install --no-dev --optimize-autoloader

# 2) Migrate and seed
# php artisan migrate --force
# php artisan db:seed --force

# 3) Build frontend (optional)
# (cd ../frontend && npm ci && npm run build)

# 4) Cache config/routes
# php artisan optimize:clear
# php artisan config:cache
# php artisan route:cache
#!/usr/bin/env bash
#
# Zero-downtime-friendly deployment for the Cronos backend.
# Intended to run on the target host (or inside the PHP container) from the repo root.
#
set -euo pipefail

cd "$(dirname "$0")/../backend"

echo "==> Installing PHP dependencies (production)"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Caching configuration, routes, events and views"
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache

echo "==> Refreshing queues (Horizon graceful restart)"
php artisan horizon:terminate || true

echo "==> Building the frontend"
cd ../frontend
npm ci
npm run build

echo "==> Deployment complete"

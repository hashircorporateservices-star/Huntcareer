#!/usr/bin/env bash
#
# HuntCareer one-shot setup.
# Run from the repo root on the VPS:  bash setup.sh
#
# What it does:
#  - Backend: pulls the Laravel 12 framework skeleton (composer.json, artisan,
#    public/, and the standard config files) WITHOUT overwriting our custom code,
#    installs packages, and generates the app key.
#  - Frontend: installs the Node dependencies (package.json is already provided).
#
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
echo "==> HuntCareer setup in: $ROOT"

command -v composer >/dev/null || { echo "composer not found — install PHP 8.2+ and Composer first"; exit 1; }
command -v npm >/dev/null      || { echo "npm not found — install Node 18+ first"; exit 1; }

# ---------------------------------------------------------------------------
# Backend — Laravel 12 skeleton overlaid UNDER our custom files
# ---------------------------------------------------------------------------
echo "==> [backend] scaffolding Laravel 12 skeleton..."
rm -rf "$ROOT/_laravel_tmp"
composer create-project laravel/laravel "$ROOT/_laravel_tmp" "^12.0" --no-interaction --quiet

# cp -n = never overwrite: keeps ALL our custom app/, config/*, routes/*,
# migrations, bootstrap/, and only fills in the missing skeleton files.
cp -rn "$ROOT/_laravel_tmp/." "$ROOT/backend/"
rm -rf "$ROOT/_laravel_tmp"

# Remove the skeleton's default migrations — our own create_core_tables migration
# already defines users (and we use Redis for cache/queue, not DB tables).
rm -f "$ROOT/backend/database/migrations/0001_01_01_"*.php

cd "$ROOT/backend"
echo "==> [backend] installing packages..."
composer require laravel/sanctum socialiteproviders/microsoft google/apiclient --no-interaction --quiet
[ -f .env ] || cp .env.example .env
php artisan key:generate --force
echo "==> [backend] done."

# ---------------------------------------------------------------------------
# Frontend — dependencies (package.json already in the repo)
# ---------------------------------------------------------------------------
cd "$ROOT/frontend"
echo "==> [frontend] installing packages..."
npm install
[ -f .env.local ] || echo "NEXT_PUBLIC_API_URL=http://localhost:8000/api" > .env.local
echo "==> [frontend] done."

cat <<'NEXT'

============================================================
 SETUP COMPLETE. Next steps:

 1) Edit backend/.env  — set DB (Postgres), Redis, and your
    AI / OAuth / Adzuna / Lemon Squeezy keys.

 2) Create the database, then:
      cd backend && php artisan migrate

 3) Run it:
      Backend :  php artisan serve            (dev)
                 or nginx + php-fpm            (prod, see DEPLOY.md)
      Queue   :  php artisan queue:work
      Schedule:  * * * * * php artisan schedule:run   (crontab)
      Frontend:  cd frontend && npm run dev    (dev)
                 or npm run build && pm2 start "npm run start"  (prod)

 4) Make yourself admin (unlimited) after first sign-in:
      php artisan tinker
      >>> App\Models\User::where('email','you@example.com')->update(['is_admin'=>true]);
============================================================
NEXT

# Deploy — GitHub + Hostinger VPS

Same shape as your HuntPDF flow (commit on GitHub web → pull + restart on the VPS),
but this app has two runtimes plus a scheduler and a queue worker.

## One-time server setup

```bash
# PHP 8.3 + extensions for Laravel 12, Composer, Node, Redis, PostgreSQL client
sudo apt update && sudo apt install -y php8.3 php8.3-{cli,fpm,pgsql,redis,mbstring,xml,curl,bcmath} \
     composer redis-server postgresql-client
# Node is already on your box for HuntPDF; reuse it.

git clone https://github.com/hashircorporateservices-star/huntcareer.git /var/www/huntcareer
cd /var/www/huntcareer
```

## Backend (Laravel)

```bash
cd /var/www/huntcareer/backend
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# fill in DB_*, REDIS_*, AWS_* (S3), AI_*, GOOGLE_*, ATS_* — see backend README
php artisan migrate --force
php artisan config:cache && php artisan route:cache
```

Run the API behind nginx via php-fpm (mirror your HuntPDF nginx block, `root` →
`backend/public`). Then the two long-running pieces:

```bash
# Queue worker (tailoring / cover letters run async) — under PM2 so it restarts with the others
pm2 start "php artisan queue:work --sleep=3 --tries=3" --name copilot-queue --cwd /var/www/huntcareer/backend

# Scheduler — this is what fires auto-apply + hourly email ingest AT THE SET TIME.
# Add to the crontab (crontab -e):
* * * * * cd /var/www/huntcareer/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Frontend (Next.js) — PM2, like HuntPDF's next-app

```bash
cd /var/www/huntcareer/frontend
npm ci && npm run build
echo 'NEXT_PUBLIC_API_URL=https://copilot.yourdomain.com/api' > .env.production
pm2 start "npm run start" --name copilot-web --cwd /var/www/huntcareer/frontend
pm2 save
```

## Redeploy (every change) — your familiar flow

```bash
cd /var/www/huntcareer
git fetch origin && git reset --hard origin/main

# backend
cd backend && composer install --no-dev -o && php artisan migrate --force \
  && php artisan config:cache && php artisan route:cache

# frontend
cd ../frontend && npm ci && npm run build

# restart everything
pm2 restart copilot-web copilot-queue
# (the scheduler cron needs no restart)
```

## What runs when

| Piece | Trigger | Does |
|---|---|---|
| `copilot:ingest-email` | hourly (scheduler) | reads your Gmail alerts → jobs |
| `copilot:auto-apply` | every minute (scheduler) | fires rules due at their set time → prepares into Review Queue |
| `copilot-queue` (PM2) | continuous | runs the AI tailoring / cover-letter jobs |
| You, in the Review Queue | on demand | approve → open / draft / ATS-submit, capped at 30/day |

## Reputation guardrails (don't raise these without reason)

- `prep_daily_cap = 500` — safe to prepare a big backlog.
- `submit_daily_cap = 30` — actual sends per day. This protects your email domain
  from spam-flagging. Blasting hundreds/day is how a job-search inbox gets blacklisted;
  a targeted 20–30 lands more interviews and keeps your name clean.

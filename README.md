# AI Job Copilot — personal-use foundation

A single-operator assistant for a multi-country finance job search (GB · IE · MT · AU · NZ · DE · US).
This repo is the **coherent foundation**, not a finished product. It ships the full database schema
and a working scheduled **auto-apply engine** end to end. The remaining features have their tables,
routes, and service seams in place, ready to fill in.

## The auto-apply design (read this first)

Fully unattended submission to LinkedIn / Indeed / Seek / StepStone is **not** built, on purpose:
those boards prohibit automated submission in their ToS (account bans, not throttling), and un-reviewed
applications sent under your name damage your reputation. Instead:

```
[schedule fires] → search → AI match score → tailor resume + cover letter
        → drop into Review Queue (status: pending_review)  ← STOPS HERE
        → you approve → open pre-filled in browser  (search-only boards)
                      → or submit via official ATS API  (Greenhouse / Lever)
```

The `require_review` flag on a rule can permit unattended submission, but it is honoured **only** for
`is_direct_ats` jobs with an authorised integration. That boundary lives in one place:
`app/Services/AtsSubmissionService.php`.

## Stack

- **Backend:** Laravel 12 · PostgreSQL · Redis (queue) · Elasticsearch (job search) · S3 (resume storage)
- **Frontend:** Next.js (App Router) · React · TypeScript · Tailwind
- **Auth:** Sanctum sessions + Google / Microsoft OAuth (Socialite)

## What's built vs. scaffolded

| Area | State |
|---|---|
| Full schema — every table you listed | **Done** (`database/migrations`) |
| Auto-apply engine (rules, scheduler, queue, submit boundary) | **Done** end to end |
| AI match scoring (with heuristic fallback) | **Done** (`JobMatchingService`) |
| Resume tailoring + cover-letter generation | **Done** (service layer, AI-backed) |
| Review Queue UI + Auto-Apply config UI + Dashboard | **Done** (Next.js) |
| Job ingestion adapters (per source) | Seam only — add fetchers per `config/copilot.php` sources |
| Auth wiring, admin panel, analytics rollup command, remaining CRUD controllers | Routed, not implemented |

## Folder structure

```
backend/
  app/
    Console/Commands/RunAutoApplyCommand.php   # the scheduled tick
    Http/Controllers/Api/                       # AutoApplyRule + ReviewQueue controllers
    Models/                                     # all Eloquent models
    Services/                                   # matching, tailoring, cover letters, ATS submit, auto-apply
  config/copilot.php                            # countries, roles, sources — the domain config
  database/migrations/                          # full schema (5 migrations)
  routes/api.php  routes/console.php            # API + scheduler
frontend/
  src/app/(dashboard)/dashboard|auto-apply|review-queue/
  src/components/Sidebar.tsx
  src/lib/api.ts
```

## Setup

**Backend**
```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate
# set DB_*, REDIS_*, AWS_* (S3), and the AI + ATS keys below
php artisan migrate
php artisan serve
```

**The scheduler** (this is what makes "run at a set time" work). On the VPS:
```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

**Frontend**
```bash
cd frontend
npm install
echo 'NEXT_PUBLIC_API_URL=http://localhost:8000/api' > .env.local
npm run dev
```

### Required env (backend `.env`)
```
# AI provider (OpenAI-compatible endpoint — matching, tailoring, cover letters)
AI_ENDPOINT=https://api.openai.com/v1/chat/completions
AI_KEY=...
AI_MODEL=gpt-4o-mini

# Official ATS integrations (optional; enable per provider)
ATS_GREENHOUSE_ENABLED=false
ATS_GREENHOUSE_TOKEN=
ATS_LEVER_ENABLED=false
ATS_LEVER_TOKEN=
```
Add matching keys to `config/services.php` under `ai` and `ats.{provider}`.

## Try the engine without any job source

```bash
# 1. seed a resume (is_base=true) and a couple of jobs in your target country
# 2. create a rule via the UI (/auto-apply) or the API
# 3. run it immediately:
php artisan copilot:auto-apply --rule=1
# 4. open /review-queue — prepared applications are waiting
```

## Next steps, in priority order

1. **Job ingestion adapters** — one class per source in `config/copilot.php`, normalising titles to
   `role_families` and writing `jobs` rows. Elasticsearch indexing on write.
2. **Auth** — Socialite for Google/Microsoft, Sanctum session cookie for the SPA.
3. **Resume upload + AI extraction** (feature #1) — S3 put, then parse into the `parsed_*` columns.
4. Remaining CRUD controllers (resumes, applications, recruiters, interview, analytics) — routes exist.
5. **Admin panel + analytics rollup command** (`copilot:rollup-analytics`, already scheduled).

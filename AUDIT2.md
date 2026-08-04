# HuntCareer — advanced audit (round 2)

This round goes past "does the route resolve" into "is the code correct": column↔model
alignment, config resolution, enum consistency, route-binding correctness, plus a manual
security/logic review.

## Automated deep checks (all now pass)

| Check | What it proves | Result |
|---|---|---|
| Column ↔ cast alignment | every model `$casts` key is a real column (across create **and** alter migrations) | 19/19 models clean |
| `config()` resolution | every `config('file.key…')` points at a key that exists | clean |
| Enum consistency | DB writes to `status`/`submit_method` use only defined enum values | clean |
| Route-model binding | every `{param}` in a route has a matching `$param` in the controller method | clean |
| (round 1) Route→controller, commands, models, frontend calls, nav | wiring resolves end to end | clean |

## Findings this round — 2 real bugs, 1 precaution, 1 UX bug

| # | Severity | Finding | Fix |
|---|---|---|---|
| 1 | **High** | `SocialAuthController` redirected via `config('app.frontend_url')` — a key that doesn't exist, so post-OAuth login would fall back to `/` and break the return to the app | Added `copilot.frontend_url` (from `FRONTEND_URL`) and pointed the redirect at it |
| 2 | **Med** | `GmailJobAlertService::normaliseRole()` still called `config('copilot.role_families')`, removed during the all-jobs generalization → iterating `null` on every ingested email | Guarded with a default `[]`; re-added an empty `role_families` key so the config resolves cleanly |
| 3 | Low | `JobMatch` depended on Laravel pluralizing to `job_matches` (the "-ch → -ches" case is a known footgun) | Pinned `protected $table = 'job_matches'` explicitly |
| 4 | Low (UX) | The submit response used a `status` field whose `'capped'` value collided conceptually with the DB enum; the frontend also removed a *capped* (unsent) item from the queue as if it had been submitted | Renamed the response field to `result`; the queue now keeps a capped item visible and shows the "limit reached" message |

## Manual review — verified correct

- **Ownership:** every user-scoped endpoint (resumes, cover letters, applications,
  recruiters, contacts, Scouts, review queue) checks `user_id` before acting. Jobs are
  intentionally global (read-only listings).
- **Webhook:** the Lemon Squeezy webhook is unauthenticated but HMAC-signature-verified,
  and aborts 401 on mismatch.
- **Secrets:** the Google refresh token is stored through the `Setting` encrypted accessor
  and read back through it (the round-1 decryption bug stays fixed).
- **Route ordering:** `POST /resumes/build` and `/auto-apply/queue/bulk-approve` don't
  collide with their `{param}` siblings (different verbs / distinct segments).
- **Uniqueness:** `job_profiles`, `settings`, `job_matches`, `applications`, and
  `auto_apply_queue` all carry the unique constraints their upserts rely on.

## Hardening recommendations (not bugs — do before charging real users)

- **Mass assignment:** models use `$guarded = []`. Every current write goes through
  `validate()`, so it's safe today, but switch to explicit `$fillable` on the models that
  accept user input, as defence in depth.
- **`subscriptions`** has no unique index on `user_id`; `updateOrCreate(['user_id'])` works
  but a race could create a duplicate. Add a unique index if you expect concurrency.
- **Borderline items don't upgrade:** a job queued as a below-tier suggestion won't be
  re-prepared as a full application later (the `(user_id, job_id)` unique + `alreadyHandled`
  block it). Acceptable, but note it's by design.

## What this audit still does NOT prove

Same honest caveat as round 1: **nothing was executed.** No PHP runtime, DB, Redis, or
network here — so this proves the code is internally consistent and correct *by inspection*,
not that a live request returns the right rows or that Adzuna/Lemon Squeezy/OAuth behave as
coded against their real APIs. Runtime confirmation still requires scaffolding the
frameworks, adding keys, and running the smoke test (sign in → Scout → `copilot:search` →
review → apply → analytics), then `php artisan route:list`.

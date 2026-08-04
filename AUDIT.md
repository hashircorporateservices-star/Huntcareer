# HuntCareer — audit report

Static + structural audit of the whole codebase, focused on endpoints and the
frontend↔API wiring. Below is what was checked, what was broken, what got fixed,
and — honestly — what this kind of audit can and can't prove.

## Result

- **API surface:** 0 structural issues. Every route resolves to a real controller
  method; every scheduled command, referenced model, and service class exists.
- **Frontend↔API:** every `api.*()` call maps to a registered route.
- **Navigation:** all 14 sidebar links resolve to a real page (0 dead links).

## What was checked (automated)

1. Every `Route::*` and `apiResource` in `routes/api.php` + `routes/web.php`
   → controller class exists **and** the method exists.
2. Every `Schedule::command()` → a command class with that signature exists.
3. Every `App\Models\X` reference in the backend → the model file exists.
4. Every frontend `api.get/post/patch/del('...')` path → a matching backend route.
5. Every sidebar link → a Next.js `page.tsx` at that path.

## Bugs found and fixed

| # | Severity | Bug | Fix |
|---|---|---|---|
| 1 | High | 6 routes pointed at controllers that didn't exist (`Job`, `Resume`, `CoverLetter`, `Application`, `Recruiter`, `Analytics`) — every call would 500 | Wrote all six controllers (real, owner-scoped CRUD + actions) |
| 2 | High | `InterviewService` referenced `App\Models\InterviewQuestion`, which was never created — a fatal on interview generation | Created the model (+ `Recruiter`, `AnalyticsSnapshot`) |
| 3 | High | Scheduler called `copilot:rollup-analytics` with no command class → nightly schedule crash | Wrote `RollupAnalyticsCommand` |
| 4 | Med | `apiResource('/auto-apply/rules')` registered a `show` route the controller lacked → 500 on GET one Scout | Added `AutoApplyRuleController@show` |
| 5 | Med | **Gmail refresh token read via `->value('value')`**, bypassing the model's decrypt accessor → Google API got ciphertext, ingestion would always fail auth | Read through the model (`->first()?->value`) so it decrypts |
| 6 | Med | **Wizard's match-threshold slider was captured but never used** — the matcher filtered on `min_match_score` (default 80), so High/Highest did nothing | Map threshold → score (70/80/90) in the controller |
| 7 | Low | 8 sidebar links (search, saved, applications, resumes, cover-letters, recruiters, analytics, settings) had no page → 404 | Built all 8 pages against the now-real endpoints |

## What a static audit does NOT prove

Being straight about the limits so "no bugs" isn't oversold:

- **Runtime is not tested here.** There's no PHP runtime, database, or Redis in this
  environment, so no route was actually executed. The audit proves the wiring is
  correct, not that every query returns the right rows.
- **External APIs are unverified.** Adzuna, Greenhouse, Lemon Squeezy, the AI
  provider, and Google/Microsoft/Facebook OAuth all need real keys + network. Their
  request/response shapes are coded to their public docs but not live-tested.
- **The app still needs scaffolding to run.** These are drop-in files over a fresh
  `laravel new` + `create-next-app`; the framework core (`artisan`, `composer.json`,
  `package.json`, Sanctum config) is not in the repo.
- **Still stubbed by design:** the `hiring_manager_contacts` "Contacts" feature has a
  table + model but no controller/UI yet; resume upload + AI extraction is a create
  endpoint, not a full S3-and-parse pipeline.

## Recommended before go-live

1. Scaffold both frameworks, `composer install` / `npm ci`, run `php artisan migrate`.
2. Add all `.env` keys (see `.env.example`) and connect the OAuth apps.
3. Smoke-test the spine end to end: sign in → create Scout → `copilot:search` →
   review queue → approve → analytics.
4. `php artisan route:list` to eyeball the final registered routes on real Laravel.

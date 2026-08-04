# Authentication setup (Google · Facebook · Microsoft)

Sign-in uses Laravel Socialite + Sanctum SPA sessions. Google also grants the
Gmail read-only scope, so one sign-in unlocks job-alert ingestion too.

## 1. Install packages
```bash
cd backend
composer require laravel/socialite socialiteproviders/microsoft
```
Microsoft is registered as a driver in `app/Providers/AppServiceProvider.php` (already done).

## 2. Create the OAuth apps + redirect URIs
Register each app and set its redirect URI to your backend:

| Provider | Console | Redirect URI |
|---|---|---|
| Google | console.cloud.google.com → Credentials → OAuth client | `https://copilot.yourdomain.com/auth/google/callback` |
| Microsoft | portal.azure.com → App registrations | `https://copilot.yourdomain.com/auth/microsoft/callback` |
| Facebook | developers.facebook.com → Facebook Login | `https://copilot.yourdomain.com/auth/facebook/callback` |

For **Google**, also enable the **Gmail API** and add the scope
`.../auth/gmail.readonly` on the consent screen (plus email/profile).

## 3. Env
Fill `GOOGLE_*`, `MICROSOFT_*`, `FACEBOOK_*`, and `FRONTEND_URL` in `.env`
(see `.env.example`).

## 4. Sanctum SPA (first-party cookie auth)
Because the frontend and API share a top domain, use cookie sessions:
```
SANCTUM_STATEFUL_DOMAINS=copilot.yourdomain.com
SESSION_DOMAIN=.yourdomain.com
FRONTEND_URL=https://copilot.yourdomain.com
```
Ensure Sanctum's middleware is enabled and the SPA sends `credentials: 'include'`
(the frontend `api.ts` already does).

## 5. The flow
1. Frontend `/login` → button links to `GET /auth/{provider}/redirect`.
2. Provider consent → `GET /auth/{provider}/callback` finds-or-creates the user,
   logs them into the web session, stores the Google refresh token (encrypted),
   and redirects to `FRONTEND_URL/dashboard`.
3. The SPA calls `GET /api/me` (with credentials) to load the session user.
4. `POST /api/logout` ends it.

## 6. Make yourself admin (unlimited)
After your first sign-in:
```bash
php artisan tinker
>>> \App\Models\User::where('email','you@example.com')->update(['is_admin'=>true]);
```
That flips on the uncapped mode (unlimited Scouts, matches, prep, submit, credits).

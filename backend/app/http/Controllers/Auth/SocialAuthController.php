<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * OAuth sign-in for Google, Facebook, and Microsoft.
 *
 * Requires:
 *   composer require laravel/socialite socialiteproviders/microsoft
 * Microsoft is registered as a Socialite driver in AppServiceProvider.
 *
 * Flow (first-party Sanctum SPA):
 *   redirect() -> provider consent -> callback() logs the user into the web
 *   session and bounces back to the frontend. The SPA then reads /api/me.
 *
 * Google additionally requests the Gmail read-only scope + offline access, so a
 * single sign-in also yields the refresh token used for job-alert ingestion.
 */
class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'facebook', 'microsoft'];

    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        $driver = Socialite::driver($provider);

        if ($provider === 'google') {
            $driver->scopes(['https://www.googleapis.com/auth/gmail.readonly'])
                   ->with(['access_type' => 'offline', 'prompt' => 'consent']);
        }

        return $driver->redirect();
    }

    public function callback(string $provider)
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        $oauthUser = Socialite::driver($provider)->user();

        $user = User::updateOrCreate(
            ['email' => $oauthUser->getEmail()],
            [
                'name'              => $oauthUser->getName() ?: $oauthUser->getNickname() ?: 'User',
                'oauth_provider'    => $provider,
                'oauth_provider_id' => $oauthUser->getId(),
                'email_verified_at' => now(),
            ]
        );

        // Google: persist the refresh token (encrypted) to enable Gmail ingestion.
        if ($provider === 'google' && $oauthUser->refreshToken) {
            $setting = Setting::firstOrNew(['user_id' => $user->id, 'key' => 'google_refresh_token']);
            $setting->is_encrypted = true;      // set before value so the mutator encrypts
            $setting->value = $oauthUser->refreshToken;
            $setting->save();
        }

        Auth::login($user, remember: true);

        return redirect(rtrim(config('copilot.frontend_url'), '/') . '/dashboard');
    }

    public function me()
    {
        return Auth::user();
    }

    public function logout(\Illuminate\Http\Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}

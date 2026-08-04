<?php

use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;

/*
 * OAuth is a browser-redirect flow, so it lives on the web guard (session),
 * which is what Sanctum's SPA authentication uses.
 */
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback']);

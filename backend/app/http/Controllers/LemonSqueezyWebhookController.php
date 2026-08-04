<?php

namespace App\Http\Controllers;

use App\Services\LemonSqueezyService;
use Illuminate\Http\Request;

/**
 * Lemon Squeezy webhook receiver. Lives on the web routes (no auth), but every
 * request is HMAC-verified before anything happens.
 */
class LemonSqueezyWebhookController extends Controller
{
    public function __invoke(Request $request, LemonSqueezyService $ls)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Signature');

        if (! $ls->verifySignature($payload, $signature)) {
            abort(403, 'Invalid signature.');
        }

        $ls->handle(json_decode($payload, true) ?: []);

        return response()->json(['received' => true]);
    }
}

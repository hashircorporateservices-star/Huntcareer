<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function __construct(protected BillingService $billing) {}

    /** Start a checkout; returns the hosted Lemon Squeezy URL to redirect to. */
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'plan'  => ['required', Rule::in(array_keys(config('plans.plans')))],
            'cycle' => ['required', Rule::in(array_keys(config('plans.billing_cycles')))],
        ]);

        $url = $this->billing->createCheckout($request->user(), $data['plan'], $data['cycle']);

        return response()->json(['url' => $url]);
    }

    /** Lemon Squeezy webhook. No auth; verified by HMAC signature. */
    public function webhook(Request $request)
    {
        $raw = $request->getContent();

        abort_unless(
            $this->billing->verifySignature($raw, $request->header('X-Signature')),
            401,
            'Invalid signature.'
        );

        $this->billing->handleWebhook(json_decode($raw, true) ?: []);

        return response()->json(['ok' => true]);
    }
}

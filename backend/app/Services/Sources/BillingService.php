<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Lemon Squeezy billing. Creates hosted checkouts and reconciles subscription
 * state + monthly credits from webhooks.
 *
 * Variant IDs (one per plan+cycle) live in config/services.php.
 */
class BillingService
{
    public function __construct(protected PlanService $plans) {}

    /** Create a hosted checkout URL for a plan + billing cycle. */
    public function createCheckout(User $user, string $plan, string $cycle): string
    {
        $cfg = config('services.lemonsqueezy');
        $variantId = data_get($cfg, "variants.$plan.$cycle");
        abort_unless($variantId, 422, 'No Lemon Squeezy variant configured for that plan/cycle.');

        $res = Http::withToken($cfg['api_key'])
            ->withHeaders(['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json'])
            ->post('https://api.lemonsqueezy.com/v1/checkouts', [
                'data' => [
                    'type' => 'checkouts',
                    'attributes' => [
                        'checkout_data' => [
                            'email'  => $user->email,
                            'custom' => ['user_id' => (string) $user->id, 'plan' => $plan, 'cycle' => $cycle],
                        ],
                    ],
                    'relationships' => [
                        'store'   => ['data' => ['type' => 'stores', 'id' => (string) $cfg['store_id']]],
                        'variant' => ['data' => ['type' => 'variants', 'id' => (string) $variantId]],
                    ],
                ],
            ])->throw()->json();

        return data_get($res, 'data.attributes.url');
    }

    /** Verify the webhook signature (HMAC-SHA256 of the raw body). */
    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        $secret = config('services.lemonsqueezy.signing_secret');
        return $signature && hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    /** Reconcile a webhook event into our subscription + credits. */
    public function handleWebhook(array $payload): void
    {
        $event  = data_get($payload, 'meta.event_name');
        $custom = data_get($payload, 'meta.custom_data', []);
        $userId = (int) data_get($custom, 'user_id');
        if (! $userId) {
            return;
        }
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $attr = data_get($payload, 'data.attributes', []);

        match ($event) {
            'subscription_created', 'subscription_updated', 'subscription_resumed' =>
                $this->upsertSubscription($user, $custom, $payload, 'active'),

            'subscription_cancelled', 'subscription_expired' =>
                $this->upsertSubscription($user, $custom, $payload, 'cancelled'),

            'subscription_payment_success' =>
                $this->onPaymentSuccess($user),

            default => null,
        };
    }

    protected function upsertSubscription(User $user, array $custom, array $payload, string $status): void
    {
        Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan'                     => $custom['plan'] ?? 'premium',
                'billing_cycle'            => $custom['cycle'] ?? 'monthly',
                'status'                   => $status,
                'provider'                 => 'lemonsqueezy',
                'provider_subscription_id' => data_get($payload, 'data.id'),
                'renews_at'                => data_get($payload, 'data.attributes.renews_at'),
                'ends_at'                  => data_get($payload, 'data.attributes.ends_at'),
            ]
        );
    }

    /** On each successful payment, grant that plan's monthly credits. */
    protected function onPaymentSuccess(User $user): void
    {
        $this->plans->grantMonthlyCredits($user);
    }
}

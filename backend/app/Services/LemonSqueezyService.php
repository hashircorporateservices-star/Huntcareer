<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Lemon Squeezy billing: create a hosted checkout, and process webhooks
 * (subscription lifecycle + monthly credit grant on payment).
 */
class LemonSqueezyService
{
    public function __construct(protected PlanService $plans) {}

    /** Create a hosted checkout URL for a plan+cycle, tagged with the user id. */
    public function checkoutUrl(User $user, string $plan, string $cycle): string
    {
        $variantId = config("plans.variants.$plan.$cycle");
        abort_unless($variantId, 422, 'That plan/cycle is not available.');

        $res = Http::withToken(config('services.lemonsqueezy.api_key'))
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
                        'store'   => ['data' => ['type' => 'stores', 'id' => (string) config('services.lemonsqueezy.store_id')]],
                        'variant' => ['data' => ['type' => 'variants', 'id' => (string) $variantId]],
                    ],
                ],
            ])->throw()->json();

        return data_get($res, 'data.attributes.url');
    }

    /** HMAC-SHA256 signature check on the raw webhook body. */
    public function verifySignature(string $payload, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }
        $expected = hash_hmac('sha256', $payload, (string) config('services.lemonsqueezy.webhook_secret'));
        return hash_equals($expected, $signature);
    }

    /** Route a verified webhook event to the right handler. */
    public function handle(array $event): void
    {
        $name = data_get($event, 'meta.event_name');
        $custom = data_get($event, 'meta.custom_data', []);
        $attr = data_get($event, 'data.attributes', []);

        $user = $this->resolveUser($custom, $attr);
        if (! $user) {
            return;
        }

        [$plan, $cycle] = $this->planFromVariant((int) data_get($attr, 'variant_id'), $custom);

        match ($name) {
            'subscription_created',
            'subscription_updated',
            'subscription_resumed' => $this->upsert($user, $plan, $cycle, $attr, 'active'),

            'subscription_payment_success' => $this->onPayment($user, $plan, $cycle, $attr),

            'subscription_cancelled' => $this->setStatus($user, 'cancelled', $attr),
            'subscription_expired'   => $this->setStatus($user, 'cancelled', $attr),
            'subscription_paused'    => $this->setStatus($user, 'past_due', $attr),

            default => null,
        };
    }

    protected function onPayment(User $user, ?string $plan, ?string $cycle, array $attr): void
    {
        $sub = $this->upsert($user, $plan, $cycle, $attr, 'active');

        // Grant monthly credits once per billing period (idempotent guard).
        $recentGrant = $user->creditTransactions()
            ->where('reason', 'monthly_grant')
            ->where('created_at', '>=', now()->subDays(25))
            ->exists();

        if (! $recentGrant) {
            $this->plans->grantMonthlyCredits($user);
        }
    }

    protected function upsert(User $user, ?string $plan, ?string $cycle, array $attr, string $status): Subscription
    {
        return Subscription::updateOrCreate(
            ['user_id' => $user->id],
            array_filter([
                'plan'                     => $plan,
                'billing_cycle'            => $cycle,
                'status'                   => $status,
                'provider'                 => 'lemonsqueezy',
                'provider_subscription_id' => data_get($attr, 'first_subscription_item.subscription_id')
                                                ?? data_get($attr, 'subscription_id'),
                'renews_at'                => data_get($attr, 'renews_at'),
                'ends_at'                  => data_get($attr, 'ends_at'),
            ], fn ($v) => $v !== null)
        );
    }

    protected function setStatus(User $user, string $status, array $attr): void
    {
        $user->subscriptions()->latest()->first()?->update([
            'status'  => $status,
            'ends_at' => data_get($attr, 'ends_at'),
        ]);
    }

    protected function resolveUser(array $custom, array $attr): ?User
    {
        if ($id = ($custom['user_id'] ?? null)) {
            return User::find($id);
        }
        return User::where('email', data_get($attr, 'user_email'))->first();
    }

    /** @return array{0:?string,1:?string} [plan, cycle] */
    protected function planFromVariant(int $variantId, array $custom): array
    {
        foreach (config('plans.variants') as $plan => $cycles) {
            foreach ($cycles as $cycle => $id) {
                if ((int) $id === $variantId) {
                    return [$plan, $cycle];
                }
            }
        }
        // Fall back to the custom data sent at checkout.
        return [$custom['plan'] ?? null, $custom['cycle'] ?? null];
    }
}

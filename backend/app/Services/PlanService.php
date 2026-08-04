<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\HiringManagerContact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Plan gating + credit ledger. All limits read from config/plans.php, so pricing
 * and limits change without touching code.
 */
class PlanService
{
    /** The user's active plan key, or null if none. */
    public function planKey(User $user): ?string
    {
        $sub = $user->subscriptions()->latest()->first();
        return $sub && $sub->isActive() ? $sub->plan : null;
    }

    /** You (the owner) run with no limits. Everyone else is plan-bound. */
    public function isUnlimited(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    /** A single limit for the user's plan. Admins get no ceiling. */
    public function limit(User $user, string $key): int|bool
    {
        if ($this->isUnlimited($user)) {
            return $key === 'tailor_every_application' ? true : PHP_INT_MAX;
        }

        $plan = $this->planKey($user) ?? 'premium';
        return config("plans.plans.$plan.limits.$key");
    }

    // ---- Credits ----

    public function balance(User $user): int
    {
        if ($this->isUnlimited($user)) {
            return PHP_INT_MAX;
        }
        return (int) $user->creditTransactions()->sum('delta');
    }

    /** Idempotent-ish monthly grant. Call from a scheduled command on renewal. */
    public function grantMonthlyCredits(User $user): void
    {
        $amount = (int) $this->limit($user, 'monthly_credits');
        if ($amount > 0) {
            $user->creditTransactions()->create([
                'delta'  => $amount,
                'reason' => 'monthly_grant',
            ]);
        }
    }

    /**
     * Spend 1 credit to reveal a hiring-manager contact. Atomic: won't reveal if
     * the balance is short.
     *
     * @throws \RuntimeException when out of credits
     */
    public function revealContact(User $user, HiringManagerContact $contact): HiringManagerContact
    {
        return DB::transaction(function () use ($user, $contact) {
            if ($this->balance($user) < 1) {
                throw new \RuntimeException('Out of credits.');
            }

            CreditTransaction::create([
                'user_id'      => $user->id,
                'delta'        => -1,
                'reason'       => 'contact_reveal',
                'subject_type' => HiringManagerContact::class,
                'subject_id'   => $contact->id,
            ]);

            $contact->update(['revealed' => true, 'revealed_at' => now()]);
            return $contact->fresh();
        });
    }

    /** Enforce the Scout (auto-apply rule) count limit before creating a new one. */
    public function canAddScout(User $user): bool
    {
        return $user->autoApplyRules()->count() < (int) $this->limit($user, 'scouts');
    }
}

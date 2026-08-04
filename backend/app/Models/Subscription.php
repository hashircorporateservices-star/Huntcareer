<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $guarded = [];
    protected $casts = [
        'trial_ends_at' => 'datetime',
        'renews_at'     => 'datetime',
        'ends_at'       => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function isActive(): bool
    {
        return in_array($this->status, ['trialing', 'active'], true);
    }
}

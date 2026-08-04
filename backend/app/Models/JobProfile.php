<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobProfile extends Model
{
    protected $guarded = [];
    protected $casts = [
        'work_auth_countries' => 'array',
        'nationalities'       => 'array',
        'screening_answers'   => 'array',
        'requires_visa'       => 'boolean',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    protected $guarded = [];

    protected $casts = [
        'extracted_skills'  => 'array',
        'visa_sponsorship'  => 'boolean',
        'is_direct_ats'     => 'boolean',
        'posted_at'         => 'datetime',
        'fetched_at'        => 'datetime',
        'closed_at'         => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
}

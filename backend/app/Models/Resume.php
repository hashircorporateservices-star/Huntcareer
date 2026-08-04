<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_base'             => 'boolean',
        'parsed_experience'   => 'array',
        'parsed_education'    => 'array',
        'parsed_skills'       => 'array',
        'parsed_certificates' => 'array',
        'parsed_achievements' => 'array',
        'parsed_at'           => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

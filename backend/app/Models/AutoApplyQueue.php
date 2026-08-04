<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoApplyQueue extends Model
{
    protected $table = 'auto_apply_queue';
    protected $guarded = [];

    protected $casts = [
        'prepared_at'  => 'datetime',
        'reviewed_at'  => 'datetime',
        'submitted_at' => 'datetime',
        'match_score'  => 'integer',
        'is_borderline' => 'boolean',
    ];

    public function job(): BelongsTo { return $this->belongsTo(Job::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function resume(): BelongsTo { return $this->belongsTo(Resume::class); }
    public function coverLetter(): BelongsTo { return $this->belongsTo(CoverLetter::class, 'cover_letter_id'); }
}

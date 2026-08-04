<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewQuestion extends Model
{
    protected $guarded = [];

    public function job(): BelongsTo { return $this->belongsTo(Job::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $guarded = [];
    protected $casts = ['applied_at' => 'datetime'];

    public function job(): BelongsTo { return $this->belongsTo(Job::class); }
    public function events(): HasMany { return $this->hasMany(ApplicationEvent::class); }

    /** Move status and record the transition for the funnel analytics. */
    public function transitionTo(string $status, ?string $note = null): void
    {
        $from = $this->status;
        $this->update(['status' => $status]);
        $this->events()->create([
            'from_status' => $from,
            'to_status'   => $status,
            'note'        => $note,
        ]);
    }
}

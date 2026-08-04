<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];

    /** Convenience recorder used across services. */
    public static function record(string $action, ?Model $subject = null, array $meta = []): self
    {
        return static::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id'   => $subject?->getKey(),
            'meta'         => $meta,
            'ip'           => request()?->ip(),
        ]);
    }
}

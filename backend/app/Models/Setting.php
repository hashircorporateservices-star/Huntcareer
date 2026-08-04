<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];
    protected $casts = ['is_encrypted' => 'boolean'];

    // Transparently decrypt/encrypt values flagged is_encrypted (e.g. OAuth tokens).
    public function getValueAttribute($raw)
    {
        return $this->is_encrypted && $raw ? decrypt($raw) : $raw;
    }

    public function setValueAttribute($val): void
    {
        $this->attributes['value'] = $this->is_encrypted ? encrypt($val) : $val;
    }
}

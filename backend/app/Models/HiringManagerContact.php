<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiringManagerContact extends Model
{
    protected $guarded = [];
    protected $casts = ['revealed' => 'boolean', 'revealed_at' => 'datetime'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    /** Hide contact details until the user spends a credit to reveal them. */
    public function toArray()
    {
        $data = parent::toArray();
        if (! $this->revealed) {
            $data['email'] = $data['linkedin_url'] = null;
            $data['locked'] = true;
        }
        return $data;
    }
}

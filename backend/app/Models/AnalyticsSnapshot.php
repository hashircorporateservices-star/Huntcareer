<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSnapshot extends Model
{
    protected $guarded = [];
    protected $casts = [
        'date'           => 'date',
        'by_country'     => 'array',
        'by_role_family' => 'array',
    ];
}

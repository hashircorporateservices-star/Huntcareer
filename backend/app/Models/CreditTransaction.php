<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['delta' => 'integer', 'created_at' => 'datetime'];
}

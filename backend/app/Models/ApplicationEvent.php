<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationEvent extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['occurred_at' => 'datetime'];
}

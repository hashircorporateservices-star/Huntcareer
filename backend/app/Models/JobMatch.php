<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobMatch extends Model
{
    protected $table = 'job_matches';
    protected $guarded = [];
    protected $casts = ['breakdown' => 'array', 'score' => 'integer'];
}

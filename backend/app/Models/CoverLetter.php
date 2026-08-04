<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverLetter extends Model
{
    protected $guarded = [];
    protected $casts = ['is_template' => 'boolean'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'status_code' => 'integer',
        'ok' => 'boolean',
        'checked_at' => 'datetime',
    ];
}

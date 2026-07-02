<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogEntry extends Model
{
    protected $guarded = [];

    protected $casts = [
        'entry_date' => 'date',
    ];
}

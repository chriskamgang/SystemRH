<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IosBetaRequest extends Model
{
    protected $fillable = [
        'email',
        'full_name',
        'status',
        'invited_at',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
    ];
}

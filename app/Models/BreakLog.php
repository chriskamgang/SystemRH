<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BreakLog extends Model
{
    protected $fillable = [
        'user_id',
        'campus_id',
        'break_start',
        'break_end',
        'duration_minutes',
        'date',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function isActive(): bool
    {
        return $this->break_end === null;
    }
}

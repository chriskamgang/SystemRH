<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campus_id',
        'date',
        'type',
        'is_justified',
        'justification',
        'justified_by',
        'justified_at',
    ];

    protected $casts = [
        'date' => 'date',
        'is_justified' => 'boolean',
        'justified_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function justifiedBy()
    {
        return $this->belongsTo(User::class, 'justified_by');
    }
}
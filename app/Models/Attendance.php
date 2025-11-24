<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campus_id',
        'unite_enseignement_id',
        'type',
        'timestamp',
        'latitude',
        'longitude',
        'accuracy',
        'is_late',
        'late_minutes',
        'device_info',
        'notes',
        'status',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'float',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'device_info' => 'array',
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

    public function tardiness()
    {
        return $this->hasOne(Tardiness::class);
    }

    public function uniteEnseignement()
    {
        return $this->belongsTo(UniteEnseignement::class, 'unite_enseignement_id');
    }
}
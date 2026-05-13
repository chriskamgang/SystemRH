<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class Attendance extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'campus_id',
        'unite_enseignement_id',
        'type',
        'shift', // Plage horaire: morning ou evening
        'timestamp',
        'latitude',
        'longitude',
        'accuracy',
        'is_late',
        'is_travel_late',
        'late_minutes',
        'is_half_day',
        'device_info',
        'notes',
        'status',
        'is_offline',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'float',
        'is_late' => 'boolean',
        'is_travel_late' => 'boolean',
        'late_minutes' => 'integer',
        'is_half_day' => 'boolean',
        'is_offline' => 'boolean',
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
        return $this->hasOne(Tardiness::class)->withoutGlobalScopes();
    }

    public function uniteEnseignement()
    {
        return $this->belongsTo(UniteEnseignement::class, 'unite_enseignement_id');
    }
}
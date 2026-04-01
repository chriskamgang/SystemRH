<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampusTravelTime extends Model
{
    protected $fillable = [
        'campus_from_id',
        'campus_to_id',
        'travel_minutes',
    ];

    protected $casts = [
        'travel_minutes' => 'integer',
    ];

    public function campusFrom()
    {
        return $this->belongsTo(Campus::class, 'campus_from_id');
    }

    public function campusTo()
    {
        return $this->belongsTo(Campus::class, 'campus_to_id');
    }

    /**
     * Obtenir le temps de trajet entre deux campus (dans les deux sens)
     */
    public static function getTravelMinutes($fromCampusId, $toCampusId, $default = 30): int
    {
        $record = self::where(function ($q) use ($fromCampusId, $toCampusId) {
            $q->where('campus_from_id', $fromCampusId)
              ->where('campus_to_id', $toCampusId);
        })->orWhere(function ($q) use ($fromCampusId, $toCampusId) {
            $q->where('campus_from_id', $toCampusId)
              ->where('campus_to_id', $fromCampusId);
        })->first();

        return $record ? $record->travel_minutes : $default;
    }
}

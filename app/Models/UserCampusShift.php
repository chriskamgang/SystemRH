<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCampusShift extends Model
{
    protected $fillable = [
        'user_id',
        'campus_id',
        'shift_type',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
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

    /**
     * Vérifier si c'est un shift du matin
     */
    public function isMorning(): bool
    {
        return $this->shift_type === 'morning';
    }

    /**
     * Vérifier si c'est un shift du soir
     */
    public function isEvening(): bool
    {
        return $this->shift_type === 'evening';
    }

    /**
     * Vérifier si c'est un shift toute la journée
     */
    public function isFullDay(): bool
    {
        return $this->shift_type === 'full_day';
    }
}

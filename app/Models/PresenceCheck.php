<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresenceCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campus_id',
        'check_time',
        'response',
        'response_time',
        'latitude',
        'longitude',
        'is_in_zone',
        'notification_sent',
        'notification_id',
    ];

    protected $casts = [
        'check_time' => 'datetime',
        'response_time' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_in_zone' => 'boolean',
        'notification_sent' => 'boolean',
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

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
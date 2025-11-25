<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeofenceNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campus_id',
        'sent_at',
        'action_taken',
        'action_at',
        'device_info',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'action_at' => 'datetime',
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
     * Vérifier si l'utilisateur peut recevoir une notification pour ce campus
     * (vérifie le cooldown)
     */
    public static function canSendNotification(int $userId, int $campusId): bool
    {
        $cooldownMinutes = \App\Models\Setting::get('geofence_notification_cooldown_minutes', 360);

        $lastNotification = self::where('user_id', $userId)
            ->where('campus_id', $campusId)
            ->where('sent_at', '>=', now()->subMinutes($cooldownMinutes))
            ->latest('sent_at')
            ->first();

        return $lastNotification === null;
    }
}

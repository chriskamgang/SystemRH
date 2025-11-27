<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLocation extends Model
{
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'accuracy',
        'device_info',
        'is_active',
        'last_updated_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'is_active' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour récupérer uniquement les utilisateurs actifs (app ouverte)
     * Actif = dernière mise à jour il y a moins de 2 minutes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('last_updated_at', '>=', now()->subMinutes(2));
    }

    /**
     * Scope pour récupérer les utilisateurs avec check-in actif
     * Un utilisateur est "checked-in" si son dernier pointage d'aujourd'hui est un check-in
     */
    public function scopeCheckedIn($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->whereHas('attendances', function ($q2) {
                // Sous-requête pour obtenir le dernier type de pointage d'aujourd'hui
                $q2->whereDate('timestamp', today())
                   ->where('type', 'check-in')
                   ->whereRaw('id = (
                       SELECT id FROM attendances a2
                       WHERE a2.user_id = attendances.user_id
                       AND DATE(a2.timestamp) = CURDATE()
                       ORDER BY a2.timestamp DESC
                       LIMIT 1
                   )');
            });
        });
    }

    /**
     * Vérifier si l'utilisateur est dans une zone campus
     */
    public function isInCampusZone(): ?Campus
    {
        $campuses = Campus::all();

        foreach ($campuses as $campus) {
            if ($campus->isUserInZone($this->latitude, $this->longitude)) {
                return $campus;
            }
        }

        return null;
    }

    /**
     * Mettre à jour ou créer la position d'un utilisateur
     */
    public static function updateOrCreateLocation(int $userId, array $locationData): self
    {
        return self::updateOrCreate(
            ['user_id' => $userId],
            array_merge($locationData, ['last_updated_at' => now()])
        );
    }
}

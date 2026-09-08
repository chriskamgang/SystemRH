<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alerte de proximite deja envoyee pour un couple tour / arret (CDC 3.1).
 *
 * Sert de verrou d'idempotence : la contrainte d'unicite en base garantit
 * qu'un etudiant n'est prevenu qu'une fois, meme si deux pings arrivent
 * simultanement.
 */
class ApprocheArret extends Model
{
    use HasFactory;

    protected $table = 'approches_arret';

    protected $fillable = ['tournee_id', 'lieu_id', 'distance_metres', 'notifie_le'];

    protected function casts(): array
    {
        return [
            'distance_metres' => 'integer',
            'notifie_le' => 'datetime',
        ];
    }

    public function tournee(): BelongsTo
    {
        return $this->belongsTo(Tournee::class);
    }

    public function lieu(): BelongsTo
    {
        return $this->belongsTo(Lieu::class);
    }
}

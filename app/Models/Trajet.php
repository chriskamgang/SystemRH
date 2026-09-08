<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trajet extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id', 'tournee_id', 'abonnement_id', 'embarque_le',
        'mode_validation', 'valide_par',
    ];

    protected function casts(): array
    {
        return ['embarque_le' => 'datetime'];
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function tournee(): BelongsTo
    {
        return $this->belongsTo(Tournee::class);
    }

    public function abonnement(): BelongsTo
    {
        return $this->belongsTo(Abonnement::class);
    }
}

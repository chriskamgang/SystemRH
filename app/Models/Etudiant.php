<?php

namespace App\Models;

use App\Enums\StatutAbonnement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etudiant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'matricule_insam', 'ligne_id', 'arret_id', 'lieu_ramassage_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(Ligne::class);
    }

    public function arret(): BelongsTo
    {
        return $this->belongsTo(Arret::class);
    }

    /**
     * Point de ramassage declare par l'etudiant.
     *
     * Son campus, lui, change au gre de l'emploi du temps : il n'est pas
     * enregistre dans le profil.
     */
    public function lieuRamassage(): BelongsTo
    {
        return $this->belongsTo(Lieu::class, 'lieu_ramassage_id');
    }

    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class);
    }

    public function trajets(): HasMany
    {
        return $this->hasMany(Trajet::class);
    }

    /** Abonnement couvrant la date du jour, s'il existe. */
    public function abonnementActif(): ?Abonnement
    {
        return $this->abonnements()
            ->where('statut', StatutAbonnement::Actif)
            ->whereDate('date_debut', '<=', today())
            ->whereDate('date_fin', '>=', today())
            ->latest('date_fin')
            ->first();
    }
}

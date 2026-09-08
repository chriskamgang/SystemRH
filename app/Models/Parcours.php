<?php

namespace App\Models;

use App\Enums\SensParcours;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Itinéraire d'une ligne dans un sens donné (CDC §3.2).
 *
 * L'aller mène les étudiants au campus le matin ; le retour les redépose
 * le soir, éventuellement à d'autres points que ceux du ramassage.
 */
class Parcours extends Model
{
    use HasFactory;

    protected $table = 'parcours';

    protected $fillable = [
        'ligne_id', 'sens', 'libelle', 'heure_depart',
        'duree_reference_minutes', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'sens' => SensParcours::class,
            'duree_reference_minutes' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(Ligne::class);
    }

    public function etapes(): HasMany
    {
        return $this->hasMany(EtapeParcours::class)->orderBy('ordre');
    }

    public function tournees(): HasMany
    {
        return $this->hasMany(Tournee::class);
    }

    /** Premier lieu desservi : là où le tour commence. */
    public function lieuDepart(): ?Lieu
    {
        return $this->etapes()->with('lieu')->first()?->lieu;
    }

    /** Dernier lieu : sa validation clôt le tour. */
    public function lieuTerminus(): ?Lieu
    {
        // `etapes()` trie deja par ordre croissant : un `orderByDesc` ne
        // ferait que s'ajouter en second critere, sans rien renverser. Le
        // tri est donc vide avant d'etre repose, faute de quoi le terminus
        // serait le premier arret et le controle de zone de cloture (3.4)
        // se laisserait valider n'importe ou sur le parcours.
        return $this->etapes()
            ->with('lieu')
            ->reorder('ordre', 'desc')
            ->first()
            ?->lieu;
    }

    public function getIntituleAttribute(): string
    {
        return $this->libelle
            ?? "{$this->ligne->nom} — {$this->sens->libelleCourt()}";
    }
}

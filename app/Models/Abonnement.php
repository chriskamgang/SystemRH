<?php

namespace App\Models;

use App\Enums\StatutAbonnement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Abonnement extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id', 'tarif_id', 'date_debut', 'date_fin', 'montant_paye_fcfa',
        'rappel_expiration_envoye_le',
        'trajets_restants', 'trajets_en_attente', 'montant_du_fcfa',
        'statut', 'moyen_paiement', 'reference_paiement', 'paye_le',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'rappel_expiration_envoye_le' => 'datetime',
            'montant_paye_fcfa' => 'integer',
            'trajets_restants' => 'integer',
            'trajets_en_attente' => 'integer',
            'montant_du_fcfa' => 'integer',
            'statut' => StatutAbonnement::class,
            'paye_le' => 'datetime',
        ];
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(Etudiant::class);
    }

    public function tarif(): BelongsTo
    {
        return $this->belongsTo(Tarif::class);
    }

    public function trajets(): HasMany
    {
        return $this->hasMany(Trajet::class);
    }

    /**
     * Somme restant a payer : le pass entier s'il n'a jamais ete regle,
     * la seule recharge s'il est deja actif.
     */
    public function montantARegler(): int
    {
        if ($this->statut === StatutAbonnement::EnAttente) {
            return $this->montant_paye_fcfa + $this->montant_du_fcfa;
        }

        return $this->statut === StatutAbonnement::Actif ? $this->montant_du_fcfa : 0;
    }

    /** Des trajets ont ete ajoutes a ce pass et attendent leur reglement. */
    public function porteUneRecharge(): bool
    {
        return $this->trajets_en_attente > 0;
    }

    /** Ce pass attend un paiement, qu'il soit neuf ou recharge. */
    public function attendUnPaiement(): bool
    {
        return $this->montantARegler() > 0;
    }

    public function estUtilisable(): bool
    {
        return $this->statut === StatutAbonnement::Actif
            && $this->date_debut->lte(today())
            && $this->date_fin->gte(today())
            && ($this->trajets_restants === null || $this->trajets_restants > 0);
    }
}

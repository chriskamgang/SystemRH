<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacatairePaymentDetail extends Model
{
    protected $fillable = [
        'payment_id',
        'unite_enseignement_id',
        'code_ue',
        'nom_matiere',
        'heures_saisies',
        'taux_horaire',
        'montant',
        'notes',
    ];

    protected $casts = [
        'heures_saisies' => 'decimal:2',
        'taux_horaire' => 'decimal:2',
        'montant' => 'decimal:2',
    ];

    /**
     * Relations
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(VacatairePayment::class, 'payment_id');
    }

    public function uniteEnseignement(): BelongsTo
    {
        return $this->belongsTo(UniteEnseignement::class, 'unite_enseignement_id');
    }

    /**
     * Méthodes helper
     */

    // Calculer le montant (heures × taux)
    public function calculerMontant(): float
    {
        return $this->heures_saisies * $this->taux_horaire;
    }

    // Formater le montant en FCFA
    public function formatMontant(): string
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }

    // Formater les heures
    public function formatHeures(): string
    {
        return number_format($this->heures_saisies, 2, ',', ' ') . 'h';
    }
}

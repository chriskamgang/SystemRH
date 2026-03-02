<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualPayrollAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'applied_by',
        'year',
        'month',
        'salaire_mensuel',
        'jours_travailles',
        'jours_total',
        'heures_retard',
        'minutes_retard',
        'prime',
        'deduction_manuelle',
        'salaire_journalier',
        'salaire_brut',
        'penalite_retard',
        'salaire_net',
        'montant_perdu',
        'pourcentage_presence',
        'notes',
        'status',
    ];

    protected $casts = [
        'salaire_mensuel' => 'decimal:2',
        'jours_travailles' => 'decimal:2',
        'jours_total' => 'decimal:2',
        'prime' => 'decimal:2',
        'deduction_manuelle' => 'decimal:2',
        'salaire_journalier' => 'decimal:2',
        'salaire_brut' => 'decimal:2',
        'penalite_retard' => 'decimal:2',
        'salaire_net' => 'decimal:2',
        'montant_perdu' => 'decimal:2',
        'pourcentage_presence' => 'decimal:2',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec l'utilisateur qui a appliqué l'ajustement
     */
    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * Scope pour filtrer par année et mois
     */
    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Scope pour filtrer les ajustements actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

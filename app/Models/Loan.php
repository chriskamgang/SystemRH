<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'monthly_amount',
        'amount_paid',
        'start_date',
        'reason',
        'status',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'completed_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Relation avec l'utilisateur (employé)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec l'admin qui a créé le prêt
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'admin qui a marqué le prêt comme terminé
     */
    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Scope pour les prêts actifs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope pour les prêts d'un utilisateur
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les prêts qui doivent être déduits ce mois
     */
    public function scopeForMonth($query, $year, $month)
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();

        return $query->where('status', 'active')
            ->where('start_date', '<=', $startOfMonth);
    }

    /**
     * Calculer le montant restant à rembourser
     */
    public function getRemainingAmountAttribute()
    {
        return max(0, $this->total_amount - $this->amount_paid);
    }

    /**
     * Calculer le nombre de mensualités restantes
     */
    public function getRemainingMonthsAttribute()
    {
        if ($this->monthly_amount <= 0) {
            return 0;
        }
        return ceil($this->remaining_amount / $this->monthly_amount);
    }

    /**
     * Calculer le nombre total de mensualités
     */
    public function getTotalMonthsAttribute()
    {
        if ($this->monthly_amount <= 0) {
            return 0;
        }
        return ceil($this->total_amount / $this->monthly_amount);
    }

    /**
     * Calculer le pourcentage de remboursement
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->total_amount <= 0) {
            return 100;
        }
        return round(($this->amount_paid / $this->total_amount) * 100, 2);
    }

    /**
     * Vérifier si le prêt doit être déduit pour un mois donné
     */
    public function shouldDeductForMonth($year, $month)
    {
        if ($this->status !== 'active') {
            return false;
        }

        $targetMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $startDate = Carbon::parse($this->start_date)->startOfMonth();

        return $targetMonth->greaterThanOrEqualTo($startDate);
    }

    /**
     * Calculer le montant à déduire pour un mois donné
     */
    public function getDeductionAmountForMonth($year, $month)
    {
        if (!$this->shouldDeductForMonth($year, $month)) {
            return 0;
        }

        $remaining = $this->remaining_amount;

        // Si le montant restant est inférieur à la mensualité, déduire seulement le reste
        return min($this->monthly_amount, $remaining);
    }
}

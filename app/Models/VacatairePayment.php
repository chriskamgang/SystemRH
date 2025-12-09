<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacatairePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'year',
        'month',
        'hourly_rate',
        'days_worked',
        'hours_worked',
        'total_late_minutes',
        'gross_amount',
        'late_penalty',
        'impot_retenu',
        'bonus',
        'net_amount',
        'status',
        'validated_at',
        'paid_at',
        'validated_by',
        'notes',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'days_worked' => 'decimal:2',
        'hours_worked' => 'decimal:2',
        'total_late_minutes' => 'integer',
        'gross_amount' => 'decimal:2',
        'late_penalty' => 'decimal:2',
        'impot_retenu' => 'decimal:2',
        'bonus' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'validated_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function details()
    {
        return $this->hasMany(VacatairePaymentDetail::class, 'payment_id');
    }

    /**
     * Méthodes helper
     */

    // Calculer le total depuis les détails
    public function calculerTotalFromDetails(): float
    {
        return $this->details()->sum('montant');
    }

    // Synchroniser le total avec les détails
    public function syncTotalFromDetails(): void
    {
        $total = $this->calculerTotalFromDetails();
        $this->update([
            'gross_amount' => $total,
            'net_amount' => $total - $this->late_penalty - $this->impot_retenu + $this->bonus,
        ]);
    }

    // Calculer l'impôt (5% du montant brut)
    public function calculerImpot(): float
    {
        return $this->gross_amount * 0.05;
    }

    // Obtenir les détails groupés par UE
    public function getDetailsGroupedByUE()
    {
        return $this->details()->with('uniteEnseignement')->get();
    }
}

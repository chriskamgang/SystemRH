<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualDeduction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'total_amount',
        'num_installments',
        'installment_number',
        'group_id',
        'reason',
        'month',
        'year',
        'status',
        'applied_by',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'total_amount' => 'float',
        'num_installments' => 'integer',
        'installment_number' => 'integer',
        'month' => 'integer',
        'year' => 'integer',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function installments()
    {
        return $this->hasMany(self::class, 'group_id', 'group_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForMonth($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function isInstallment(): bool
    {
        return $this->num_installments > 1;
    }

    public function getPaidInstallmentsCountAttribute(): int
    {
        if (!$this->group_id) return 0;
        return self::where('group_id', $this->group_id)
            ->where('status', 'active')
            ->count();
    }

    public function getRemainingAmountAttribute(): float
    {
        if (!$this->group_id) return 0;
        $paid = self::where('group_id', $this->group_id)
            ->where('status', 'active')
            ->sum('amount');
        return max(0, ($this->total_amount ?? 0) - $paid);
    }
}

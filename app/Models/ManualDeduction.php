<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualDeduction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
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
}

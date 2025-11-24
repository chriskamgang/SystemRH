<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'monthly_salary',
        'working_days',
        'days_worked',
        'days_not_worked',
        'days_justified',
        'total_late_minutes',
        'late_minutes_justified',
        'late_penalty_amount',
        'absence_deduction',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'status',
        'approved_at',
        'paid_at',
        'approved_by',
    ];

    protected $casts = [
        'monthly_salary' => 'decimal:2',
        'working_days' => 'decimal:2',
        'days_worked' => 'decimal:2',
        'days_not_worked' => 'decimal:2',
        'days_justified' => 'decimal:2',
        'total_late_minutes' => 'integer',
        'late_minutes_justified' => 'integer',
        'late_penalty_amount' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

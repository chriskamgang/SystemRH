<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrAnalyticsSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'year', 'month', 'total_employees', 'new_hires', 'departures',
        'turnover_rate', 'avg_attendance_rate', 'avg_late_rate',
        'total_leave_days', 'total_payroll', 'avg_evaluation_score',
        'training_completions', 'open_positions',
        'department_breakdown', 'employee_type_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'department_breakdown' => 'array',
            'employee_type_breakdown' => 'array',
        ];
    }
}

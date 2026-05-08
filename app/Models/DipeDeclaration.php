<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DipeDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'year', 'trimester', 'status',
        'total_gross', 'total_employee', 'total_employer',
        'employee_count', 'reference', 'submitted_by', 'submitted_at',
    ];

    protected $casts = ['submitted_at' => 'datetime'];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function getTrimesterLabelAttribute()
    {
        return 'T' . $this->trimester . ' ' . $this->year;
    }
}

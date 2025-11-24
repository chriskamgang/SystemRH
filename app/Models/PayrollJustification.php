<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollJustification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_by',
        'year',
        'month',
        'days_justified',
        'late_minutes_justified',
        'reason',
        'status',
    ];

    protected $casts = [
        'days_justified' => 'decimal:2',
        'late_minutes_justified' => 'integer',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

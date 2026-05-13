<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class Tardiness extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'campus_id',
        'attendance_id',
        'date',
        'scheduled_time',
        'actual_time',
        'late_minutes',
        'status',
        'justification',
    ];

    protected $casts = [
        'date' => 'date',
        'scheduled_time' => 'datetime:H:i:s',
        'actual_time' => 'datetime:H:i:s',
        'late_minutes' => 'float',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class)->withoutGlobalScopes();
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function justifiedBy()
    {
        return $this->belongsTo(User::class, 'justified_by')->withoutGlobalScopes();
    }
}
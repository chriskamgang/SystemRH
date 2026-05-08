<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'attachment',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public const TYPES = [
        'annual' => 'Congé annuel',
        'sick' => 'Congé maladie',
        'maternity' => 'Congé maternité',
        'paternity' => 'Congé paternité',
        'unpaid' => 'Congé sans solde',
        'family_event' => 'Événement familial',
        'other' => 'Autre',
    ];

    public const DEFAULT_BALANCES = [
        'annual' => 30,
        'sick' => 15,
        'maternity' => 90,
        'paternity' => 10,
        'unpaid' => 0, // illimité
        'family_event' => 10,
        'other' => 0,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class LeaveRequest extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
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
        return $this->belongsTo(User::class)->withoutGlobalScopes();
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withoutGlobalScopes();
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

    /**
     * Vérifier si un employé est en congé approuvé à une date donnée
     */
    public static function isUserOnLeave(int $userId, $date = null): bool
    {
        $date = $date ? \Carbon\Carbon::parse($date)->toDateString() : today()->toDateString();

        return self::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    /**
     * Récupérer le congé actif d'un employé à une date donnée
     */
    public static function getActiveLeave(int $userId, $date = null): ?self
    {
        $date = $date ? \Carbon\Carbon::parse($date)->toDateString() : today()->toDateString();

        return self::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }
}

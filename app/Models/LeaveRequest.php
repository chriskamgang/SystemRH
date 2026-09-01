<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;
use App\Services\LeaveCalculationService;

class LeaveRequest extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'family_event_type',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'attachment',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_comment',
        'interim_name',
        'interim_function',
        'interim_tasks',
        'manager_status',
        'manager_reviewed_by',
        'manager_reviewed_at',
        'manager_comment',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
            'manager_reviewed_at' => 'datetime',
        ];
    }

    public const TYPES = [
        'annual' => 'Congé annuel',
        'sick' => 'Congé maladie',
        'maternity' => 'Congé maternité',
        'paternity' => 'Congé paternité',
        'unpaid' => 'Congé sans solde',
        'family_event' => 'Événement familial',
        'work_accident' => 'Accident du travail',
        'other' => 'Autre',
    ];

    public const FAMILY_EVENT_TYPES = [
        'marriage' => 'Mariage du salarié (3j)',
        'birth' => 'Naissance d\'un enfant (3j)',
        'death_spouse' => 'Décès du conjoint (3j)',
        'death_parent' => 'Décès d\'un parent (3j)',
        'death_child' => 'Décès d\'un enfant (3j)',
        'child_marriage' => 'Mariage d\'un enfant (2j)',
    ];

    public const DEFAULT_BALANCES = [
        'annual' => 18, // Sera recalculé par LeaveCalculationService
        'sick' => 180, // 6 mois max
        'maternity' => 98, // 14 semaines
        'paternity' => 3,
        'unpaid' => 0,
        'family_event' => 10, // max 10j/an
        'work_accident' => 0, // selon arrêt médical
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

    public function managerReviewer()
    {
        return $this->belongsTo(User::class, 'manager_reviewed_by')->withoutGlobalScopes();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isAwaitingManager(): bool
    {
        return $this->status === 'pending' && $this->manager_status === 'pending';
    }

    public function isAwaitingRH(): bool
    {
        return $this->status === 'pending' && $this->manager_status === 'approved';
    }

    public function getTypeLabel(): string
    {
        $label = self::TYPES[$this->type] ?? $this->type;
        if ($this->type === 'family_event' && $this->family_event_type) {
            $eventLabel = LeaveCalculationService::FAMILY_EVENT_TYPES[$this->family_event_type]['label'] ?? '';
            if ($eventLabel) {
                $label .= ' - ' . $eventLabel;
            }
        }
        return $label;
    }

    public function getStatusLabel(): string
    {
        if ($this->status === 'pending') {
            if ($this->manager_status === 'pending') {
                return 'En attente du supérieur';
            }
            if ($this->manager_status === 'approved') {
                return 'En attente RH';
            }
            if ($this->manager_status === 'rejected') {
                return 'Refusé par le supérieur';
            }
            return 'En attente';
        }

        return match ($this->status) {
            'approved' => 'Approuvé',
            'rejected' => 'Refusé',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class Ticket extends Model
{
    use HasFactory, BelongsToCompany;

    // ──────────────────────────────────────────────
    // Dynamic DB-backed helpers (with constant fallback)
    // ──────────────────────────────────────────────

    /**
     * Get active services from DB, falling back to constants.
     */
    public static function getActiveServices(): array
    {
        try {
            $fromDb = TicketService::active()->get()->pluck('name', 'slug')->toArray();
            if (!empty($fromDb)) {
                return $fromDb;
            }
        } catch (\Exception $e) {
            // Table may not exist yet (pre-migration)
        }

        return self::SERVICES;
    }

    /**
     * Get active categories from DB, falling back to constants.
     */
    public static function getActiveCategories(): array
    {
        try {
            $fromDb = TicketCategory::active()->get()->pluck('name', 'slug')->toArray();
            if (!empty($fromDb)) {
                return $fromDb;
            }
        } catch (\Exception $e) {
            // Table may not exist yet (pre-migration)
        }

        return self::CATEGORIES;
    }

    /**
     * Relation: service (via slug match on target_service).
     */
    public function service()
    {
        return $this->belongsTo(TicketService::class, 'target_service', 'slug');
    }

    /**
     * Relation: category model (via slug match on category).
     */
    public function categoryModel()
    {
        return $this->belongsTo(TicketCategory::class, 'category', 'slug');
    }

    protected $fillable = [
        'company_id',
        'ticket_number',
        'user_id',
        'category',
        'target_service',
        'assigned_to_service',
        'assigned_to_user_id',
        'assigned_by',
        'priority',
        'subject',
        'description',
        'attachment_path',
        'status',
        'was_redirected',
        'redirect_reason',
        'assigned_at',
        'resolved_at',
        'closed_at',
        'satisfaction_rating',
        'satisfaction_comment',
    ];

    protected $casts = [
        'was_redirected' => 'boolean',
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public const SERVICES = [
        'rh' => 'Ressources Humaines',
        'scolarite' => 'Scolarité',
        'finance' => 'Finance',
        'technique' => 'Service Technique',
        'direction' => 'Direction',
        'general' => 'Services Généraux',
    ];

    public const CATEGORIES = [
        'rh' => 'Ressources Humaines',
        'scolarite' => 'Scolarité',
        'finance' => 'Finance',
        'technique' => 'Technique',
        'infrastructure' => 'Infrastructure',
        'autre' => 'Autre',
    ];

    public const PRIORITIES = [
        'low' => 'Basse',
        'medium' => 'Moyenne',
        'high' => 'Haute',
        'critical' => 'Critique',
    ];

    public const STATUSES = [
        'new' => 'Nouveau',
        'assigned' => 'Assigné',
        'in_progress' => 'En cours',
        'responded' => 'Répondu',
        'resolved' => 'Résolu',
        'closed' => 'Clôturé',
    ];

    public static function generateTicketNumber(): string
    {
        $year = now()->year;
        $last = static::where('ticket_number', 'like', "TK-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $nextNum = 1;
        if ($last) {
            $parts = explode('-', $last->ticket_number);
            $nextNum = (int) end($parts) + 1;
        }

        return sprintf("TK-%d-%04d", $year, $nextNum);
    }

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at', 'asc');
    }

    public function publicComments()
    {
        return $this->hasMany(TicketComment::class)
            ->whereIn('comment_type', ['public', 'response'])
            ->orderBy('created_at', 'asc');
    }

    // Helpers
    public function getServiceLabel(): string
    {
        $slug = $this->assigned_to_service ?? $this->target_service;
        $services = self::getActiveServices();
        return $services[$slug] ?? self::SERVICES[$slug] ?? $slug;
    }

    public function getCategoryLabel(): string
    {
        $categories = self::getActiveCategories();
        return $categories[$this->category] ?? self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getPriorityLabel(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getStatusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}

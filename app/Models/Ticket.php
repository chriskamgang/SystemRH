<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
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
        return self::SERVICES[$this->assigned_to_service ?? $this->target_service] ?? $this->target_service;
    }

    public function getCategoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
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

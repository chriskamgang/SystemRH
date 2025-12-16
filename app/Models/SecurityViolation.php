<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SecurityViolation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'violation_type',
        'device_info',
        'ip_address',
        'user_agent',
        'occurred_at',
        'status',
        'severity',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'violation_type' => 'array',
        'device_info' => 'array',
        'occurred_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Méthodes helper
     */
    public function getViolationTypesFormatted()
    {
        $types = [];
        $violations = $this->violation_type;

        if ($violations['vpn'] ?? false) $types[] = 'VPN';
        if ($violations['mock'] ?? false) $types[] = 'Fake GPS';
        if ($violations['root'] ?? false) $types[] = 'Root/Jailbreak';
        if ($violations['emulator'] ?? false) $types[] = 'Émulateur';
        if ($violations['gps_inconsistent'] ?? false) $types[] = 'GPS Incohérent';

        return implode(', ', $types);
    }

    public function calculateSeverity()
    {
        $violations = $this->violation_type;
        $score = 0;

        // Système de scoring
        if ($violations['vpn'] ?? false) $score += 3;
        if ($violations['mock'] ?? false) $score += 4;
        if ($violations['root'] ?? false) $score += 3;
        if ($violations['emulator'] ?? false) $score += 2;
        if ($violations['gps_inconsistent'] ?? false) $score += 2;

        if ($score >= 6) return 'critical';
        if ($score >= 4) return 'high';
        if ($score >= 2) return 'medium';
        return 'low';
    }

    public function getSeverityColorClass()
    {
        return match($this->severity) {
            'critical' => 'bg-red-600 text-white',
            'high' => 'bg-orange-500 text-white',
            'medium' => 'bg-yellow-500 text-white',
            'low' => 'bg-blue-500 text-white',
            default => 'bg-gray-500 text-white',
        };
    }

    public function getStatusColorClass()
    {
        return match($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'reviewed' => 'bg-blue-100 text-blue-800',
            'dismissed' => 'bg-gray-100 text-gray-800',
            'action_taken' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}

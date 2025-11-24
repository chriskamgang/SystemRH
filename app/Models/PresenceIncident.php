<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresenceIncident extends Model
{
    protected $fillable = [
        'user_id',
        'campus_id',
        'attendance_id',
        'unite_enseignement_id',
        'incident_date',
        'notification_sent_at',
        'response_deadline',
        'has_responded',
        'responded_at',
        'response_latitude',
        'response_longitude',
        'was_in_zone',
        'status',
        'validated_by',
        'validated_at',
        'admin_notes',
        'penalty_hours',
        'penalty_applied',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'responded_at' => 'datetime',
        'validated_at' => 'datetime',
        'has_responded' => 'boolean',
        'was_in_zone' => 'boolean',
        'penalty_applied' => 'boolean',
        'response_latitude' => 'decimal:8',
        'response_longitude' => 'decimal:8',
        'penalty_hours' => 'decimal:2',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function uniteEnseignement()
    {
        return $this->belongsTo(UniteEnseignement::class, 'unite_enseignement_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('incident_date', today());
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Helper methods
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isValidated()
    {
        return $this->status === 'validated';
    }

    public function markAsResponded($latitude, $longitude, $wasInZone)
    {
        $this->update([
            'has_responded' => true,
            'responded_at' => now(),
            'response_latitude' => $latitude,
            'response_longitude' => $longitude,
            'was_in_zone' => $wasInZone,
        ]);
    }

    public function validateIncident($adminId, $notes = null)
    {
        $this->update([
            'status' => 'validated',
            'validated_by' => $adminId,
            'validated_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    public function ignoreIncident($adminId, $notes = null)
    {
        $this->update([
            'status' => 'ignored',
            'validated_by' => $adminId,
            'validated_at' => now(),
            'admin_notes' => $notes,
        ]);
    }
}

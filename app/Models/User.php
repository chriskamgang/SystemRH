<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'hourly_rate',
        'monthly_salary',
        'photo',
        'employee_type',
        'volume_horaire_hebdomadaire',
        'jours_travail',
        'custom_start_time',
        'custom_end_time',
        'custom_late_tolerance',
        'department_id',
        'role_id',
        'is_active',
        'fcm_token',
        'device_id',
        'device_model',
        'device_os',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'jours_travail' => 'array',
            'volume_horaire_hebdomadaire' => 'decimal:2',
        ];
    }

    /**
     * Relations
     */

    // Relation avec Role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Relation avec Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Relation many-to-many avec Campus
    public function campuses()
    {
        return $this->belongsToMany(Campus::class, 'user_campus')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    // Campus principal
    public function primaryCampus()
    {
        return $this->belongsToMany(Campus::class, 'user_campus')
            ->wherePivot('is_primary', true)
            ->withTimestamps();
    }

    // Plages horaires assignées par campus
    public function campusShifts()
    {
        return $this->hasMany(UserCampusShift::class);
    }

    // Vérifier si l'utilisateur travaille une plage spécifique sur un campus
    public function worksShift($campusId, $shift)
    {
        $assignment = $this->campusShifts()->where('campus_id', $campusId)->first();

        if (!$assignment) {
            return false;
        }

        return $shift === 'morning' ? $assignment->works_morning : $assignment->works_evening;
    }

    // Permissions spécifiques de l'utilisateur
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withTimestamps();
    }

    // Pointages
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Vérifications de présence
    public function presenceChecks()
    {
        return $this->hasMany(PresenceCheck::class);
    }

    // Retards
    public function tardiness()
    {
        return $this->hasMany(Tardiness::class);
    }

    // Absences
    public function absences()
    {
        return $this->hasMany(Absence::class);
    }

    // Localisation en temps réel
    public function location()
    {
        return $this->hasOne(UserLocation::class);
    }

    // Notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Unités d'enseignement (pour les enseignants vacataires et semi-permanents)
    public function unitesEnseignement()
    {
        return $this->hasMany(UniteEnseignement::class, 'enseignant_id');
    }

    // UE activées uniquement
    public function unitesEnseignementActivees()
    {
        return $this->hasMany(UniteEnseignement::class, 'enseignant_id')
            ->where('statut', 'activee');
    }

    // UE non activées uniquement
    public function unitesEnseignementNonActivees()
    {
        return $this->hasMany(UniteEnseignement::class, 'enseignant_id')
            ->where('statut', 'non_activee');
    }

    /**
     * Accessors
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Helper methods
     */
    public function hasPermission($permissionName)
    {
        // Vérifier si l'utilisateur a la permission via son rôle ou directement
        return $this->role->permissions->contains('name', $permissionName) ||
               $this->permissions->contains('name', $permissionName);
    }

    public function isAdmin()
    {
        return $this->role->name === 'admin';
    }

    /**
     * Helper methods pour les semi-permanents
     */
    public function isSemiPermanent()
    {
        return $this->employee_type === 'semi_permanent';
    }

    public function isVacataire()
    {
        return $this->employee_type === 'enseignant_vacataire';
    }

    public function isTitulaire()
    {
        return $this->employee_type === 'enseignant_titulaire';
    }

    public function getVolumeHoraireHebdomadaire()
    {
        return $this->volume_horaire_hebdomadaire ?? 0;
    }

    public function getJoursTravail()
    {
        return $this->jours_travail ?? [];
    }

    public function travailleJour($jour)
    {
        // $jour peut être 'lundi', 'mardi', etc.
        $joursTravail = $this->getJoursTravail();
        return in_array(strtolower($jour), array_map('strtolower', $joursTravail));
    }

    public function getJoursTravailFormatte()
    {
        $jours = $this->getJoursTravail();
        if (empty($jours)) {
            return 'Non défini';
        }
        return implode(', ', array_map('ucfirst', $jours));
    }

    /**
     * Vérifier si l'utilisateur a des horaires personnalisés
     */
    public function hasCustomWorkHours()
    {
        return !empty($this->custom_start_time) && !empty($this->custom_end_time);
    }

    /**
     * Obtenir l'heure de début (personnalisée ou du campus)
     */
    public function getStartTime($campus = null)
    {
        if ($this->hasCustomWorkHours()) {
            return $this->custom_start_time;
        }
        return $campus ? $campus->start_time : null;
    }

    /**
     * Obtenir l'heure de fin (personnalisée ou du campus)
     */
    public function getEndTime($campus = null)
    {
        if ($this->hasCustomWorkHours()) {
            return $this->custom_end_time;
        }
        return $campus ? $campus->end_time : null;
    }

    /**
     * Obtenir la tolérance de retard (personnalisée ou du campus)
     */
    public function getLateTolerance($campus = null)
    {
        if ($this->custom_late_tolerance !== null) {
            return $this->custom_late_tolerance;
        }
        return $campus ? $campus->late_tolerance : 15; // 15 minutes par défaut
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $fillable = [
        'name',
        'code',
        'annee_academique',
        'numero_semestre',
        'date_debut',
        'date_fin',
        'is_active',
        'description',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Scope pour obtenir le semestre actif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour filtrer par année académique
     */
    public function scopeAnneeAcademique($query, $annee)
    {
        return $query->where('annee_academique', $annee);
    }

    /**
     * Relation avec les unités d'enseignement
     */
    public function unitesEnseignement()
    {
        return $this->hasMany(UniteEnseignement::class, 'semester_id');
    }

    /**
     * Vérifier si le semestre est en cours
     */
    public function estEnCours()
    {
        return $this->is_active &&
               now()->between($this->date_debut, $this->date_fin);
    }

    /**
     * Activer ce semestre et désactiver tous les autres
     */
    public function activate()
    {
        // Désactiver tous les autres semestres
        static::where('id', '!=', $this->id)->update(['is_active' => false]);

        // Activer celui-ci
        $this->update(['is_active' => true]);
    }
}

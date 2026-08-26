<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UeSeance extends Model
{
    protected $fillable = [
        'unite_enseignement_id',
        'numero',
        'titre',
        'objectif',
    ];

    public function uniteEnseignement()
    {
        return $this->belongsTo(UniteEnseignement::class);
    }

    public function validations()
    {
        return $this->hasMany(UeValidation::class);
    }

    public function validationPour($userId)
    {
        return $this->validations()->where('user_id', $userId)->first();
    }
}

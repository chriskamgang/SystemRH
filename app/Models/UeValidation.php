<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UeValidation extends Model
{
    protected $fillable = [
        'ue_seance_id',
        'user_id',
        'tp_effectue',
        'objectif_atteint',
        'observation',
        'validated_at',
    ];

    protected $casts = [
        'tp_effectue' => 'boolean',
        'objectif_atteint' => 'boolean',
        'validated_at' => 'datetime',
    ];

    public function seance()
    {
        return $this->belongsTo(UeSeance::class, 'ue_seance_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campus_id',
        'nom',
        'code',
        'nombre_pc',
        'capacite',
        'type',
        'is_active',
        'description',
    ];

    protected $casts = [
        'nombre_pc' => 'integer',
        'capacite' => 'integer',
        'is_active' => 'boolean',
    ];

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
}

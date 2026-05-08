<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'purpose',
        'file_path',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    const TYPE_LABELS = [
        'work' => 'Attestation de travail',
        'salary' => 'Attestation de salaire',
        'employment' => 'Certificat de travail',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function getTypeLabelAttribute()
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}

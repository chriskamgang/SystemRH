<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class TrainingProgram extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'title', 'description', 'type', 'category', 'duration_hours',
        'level', 'is_mandatory', 'is_active', 'thumbnail', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    const LEVEL_LABELS = [
        'beginner' => 'Debutant',
        'intermediate' => 'Intermediaire',
        'advanced' => 'Avance',
    ];

    const TYPE_LABELS = [
        'online' => 'En ligne',
        'presential' => 'Presentiel',
        'hybrid' => 'Hybride',
    ];

    public function sessions()
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function enrollments()
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    public function materials()
    {
        return $this->hasMany(TrainingMaterial::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }
}

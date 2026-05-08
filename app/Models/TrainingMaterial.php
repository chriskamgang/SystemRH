<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_program_id', 'title', 'description', 'type',
        'file_path', 'external_url', 'duration_minutes', 'sort_order', 'is_required',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function program()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function userProgress()
    {
        return $this->hasMany(TrainingMaterialProgress::class);
    }
}

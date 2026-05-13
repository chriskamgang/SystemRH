<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_program_id', 'trainer_name', 'trainer_id', 'location',
        'meeting_link', 'start_date', 'end_date', 'max_participants', 'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }

    public function program()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id')->withoutGlobalScopes();
    }

    public function enrollments()
    {
        return $this->hasMany(TrainingEnrollment::class);
    }
}

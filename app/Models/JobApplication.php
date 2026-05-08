<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id', 'candidate_name', 'candidate_email', 'candidate_phone',
        'cv_path', 'cover_letter', 'status', 'notes', 'rating',
        'interview_date', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'interview_date' => 'datetime',
        ];
    }

    const STATUS_LABELS = [
        'new' => 'Nouveau',
        'screening' => 'Pre-selection',
        'interview' => 'Entretien',
        'technical_test' => 'Test technique',
        'offer' => 'Offre',
        'hired' => 'Recrute',
        'rejected' => 'Rejete',
    ];

    const PIPELINE_ORDER = ['new', 'screening', 'interview', 'technical_test', 'offer', 'hired'];

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}

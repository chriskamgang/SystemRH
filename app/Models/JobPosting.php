<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class JobPosting extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id', 'title', 'description', 'department_id', 'location', 'contract_type',
        'salary_range', 'requirements', 'responsibilities', 'benefits',
        'status', 'positions_count', 'published_at', 'closes_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    const CONTRACT_LABELS = [
        'cdi' => 'CDI',
        'cdd' => 'CDD',
        'stage' => 'Stage',
        'vacataire' => 'Vacataire',
        'freelance' => 'Freelance',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'published' && (!$this->closes_at || $this->closes_at->isFuture());
    }

    public function getContractLabelAttribute(): string
    {
        return self::CONTRACT_LABELS[$this->contract_type] ?? $this->contract_type;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class EvaluationCampaign extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['company_id', 'title', 'description', 'year', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function criteria()
    {
        return $this->hasMany(EvaluationCriteria::class, 'campaign_id')->orderBy('sort_order');
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class, 'campaign_id');
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriteria extends Model
{
    use HasFactory;

    protected $table = 'evaluation_criteria';

    protected $fillable = ['campaign_id', 'name', 'description', 'max_score', 'weight', 'sort_order'];

    public function campaign()
    {
        return $this->belongsTo(EvaluationCampaign::class, 'campaign_id');
    }
}

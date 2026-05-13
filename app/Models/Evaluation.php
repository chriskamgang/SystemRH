<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id', 'employee_id', 'evaluator_id', 'status',
        'overall_score', 'employee_comments', 'evaluator_comments',
        'objectives_next_year', 'training_needs',
        'self_evaluated_at', 'evaluated_at', 'validated_at',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
        'self_evaluated_at' => 'datetime',
        'evaluated_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(EvaluationCampaign::class, 'campaign_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id')->withoutGlobalScopes();
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id')->withoutGlobalScopes();
    }

    public function scores()
    {
        return $this->hasMany(EvaluationScore::class);
    }

    public function calculateOverallScore()
    {
        $scores = $this->scores()->with('criteria')->get();
        if ($scores->isEmpty()) return 0;

        $totalWeighted = 0;
        $totalWeight = 0;

        foreach ($scores as $score) {
            $value = $score->evaluator_score ?? $score->employee_score ?? 0;
            $weight = $score->criteria->weight ?? 1;
            $maxScore = $score->criteria->max_score ?? 5;
            $totalWeighted += ($value / $maxScore) * $weight;
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? round(($totalWeighted / $totalWeight) * 5, 2) : 0;
    }
}

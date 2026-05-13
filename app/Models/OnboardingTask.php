<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'process_id', 'title', 'description', 'assigned_to',
        'status', 'due_date', 'completed_date', 'completed_by',
        'notes', 'sort_order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
    ];

    public function process()
    {
        return $this->belongsTo(OnboardingProcess::class, 'process_id');
    }

    public function completedByUser()
    {
        return $this->belongsTo(User::class, 'completed_by')->withoutGlobalScopes();
    }
}

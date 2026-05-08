<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingTemplateTask extends Model
{
    use HasFactory;

    protected $fillable = ['template_id', 'title', 'description', 'assigned_to', 'due_days', 'sort_order'];

    public function template()
    {
        return $this->belongsTo(OnboardingTemplate::class, 'template_id');
    }
}

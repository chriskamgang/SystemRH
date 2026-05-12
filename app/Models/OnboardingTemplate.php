<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;

class OnboardingTemplate extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'type', 'employee_type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function tasks()
    {
        return $this->hasMany(OnboardingTemplateTask::class, 'template_id')->orderBy('sort_order');
    }

    public function processes()
    {
        return $this->hasMany(OnboardingProcess::class, 'template_id');
    }
}

<?php

namespace App\Models\Traits;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // Appliquer le scope global pour filtrer par company_id
        static::addGlobalScope(new CompanyScope());

        // Auto-assigner le company_id a la creation
        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $model->company_id = session('current_company_id');
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

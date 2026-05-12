<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Ne rien filtrer si pas de company_id en session
        // (cas du login, commandes artisan, super admin sans switch)
        $companyId = session('current_company_id');

        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }

    /**
     * Extension pour permettre withoutCompanyScope() sur les queries
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutCompanyScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}

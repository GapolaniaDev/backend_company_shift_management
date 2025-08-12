<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (app()->bound('currentCompanyId') && app('currentCompanyId')) {
            $builder->where($model->getTable() . '.company_id', app('currentCompanyId'));
        }
    }
}
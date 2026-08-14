<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if ($organizationId = auth()->user()?->organization_id) {
            $builder->where($model->qualifyColumn('organization_id'), $organizationId);
        }
    }
}

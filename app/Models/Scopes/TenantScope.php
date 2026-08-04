<?php

namespace App\Models\Scopes;

use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/** @implements Scope<Model> */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->hasTenant()) {
            $builder->where($model->qualifyColumn('tenant_id'), $context->id());

            return;
        }

        $user = Auth::user();

        if ($user?->current_team_id && $context->setFromUser($user)) {
            $builder->where($model->qualifyColumn('tenant_id'), $context->id());
        }
    }
}

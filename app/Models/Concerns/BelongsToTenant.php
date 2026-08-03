<?php

namespace App\Models\Concerns;

use App\Domain\Tenancy\TenantContext;
use App\Models\Scopes\TenantScope;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            $context = app(TenantContext::class);

            if (empty($model->tenant_id) && $context->hasTenant()) {
                $model->tenant_id = $context->id();
            }
        });
    }

    /** @return BelongsTo<Team, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'tenant_id');
    }
}

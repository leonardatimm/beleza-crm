<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $description
 * @property int $duration_minutes
 * @property int $price_cents
 * @property bool $is_active
 */
#[Fillable(['tenant_id', 'name', 'description', 'duration_minutes', 'price_cents', 'is_active'])]
class Service extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'duration_minutes' => 'integer', 'price_cents' => 'integer'];
    }

    /** @return BelongsToMany<ProfessionalProfile, $this> */
    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(ProfessionalProfile::class, 'professional_service')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }
}

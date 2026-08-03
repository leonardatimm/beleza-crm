<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'professional_profile_id', 'weekday', 'starts_at', 'ends_at', 'is_active'])]
class ProfessionalAvailability extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return ['weekday' => 'integer', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<ProfessionalProfile, $this> */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class, 'professional_profile_id');
    }
}

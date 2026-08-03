<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $user_id
 * @property string $display_name
 * @property bool $is_active
 */
#[Fillable(['tenant_id', 'user_id', 'display_name', 'color', 'is_active'])]
class ProfessionalProfile extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Service, $this> */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'professional_service')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    /** @return HasMany<ProfessionalAvailability, $this> */
    public function availabilities(): HasMany
    {
        return $this->hasMany(ProfessionalAvailability::class);
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}

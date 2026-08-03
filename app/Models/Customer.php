<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property CarbonImmutable|null $birth_date
 * @property bool $is_active
 */
#[Fillable(['tenant_id', 'name', 'phone', 'normalized_phone', 'email', 'birth_date', 'notes', 'is_active'])]
class Customer extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Customer $customer): void {
            $customer->normalized_phone = $customer->phone
                ? preg_replace('/\D+/', '', $customer->phone)
                : null;
        });
    }

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'is_active' => 'boolean'];
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}

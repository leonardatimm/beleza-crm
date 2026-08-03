<?php

namespace App\Models;

use App\Domain\Scheduling\Enums\AppointmentStatus;
use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $customer_id
 * @property int $professional_profile_id
 * @property int $created_by
 * @property AppointmentStatus $status
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $completed_at
 * @property int $duration_minutes
 * @property int $total_cents
 * @property-read Customer $customer
 * @property-read ProfessionalProfile $professional
 */
#[Fillable(['tenant_id', 'customer_id', 'professional_profile_id', 'created_by', 'status', 'starts_at', 'ends_at', 'duration_minutes', 'total_cents', 'notes', 'cancellation_reason', 'cancelled_by', 'cancelled_at', 'completed_at'])]
class Appointment extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'duration_minutes' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<ProfessionalProfile, $this> */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class, 'professional_profile_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<AppointmentItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(AppointmentItem::class);
    }

    /** @return HasMany<AppointmentStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(AppointmentStatusHistory::class);
    }

    /** @return HasOne<FinancialEntry, $this> */
    public function receivable(): HasOne
    {
        return $this->hasOne(FinancialEntry::class)->where('origin_type', 'appointment');
    }
}

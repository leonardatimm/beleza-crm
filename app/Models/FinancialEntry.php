<?php

namespace App\Models;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Enums\FinancialEntryType;
use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $appointment_id
 * @property FinancialEntryType $type
 * @property FinancialEntryStatus $status
 * @property string $origin_type
 * @property string $description
 * @property int $amount_cents
 * @property CarbonImmutable $due_date
 * @property CarbonImmutable|null $paid_at
 */
#[Fillable(['tenant_id', 'appointment_id', 'category_id', 'payment_method_id', 'created_by', 'type', 'status', 'origin_type', 'description', 'amount_cents', 'due_date', 'paid_at', 'reversed_at', 'reversal_of_id', 'external_reference', 'idempotency_key'])]
class FinancialEntry extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'type' => FinancialEntryType::class,
            'status' => FinancialEntryStatus::class,
            'amount_cents' => 'integer',
            'due_date' => 'date',
            'paid_at' => 'immutable_datetime',
            'reversed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<FinancialCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class);
    }

    /** @return BelongsTo<PaymentMethod, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}

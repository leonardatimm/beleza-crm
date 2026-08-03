<?php

namespace App\Domain\Scheduling\Listeners;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Enums\FinancialEntryType;
use App\Domain\Scheduling\Events\AppointmentCompleted;
use App\Models\FinancialEntry;
use App\Models\Team;

class CreateReceivableForCompletedAppointment
{
    public function handle(AppointmentCompleted $event): void
    {
        $appointment = $event->appointment->fresh(['customer']);

        if (! $appointment) {
            return;
        }
        $timezone = Team::query()->findOrFail($appointment->tenant_id)->timezone;

        FinancialEntry::withoutGlobalScopes()->firstOrCreate(
            [
                'tenant_id' => $appointment->tenant_id,
                'appointment_id' => $appointment->id,
                'origin_type' => 'appointment',
            ],
            [
                'created_by' => $appointment->created_by,
                'type' => FinancialEntryType::Income,
                'status' => FinancialEntryStatus::Pending,
                'description' => 'Atendimento de '.$appointment->customer->name,
                'amount_cents' => $appointment->total_cents,
                'due_date' => $appointment->starts_at->setTimezone($timezone)->toDateString(),
                'idempotency_key' => 'appointment:'.$appointment->id.':receivable',
            ],
        );
    }
}

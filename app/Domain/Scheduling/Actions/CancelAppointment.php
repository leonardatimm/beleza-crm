<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelAppointment
{
    public function handle(Appointment $appointment, int $actorId, string $reason): Appointment
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Informe o motivo do cancelamento.']);
        }

        return DB::transaction(function () use ($appointment, $actorId, $reason): Appointment {
            $locked = Appointment::withoutGlobalScopes()->lockForUpdate()->findOrFail($appointment->id);

            if (! $locked->status->canTransitionTo(AppointmentStatus::Cancelled)) {
                throw ValidationException::withMessages(['status' => 'Este atendimento não pode ser cancelado no estado atual.']);
            }

            $from = $locked->status;
            $locked->update([
                'status' => AppointmentStatus::Cancelled,
                'cancellation_reason' => trim($reason),
                'cancelled_by' => $actorId,
                'cancelled_at' => now(),
            ]);
            $locked->statusHistory()->create([
                'tenant_id' => $locked->tenant_id,
                'changed_by' => $actorId,
                'from_status' => $from->value,
                'to_status' => AppointmentStatus::Cancelled->value,
                'reason' => trim($reason),
            ]);

            return $locked->refresh();
        }, 3);
    }
}

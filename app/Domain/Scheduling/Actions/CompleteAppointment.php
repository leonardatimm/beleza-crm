<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Enums\AppointmentStatus;
use App\Domain\Scheduling\Events\AppointmentCompleted;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteAppointment
{
    public function handle(Appointment $appointment, int $actorId): Appointment
    {
        return DB::transaction(function () use ($appointment, $actorId): Appointment {
            $locked = Appointment::withoutGlobalScopes()->lockForUpdate()->findOrFail($appointment->id);

            if ($locked->status === AppointmentStatus::Completed) {
                AppointmentCompleted::dispatch($locked);

                return $locked;
            }

            if (! $locked->status->canTransitionTo(AppointmentStatus::Completed)) {
                throw ValidationException::withMessages(['status' => 'Este atendimento não pode ser concluído no estado atual.']);
            }

            if ($locked->items()->count() === 0) {
                throw ValidationException::withMessages(['status' => 'O atendimento precisa ter ao menos um serviço válido.']);
            }

            $from = $locked->status;
            $locked->update(['status' => AppointmentStatus::Completed, 'completed_at' => now()]);
            $locked->statusHistory()->create([
                'tenant_id' => $locked->tenant_id,
                'changed_by' => $actorId,
                'from_status' => $from->value,
                'to_status' => AppointmentStatus::Completed->value,
            ]);

            AppointmentCompleted::dispatch($locked);

            return $locked->refresh();
        }, 3);
    }
}

<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Data\ScheduleAppointmentData;
use App\Domain\Scheduling\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ProfessionalAvailability;
use App\Models\ProfessionalProfile;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleAppointment
{
    public function handle(ScheduleAppointmentData $data): Appointment
    {
        $customerExists = Customer::withoutGlobalScopes()
            ->where('tenant_id', $data->tenantId)
            ->where('is_active', true)
            ->whereKey($data->customerId)
            ->exists();

        if (! $customerExists) {
            throw ValidationException::withMessages(['customerId' => 'Cliente inválido ou inativo.']);
        }

        $services = Service::withoutGlobalScopes()
            ->where('tenant_id', $data->tenantId)
            ->where('is_active', true)
            ->whereIn('id', array_unique($data->serviceIds))
            ->get();

        if ($services->count() !== count(array_unique($data->serviceIds)) || $services->isEmpty()) {
            throw ValidationException::withMessages(['serviceIds' => 'Selecione serviços válidos e ativos.']);
        }

        $professional = ProfessionalProfile::withoutGlobalScopes()
            ->where('tenant_id', $data->tenantId)
            ->where('is_active', true)
            ->find($data->professionalProfileId);

        if (! $professional) {
            throw ValidationException::withMessages(['professionalProfileId' => 'Profissional inválido ou inativo.']);
        }

        $duration = (int) $services->sum('duration_minutes');
        $total = (int) $services->sum('price_cents');
        $endsAt = $data->startsAt->addMinutes($duration);

        $this->ensureAvailable($data->tenantId, $professional->id, $data->startsAt, $endsAt);

        try {
            return DB::transaction(function () use ($data, $services, $duration, $total, $endsAt): Appointment {
                $this->ensureNoConflict($data->tenantId, $data->professionalProfileId, $data->startsAt, $endsAt);

                $appointment = Appointment::withoutGlobalScopes()->create([
                    'tenant_id' => $data->tenantId,
                    'customer_id' => $data->customerId,
                    'professional_profile_id' => $data->professionalProfileId,
                    'created_by' => $data->actorId,
                    'status' => AppointmentStatus::Scheduled,
                    'starts_at' => $data->startsAt,
                    'ends_at' => $endsAt,
                    'duration_minutes' => $duration,
                    'total_cents' => $total,
                    'notes' => $data->notes,
                ]);

                foreach ($services as $service) {
                    $appointment->items()->create([
                        'tenant_id' => $data->tenantId,
                        'service_id' => $service->id,
                        'service_name' => $service->name,
                        'duration_minutes' => $service->duration_minutes,
                        'price_cents' => $service->price_cents,
                    ]);
                }

                $appointment->statusHistory()->create([
                    'tenant_id' => $data->tenantId,
                    'changed_by' => $data->actorId,
                    'from_status' => null,
                    'to_status' => AppointmentStatus::Scheduled->value,
                ]);

                return $appointment->load(['customer', 'professional', 'items']);
            }, 3);
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23P01') {
                throw ValidationException::withMessages(['startsAt' => 'Este horário acabou de ser ocupado. Escolha outro horário.']);
            }

            throw $exception;
        }
    }

    private function ensureAvailable(int $tenantId, int $professionalId, CarbonImmutable $startsAt, CarbonImmutable $endsAt): void
    {
        $timezone = Team::query()->findOrFail($tenantId)->timezone;
        $localStart = $startsAt->setTimezone($timezone);
        $localEnd = $endsAt->setTimezone($timezone);

        if ($localStart->toDateString() !== $localEnd->toDateString()) {
            throw ValidationException::withMessages(['startsAt' => 'O atendimento deve iniciar e terminar no mesmo dia.']);
        }

        $availabilities = ProfessionalAvailability::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('professional_profile_id', $professionalId)
            ->where('weekday', $localStart->dayOfWeekIso)
            ->where('is_active', true)
            ->get();

        if ($availabilities->isNotEmpty() && ! $availabilities->contains(fn ($slot) => $slot->starts_at <= $localStart->format('H:i:s') && $slot->ends_at >= $localEnd->format('H:i:s')
        )) {
            throw ValidationException::withMessages(['startsAt' => 'Horário fora da disponibilidade do profissional.']);
        }

        $blocked = ScheduleBlock::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('professional_profile_id', $professionalId)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($blocked) {
            throw ValidationException::withMessages(['startsAt' => 'O profissional possui um bloqueio neste horário.']);
        }
    }

    private function ensureNoConflict(int $tenantId, int $professionalId, CarbonImmutable $startsAt, CarbonImmutable $endsAt, ?int $ignoreId = null): void
    {
        $conflict = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('professional_profile_id', $professionalId)
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->lockForUpdate()
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['startsAt' => 'Já existe um atendimento neste horário.']);
        }
    }
}

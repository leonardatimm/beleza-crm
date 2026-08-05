<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\ProfessionalAvailability;
use App\Models\ScheduleBlock;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class FindAvailableAppointmentSlots
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function handle(int $tenantId, int $professionalProfileId, CarbonImmutable $date, int $durationMinutes): array
    {
        if ($durationMinutes <= 0) {
            return $this->halfHourSlots();
        }

        $timezone = Team::query()->findOrFail($tenantId)->timezone;
        $dayStart = $date->setTimezone($timezone)->startOfDay();
        $dayEnd = $dayStart->addDay();
        $dayStartUtc = $dayStart->utc();
        $dayEndUtc = $dayEnd->utc();

        $availabilities = ProfessionalAvailability::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('professional_profile_id', $professionalProfileId)
            ->where('weekday', $dayStart->dayOfWeekIso)
            ->where('is_active', true)
            ->get();

        $appointments = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('professional_profile_id', $professionalProfileId)
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->where('starts_at', '<', $dayEndUtc)
            ->where('ends_at', '>', $dayStartUtc)
            ->get(['starts_at', 'ends_at']);

        $blocks = ScheduleBlock::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('professional_profile_id', $professionalProfileId)
            ->where('starts_at', '<', $dayEndUtc)
            ->where('ends_at', '>', $dayStartUtc)
            ->get(['starts_at', 'ends_at']);

        $slots = [];

        for ($startsAt = $dayStart; $startsAt->lt($dayEnd); $startsAt = $startsAt->addMinutes(30)) {
            $endsAt = $startsAt->addMinutes($durationMinutes);

            if ($endsAt->gt($dayEnd) || ! $this->fitsAvailability($availabilities, $startsAt, $endsAt)) {
                continue;
            }

            $startsAtUtc = $startsAt->utc();
            $endsAtUtc = $endsAt->utc();

            if ($this->overlaps($appointments, $startsAtUtc, $endsAtUtc) || $this->overlaps($blocks, $startsAtUtc, $endsAtUtc)) {
                continue;
            }

            $slots[] = [
                'value' => $startsAt->format('H:i'),
                'label' => $startsAt->format('H:i'),
            ];
        }

        return $slots;
    }

    /** @return array<int, array{value: string, label: string}> */
    private function halfHourSlots(): array
    {
        return collect(range(0, 47))
            ->map(function (int $slot): array {
                $minutes = $slot * 30;
                $time = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);

                return ['value' => $time, 'label' => $time];
            })
            ->all();
    }

    /** @param Collection<int, ProfessionalAvailability> $availabilities */
    private function fitsAvailability(Collection $availabilities, CarbonImmutable $startsAt, CarbonImmutable $endsAt): bool
    {
        return $availabilities->isEmpty() || $availabilities->contains(
            fn (ProfessionalAvailability $availability): bool => $availability->starts_at <= $startsAt->format('H:i:s')
                && $availability->ends_at >= $endsAt->format('H:i:s'),
        );
    }

    /** @param Collection<int, covariant Appointment|ScheduleBlock> $intervals */
    private function overlaps(Collection $intervals, CarbonImmutable $startsAt, CarbonImmutable $endsAt): bool
    {
        return $intervals->contains(
            fn ($interval): bool => $interval->starts_at < $endsAt && $interval->ends_at > $startsAt,
        );
    }
}

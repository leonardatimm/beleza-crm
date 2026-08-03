<?php

namespace App\Domain\Scheduling\Data;

use Carbon\CarbonImmutable;

readonly class ScheduleAppointmentData
{
    /** @param array<int> $serviceIds */
    public function __construct(
        public int $tenantId,
        public int $customerId,
        public int $professionalProfileId,
        public int $actorId,
        public CarbonImmutable $startsAt,
        public array $serviceIds,
        public ?string $notes = null,
    ) {}
}

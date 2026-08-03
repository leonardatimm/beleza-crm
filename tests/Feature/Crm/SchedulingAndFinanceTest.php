<?php

namespace Tests\Feature\Crm;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Scheduling\Actions\CompleteAppointment;
use App\Domain\Scheduling\Actions\ScheduleAppointment;
use App\Domain\Scheduling\Data\ScheduleAppointmentData;
use App\Domain\Scheduling\Enums\AppointmentStatus;
use App\Domain\Tenancy\TenantContext;
use App\Enums\TeamRole;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\FinancialEntry;
use App\Models\ProfessionalProfile;
use App\Models\Service;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SchedulingAndFinanceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_schedule_snapshots_services_and_rejects_overlap(): void
    {
        [$owner, $team, $profile, $customer, $service] = $this->fixture();
        $startsAt = CarbonImmutable::now($team->timezone)->next('monday')->setTime(10, 0)->utc();
        $action = app(ScheduleAppointment::class);
        $appointment = $action->handle($this->data($owner, $team, $profile, $customer, $service, $startsAt));

        $this->assertSame(60, $appointment->duration_minutes);
        $this->assertSame(8500, $appointment->total_cents);
        $this->assertSame('Maquiagem', $appointment->items->first()->service_name);

        $service->update(['name' => 'Maquiagem premium', 'price_cents' => 12000]);
        $this->assertDatabaseHas('appointment_items', ['appointment_id' => $appointment->id, 'service_name' => 'Maquiagem', 'price_cents' => 8500]);

        $this->expectException(ValidationException::class);
        $action->handle($this->data($owner, $team, $profile, $customer, $service, $startsAt->addMinutes(30)));
    }

    public function test_adjacent_appointments_are_allowed(): void
    {
        [$owner, $team, $profile, $customer, $service] = $this->fixture();
        $startsAt = CarbonImmutable::now($team->timezone)->next('tuesday')->setTime(10, 0)->utc();
        $action = app(ScheduleAppointment::class);
        $action->handle($this->data($owner, $team, $profile, $customer, $service, $startsAt));
        $second = $action->handle($this->data($owner, $team, $profile, $customer, $service, $startsAt->addHour()));

        $this->assertSame(2, Appointment::query()->count());
        $this->assertSame($startsAt->addHour()->timestamp, $second->starts_at->timestamp);
    }

    public function test_completing_twice_creates_exactly_one_receivable(): void
    {
        [$owner, $team, $profile, $customer, $service] = $this->fixture();
        $startsAt = CarbonImmutable::now($team->timezone)->next('wednesday')->setTime(10, 0)->utc();
        $appointment = app(ScheduleAppointment::class)->handle($this->data($owner, $team, $profile, $customer, $service, $startsAt));
        $action = app(CompleteAppointment::class);

        $action->handle($appointment, $owner->id);
        $action->handle($appointment->refresh(), $owner->id);

        $this->assertSame(AppointmentStatus::Completed, $appointment->refresh()->status);
        $this->assertSame(1, FinancialEntry::query()->where('appointment_id', $appointment->id)->count());
        $this->assertSame(FinancialEntryStatus::Pending, FinancialEntry::query()->first()->status);
        $this->assertSame(8500, FinancialEntry::query()->first()->amount_cents);
    }

    private function fixture(): array
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['timezone' => 'America/Sao_Paulo']);
        $team->memberships()->create(['user_id' => $owner->id, 'role' => TeamRole::Owner]);
        $owner->update(['current_team_id' => $team->id]);
        app(TenantContext::class)->set($team);
        $profile = ProfessionalProfile::query()->where('user_id', $owner->id)->firstOrFail();
        $customer = Customer::create(['name' => 'Mariana']);
        $service = Service::create(['name' => 'Maquiagem', 'duration_minutes' => 60, 'price_cents' => 8500]);

        return [$owner, $team, $profile, $customer, $service];
    }

    private function data(User $owner, Team $team, ProfessionalProfile $profile, Customer $customer, Service $service, CarbonImmutable $startsAt): ScheduleAppointmentData
    {
        return new ScheduleAppointmentData($team->id, $customer->id, $profile->id, $owner->id, $startsAt, [$service->id]);
    }
}

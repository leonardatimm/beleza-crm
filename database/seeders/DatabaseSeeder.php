<?php

namespace Database\Seeders;

use App\Domain\Scheduling\Actions\CompleteAppointment;
use App\Domain\Scheduling\Actions\ScheduleAppointment;
use App\Domain\Scheduling\Data\ScheduleAppointmentData;
use App\Domain\Tenancy\TenantContext;
use App\Models\Customer;
use App\Models\ProfessionalAvailability;
use App\Models\ProfessionalProfile;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Léo Demo',
            'email' => 'demo@belezacrm.test',
        ]);
        $team = $user->currentTeam;
        $team->update(['name' => 'Studio Violeta', 'timezone' => 'America/Sao_Paulo']);
        app(TenantContext::class)->set($team);

        $profile = ProfessionalProfile::firstOrCreate(
            ['tenant_id' => $team->id, 'user_id' => $user->id],
            ['display_name' => $user->name],
        );
        foreach (range(1, 5) as $weekday) {
            ProfessionalAvailability::firstOrCreate([
                'tenant_id' => $team->id,
                'professional_profile_id' => $profile->id,
                'weekday' => $weekday,
                'starts_at' => '09:00:00',
                'ends_at' => '18:00:00',
            ]);
        }

        $customers = collect(['Mariana Alves', 'Camila Souza', 'Ana Lima'])
            ->map(fn (string $name) => Customer::create(['name' => $name, 'phone' => '(11) 99999-'.fake()->numerify('####')]));
        $services = collect([
            ['name' => 'Design de sobrancelha', 'duration_minutes' => 45, 'price_cents' => 6500],
            ['name' => 'Maquiagem social', 'duration_minutes' => 90, 'price_cents' => 18000],
            ['name' => 'Corte + escova', 'duration_minutes' => 75, 'price_cents' => 12000],
        ])->map(fn (array $data) => Service::create($data));

        $startsAt = CarbonImmutable::now($team->timezone)->next('monday')->setTime(10, 0)->utc();
        app(ScheduleAppointment::class)->handle(new ScheduleAppointmentData(
            $team->id,
            $customers->first()->id,
            $profile->id,
            $user->id,
            $startsAt,
            [$services->first()->id],
        ));

        $completed = app(ScheduleAppointment::class)->handle(new ScheduleAppointmentData(
            $team->id,
            $customers->get(1)->id,
            $profile->id,
            $user->id,
            $startsAt->addHours(2),
            [$services->get(1)->id],
        ));
        app(CompleteAppointment::class)->handle($completed, $user->id);
    }
}

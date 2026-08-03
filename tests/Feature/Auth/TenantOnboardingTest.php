<?php

namespace Tests\Feature\Auth;

use App\Domain\Tenancy\Actions\CreateTenantAndMembershipAction;
use App\Domain\Tenancy\Data\CreateTenantData;
use App\Domain\Tenancy\Enums\BusinessType;
use App\Enums\TeamRole;
use App\Livewire\Onboarding\Establishment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_establishment_onboarding(): void
    {
        $this->get(route('onboarding.establishment'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_tenant_is_redirected_from_dashboard_to_onboarding(): void
    {
        $user = $this->userWithoutTenant();

        $this->actingAs($user)
            ->get('/qualquer-estabelecimento/dashboard')
            ->assertRedirect(route('onboarding.establishment'));
    }

    public function test_individual_can_create_tenant_and_owner_membership(): void
    {
        $user = $this->userWithoutTenant();

        $this->actingAs($user);

        Livewire::test(Establishment::class)
            ->set('business_type', 'individual')
            ->set('cpf', '529.982.247-25')
            ->set('professional_name', 'Léo Beauty')
            ->set('phone', '(47) 99999-1234')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect('/leo-beauty/dashboard');

        $tenant = Tenant::query()->sole();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'business_type' => 'individual',
            'document_type' => 'CPF',
            'document_number' => '52998224725',
            'trade_name' => 'Léo Beauty',
            'phone' => '47999991234',
            'responsible_cpf' => '52998224725',
        ]);
        $this->assertDatabaseHas('tenant_memberships', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => TeamRole::Owner->value,
        ]);
        $this->assertSame($tenant->id, $user->fresh()->current_team_id);
        $this->assertSame($tenant->id, session('tenant_id'));
        $this->assertFalse(session()->has('onboarding.pending'));
    }

    public function test_company_can_create_tenant_with_responsible_document(): void
    {
        $user = $this->userWithoutTenant('empresa@example.com');

        $this->actingAs($user);

        Livewire::test(Establishment::class)
            ->set('business_type', 'company')
            ->set('cnpj', '04.252.011/0001-10')
            ->set('legal_name', 'Beleza Exemplo LTDA')
            ->set('trade_name', 'Studio Exemplo')
            ->set('responsible_cpf', '529.982.247-25')
            ->set('phone', '(47) 3333-4444')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', [
            'business_type' => 'company',
            'document_type' => 'CNPJ',
            'document_number' => '04252011000110',
            'legal_name' => 'Beleza Exemplo LTDA',
            'trade_name' => 'Studio Exemplo',
            'responsible_cpf' => '52998224725',
        ]);
    }

    public function test_invalid_documents_are_rejected(): void
    {
        $this->actingAs($this->userWithoutTenant());

        Livewire::test(Establishment::class)
            ->set('business_type', 'individual')
            ->set('cpf', '111.111.111-11')
            ->set('professional_name', 'Nome Teste')
            ->set('phone', '(47) 99999-1234')
            ->call('save')
            ->assertHasErrors(['cpf']);

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('tenant_memberships', 0);
    }

    public function test_fields_from_inactive_business_type_are_not_validated(): void
    {
        $this->actingAs($this->userWithoutTenant());

        Livewire::test(Establishment::class)
            ->set('business_type', 'company')
            ->set('cnpj', 'CNPJ incompleto que ficou oculto')
            ->set('business_type', 'individual')
            ->set('cpf', '012.635.689-04')
            ->set('professional_name', 'Leonarda Timm')
            ->set('phone', '(47) 98855-1236')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect('/leonarda-timm/dashboard');

        $this->assertDatabaseHas('tenants', [
            'business_type' => 'individual',
            'document_type' => 'CPF',
            'document_number' => '01263568904',
        ]);
    }

    public function test_document_fields_are_mass_assignable_for_onboarding(): void
    {
        $tenant = new Tenant;

        $this->assertContains('document_type', $tenant->getFillable());
        $this->assertContains('document_number', $tenant->getFillable());
    }

    public function test_tenant_creation_is_rolled_back_when_membership_creation_fails(): void
    {
        $user = $this->userWithoutTenant();

        Event::listen('eloquent.creating: App\\Models\\Membership', function (): never {
            throw new RuntimeException('Membership failure');
        });

        try {
            app(CreateTenantAndMembershipAction::class)->handle($user, new CreateTenantData(
                businessType: BusinessType::Individual,
                phone: '(47) 99999-1234',
                cpf: '529.982.247-25',
                professionalName: 'Rollback Beauty',
            ));

            $this->fail('The simulated membership failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Membership failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseCount('tenant_memberships', 0);
        $this->assertNull($user->fresh()->current_team_id);
        $this->assertNull(session('tenant_id'));
    }

    private function userWithoutTenant(string $email = 'owner@example.com'): User
    {
        return User::query()->create([
            'name' => 'Owner Test',
            'email' => $email,
            'password' => 'password',
        ]);
    }
}

<?php

namespace Tests\Feature\Crm;

use App\Domain\Tenancy\TenantContext;
use App\Enums\TeamRole;
use App\Models\Customer;
use App\Models\FinancialEntry;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_scope_and_policy_isolate_customers_by_tenant(): void
    {
        $owner = User::factory()->create();
        $first = $this->createMembership($owner, TeamRole::Owner);
        $second = Team::factory()->create();

        Customer::withoutGlobalScopes()->create(['tenant_id' => $first->id, 'name' => 'Cliente A']);
        $foreign = Customer::withoutGlobalScopes()->create(['tenant_id' => $second->id, 'name' => 'Cliente B']);

        app(TenantContext::class)->set($first);
        $this->actingAs($owner);

        $this->assertSame(['Cliente A'], Customer::query()->pluck('name')->all());
        $this->assertFalse(Gate::forUser($owner)->allows('view', $foreign));
    }

    public function test_tenant_is_resolved_from_authenticated_users_current_team_when_context_is_empty(): void
    {
        $owner = User::factory()->create();
        $team = $this->createMembership($owner, TeamRole::Owner);

        app(TenantContext::class)->clear();

        $this->actingAs($owner);

        $customer = Customer::create(['name' => 'Thalles']);

        $this->assertSame($team->id, $customer->tenant_id);
        $this->assertSame(['Thalles'], Customer::query()->pluck('name')->all());
    }

    public function test_professional_cannot_access_financial_module(): void
    {
        $professional = User::factory()->create();
        $this->createMembership($professional, TeamRole::Professional);

        $this->assertFalse(Gate::forUser($professional)->allows('viewAny', FinancialEntry::class));
    }

    private function createMembership(User $user, TeamRole $role): Team
    {
        $team = Team::factory()->create();
        $team->memberships()->create(['user_id' => $user->id, 'role' => $role]);
        $user->update(['current_team_id' => $team->id]);
        $user->setRelation('currentTeam', $team);
        app(TenantContext::class)->set($team);

        return $team;
    }
}

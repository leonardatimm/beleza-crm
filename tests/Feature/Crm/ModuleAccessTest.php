<?php

namespace Tests\Feature\Crm;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_render_all_mvp_modules(): void
    {
        $owner = User::factory()->create();

        foreach (['dashboard', 'clients.index', 'services.index', 'schedule.index', 'finance.index'] as $route) {
            $this->actingAs($owner)->get(route($route))->assertOk();
        }
    }

    public function test_professional_receives_forbidden_on_financial_page(): void
    {
        $professional = User::factory()->create();
        $team = Team::factory()->create();
        $team->memberships()->create(['user_id' => $professional->id, 'role' => TeamRole::Professional]);
        $professional->update(['current_team_id' => $team->id]);

        $this->actingAs($professional)
            ->get(route('finance.index', ['current_team' => $team->slug]))
            ->assertForbidden();
    }
}

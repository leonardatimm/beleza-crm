<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\RegisterStepOne;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        Livewire::test(RegisterStepOne::class)
            ->set('name', 'John Doe')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('onboarding.establishment'));

        $user = User::where('email', 'test@example.com')->first();

        $this->assertAuthenticated();
        $this->assertNotNull($user);
        $this->assertNull($user->current_team_id);
        $this->assertDatabaseCount('tenants', 0);
        $this->assertTrue(session('onboarding.pending'));
    }
}

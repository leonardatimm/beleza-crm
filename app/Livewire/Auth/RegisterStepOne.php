<?php

namespace App\Livewire\Auth;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Criar sua conta')]
class RegisterStepOne extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(CreateNewUser $createNewUser): void
    {
        $user = $createNewUser->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        event(new Registered($user));

        Auth::login($user);
        Session::regenerate();
        Session::put('onboarding.pending', true);

        $this->redirectRoute('onboarding.establishment', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.register-step-one');
    }
}

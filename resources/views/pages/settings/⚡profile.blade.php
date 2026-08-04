<?php

use App\Concerns\ProfileValidationRules;
use App\Rules\BrazilianPhone as ValidBrazilianPhone;
use App\Support\BrazilianDocument;
use App\Support\BrazilianPhone;
/* @chisel-email-verification */
use Flux\Flux;
/* @end-chisel-email-verification */
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Perfil pessoal')] class extends Component
{
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->phone = BrazilianPhone::format(Auth::user()->phone ?? Auth::user()->currentTeam?->phone ?? '');
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            ...$this->profileRules($user->id),
            'phone' => ['nullable', 'string', new ValidBrazilianPhone],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ? BrazilianPhone::digits($validated['phone']) : null,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /* @chisel-email-verification */
    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $default = $user->currentTeam
                ? route('dashboard', ['current_team' => $user->currentTeam->slug], false)
                : route('onboarding.establishment', absolute: false);

            $this->redirectIntended(default: $default);

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }

    public function cpf(): string
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        $cpf = $user->cpf
            ?? $team?->responsible_cpf
            ?? ($team?->document_type === 'CPF' ? $team->document_number : null);

        return $cpf ? BrazilianDocument::formatCpf($cpf) : 'Não informado';
    }

    public function roleLabel(): string
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        return $team ? ($user->teamRole($team)?->label() ?? 'Sem função') : 'Sem estabelecimento';
    }
    /* @end-chisel-email-verification */
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">Perfil pessoal</flux:heading>

    <x-pages::settings.layout heading="Perfil pessoal" subheading="Atualize seus dados pessoais de acesso">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" label="Nome" type="text" required autofocus autocomplete="name" />

            <flux:input :value="$this->cpf()" label="CPF" type="text" readonly disabled />

            <div>
                <flux:input wire:model="email" label="E-mail" type="email" required autocomplete="email" />

                {{-- @chisel-email-verification --}}
                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
                {{-- @end-chisel-email-verification --}}
            </div>

            <flux:input wire:model="phone" label="Telefone" inputmode="tel" maxlength="15" placeholder="(00) 00000-0000" x-on:input="window.BelezaMasks.apply($event, 'phone')" />

            <flux:input :value="$this->roleLabel()" label="Função" type="text" readonly disabled />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

            </div>
        </form>

        {{-- @chisel-email-verification --}}
        @if ($this->showDeleteUser)
        {{-- @end-chisel-email-verification --}}
            <livewire:pages::settings.delete-user-form />
        {{-- @chisel-email-verification --}}
        @endif
        {{-- @end-chisel-email-verification --}}
    </x-pages::settings.layout>
</section>

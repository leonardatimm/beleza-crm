<?php

namespace App\Livewire\Onboarding;

use App\Domain\Tenancy\Actions\CreateTenantAndMembershipAction;
use App\Domain\Tenancy\Data\CreateTenantData;
use App\Domain\Tenancy\Enums\BusinessType;
use App\Http\Requests\Onboarding\StoreTenantOnboardingRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.auth')]
#[Title('Configure seu estabelecimento')]
class Establishment extends Component
{
    public string $business_type = 'individual';

    public string $cpf = '';

    public string $professional_name = '';

    public string $cnpj = '';

    public string $legal_name = '';

    public string $trade_name = '';

    public string $phone = '';

    public string $responsible_cpf = '';

    public function mount(): void
    {
        $user = Auth::user();

        if ($user?->currentTeam) {
            $this->redirectRoute('dashboard', ['current_team' => $user->currentTeam->slug], navigate: true);
        }
    }

    public function selectBusinessType(string $businessType): void
    {
        abort_unless(BusinessType::tryFrom($businessType) !== null, 422);

        $this->business_type = $businessType;
        $this->resetValidation();
    }

    public function save(CreateTenantAndMembershipAction $action): void
    {
        $validated = $this->validate(
            StoreTenantOnboardingRequest::rulesFor($this->business_type),
            attributes: StoreTenantOnboardingRequest::attributeNames(),
        );

        $tenant = $action->handle(
            Auth::user(),
            CreateTenantData::fromValidated($validated),
        );

        $this->redirectRoute('dashboard', ['current_team' => $tenant->slug], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.onboarding.establishment');
    }
}

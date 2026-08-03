<?php

use App\Domain\Scheduling\Actions\CancelAppointment;
use App\Domain\Scheduling\Actions\CompleteAppointment;
use App\Domain\Scheduling\Actions\ScheduleAppointment;
use App\Domain\Scheduling\Data\ScheduleAppointmentData;
use App\Domain\Scheduling\Enums\AppointmentStatus;
use App\Enums\TeamRole;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\ProfessionalProfile;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Agenda')] class extends Component {
    public string $selectedDate = '';
    public string $startsAt = '';
    public string $customerId = '';
    public string $professionalProfileId = '';
    public array $serviceIds = [];
    public string $notes = '';
    public array $cancelReasons = [];

    public function mount(): void
    {
        $timezone = Auth::user()->currentTeam->timezone;
        $today = now($timezone);
        $this->selectedDate = $today->toDateString();
        $this->startsAt = $today->addHour()->startOfHour()->format('Y-m-d\TH:i');
        $profile = ProfessionalProfile::query()->where('user_id', Auth::id())->first();
        $this->professionalProfileId = (string) ($profile?->id ?? ProfessionalProfile::query()->value('id') ?? '');
    }

    #[Computed] public function customers() { return Customer::query()->where('is_active', true)->orderBy('name')->get(); }
    #[Computed] public function services() { return Service::query()->where('is_active', true)->orderBy('name')->get(); }
    #[Computed] public function professionals() { return ProfessionalProfile::query()->where('is_active', true)->orderBy('display_name')->get(); }

    #[Computed]
    public function appointments()
    {
        Gate::authorize('viewAny', Appointment::class);
        $timezone = Auth::user()->currentTeam->timezone;
        $start = CarbonImmutable::parse($this->selectedDate, $timezone)->startOfDay()->utc();
        $end = $start->addDay();
        $query = Appointment::query()->with(['customer', 'professional', 'items'])->whereBetween('starts_at', [$start, $end])->orderBy('starts_at');
        if (in_array(Auth::user()->teamRole(Auth::user()->currentTeam), [TeamRole::Professional, TeamRole::Member], true)) {
            $query->whereHas('professional', fn ($query) => $query->where('user_id', Auth::id()));
        }
        return $query->get();
    }

    public function save(ScheduleAppointment $action): void
    {
        Gate::authorize('create', Appointment::class);
        $data = $this->validate([
            'customerId' => ['required', 'integer'],
            'professionalProfileId' => ['required', 'integer'],
            'startsAt' => ['required', 'date'],
            'serviceIds' => ['required', 'array', 'min:1'],
            'serviceIds.*' => ['integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $timezone = Auth::user()->currentTeam->timezone;
        $appointment = $action->handle(new ScheduleAppointmentData(
            tenantId: Auth::user()->current_team_id,
            customerId: (int) $data['customerId'],
            professionalProfileId: (int) $data['professionalProfileId'],
            actorId: Auth::id(),
            startsAt: CarbonImmutable::parse($data['startsAt'], $timezone)->utc(),
            serviceIds: array_map('intval', $data['serviceIds']),
            notes: $data['notes'] ?: null,
        ));
        $this->selectedDate = $appointment->starts_at->setTimezone($timezone)->toDateString();
        $this->reset('customerId', 'serviceIds', 'notes');
        unset($this->appointments);
        Flux::toast(variant: 'success', text: 'Agendamento criado com sucesso.');
    }

    public function complete(int $id, CompleteAppointment $action): void
    {
        $appointment = Appointment::findOrFail($id); Gate::authorize('complete', $appointment);
        $action->handle($appointment, Auth::id()); unset($this->appointments);
        Flux::toast(variant: 'success', text: 'Atendimento concluído e conta a receber gerada.');
    }

    public function cancel(int $id, CancelAppointment $action): void
    {
        $appointment = Appointment::findOrFail($id); Gate::authorize('cancel', $appointment);
        $reason = trim($this->cancelReasons[$id] ?? '');
        $action->handle($appointment, Auth::id(), $reason); unset($this->appointments);
        Flux::toast(variant: 'success', text: 'Agendamento cancelado.');
    }
}; ?>

<section class="mx-auto w-full max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><flux:heading size="xl">Agenda</flux:heading><flux:text>Atendimentos do dia e criação rápida de horários.</flux:text></div><div class="w-full sm:w-56"><flux:input wire:model.live="selectedDate" type="date" label="Dia da agenda" /></div></div>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_26rem]">
        <div class="space-y-3">
            @forelse($this->appointments as $appointment)
                <article class="rounded-2xl border border-violet-100 bg-white p-5 shadow-sm dark:border-violet-950 dark:bg-zinc-950" wire:key="appointment-{{ $appointment->id }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex gap-4"><div class="min-w-16 text-center"><p class="text-xl font-bold text-violet-700 dark:text-violet-400">{{ $appointment->starts_at->setTimezone(auth()->user()->currentTeam->timezone)->format('H:i') }}</p><p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $appointment->duration_minutes }} min</p></div><div><h3 class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $appointment->customer->name }}</h3><p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $appointment->professional->display_name }}</p><p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $appointment->items->pluck('service_name')->join(' + ') }}</p></div></div>
                        <div class="text-right"><flux:badge :color="match($appointment->status){AppointmentStatus::Completed => 'green', AppointmentStatus::Cancelled => 'zinc', AppointmentStatus::Confirmed => 'blue', default => 'violet'}">{{ $appointment->status->label() }}</flux:badge><p class="mt-2 font-semibold text-zinc-900 dark:text-zinc-50">R$ {{ number_format($appointment->total_cents / 100, 2, ',', '.') }}</p></div>
                    </div>
                    @if(!in_array($appointment->status, [AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow]))
                        <div class="mt-4 flex flex-col gap-2 border-t border-violet-50 pt-4 sm:flex-row sm:justify-end dark:border-violet-950"><flux:input wire:model="cancelReasons.{{ $appointment->id }}" size="sm" placeholder="Motivo para cancelar" class="sm:max-w-64" /><flux:button size="sm" variant="ghost" wire:click="cancel({{ $appointment->id }})">Cancelar</flux:button><flux:button size="sm" variant="primary" icon="check" wire:click="complete({{ $appointment->id }})" wire:confirm="Concluir o atendimento e gerar a conta a receber?">Concluir</flux:button></div>
                    @endif
                </article>
            @empty<div class="rounded-2xl border border-dashed border-violet-200 bg-white p-12 text-center dark:border-violet-900 dark:bg-zinc-950"><p class="font-medium text-zinc-700 dark:text-zinc-200">Agenda livre neste dia</p><p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Use o formulário ao lado para criar um atendimento.</p></div>@endforelse
        </div>
        <form wire:submit="save" class="h-fit space-y-4 rounded-2xl border border-violet-100 bg-white p-5 shadow-sm dark:border-violet-950 dark:bg-zinc-950">
            <flux:heading size="lg">Novo agendamento</flux:heading>
            <flux:select wire:model="customerId" label="Cliente" placeholder="Selecione" required>@foreach($this->customers as $customer)<flux:select.option value="{{ $customer->id }}">{{ $customer->name }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model="professionalProfileId" label="Profissional" required>@foreach($this->professionals as $professional)<flux:select.option value="{{ $professional->id }}">{{ $professional->display_name }}</flux:select.option>@endforeach</flux:select>
            <flux:input wire:model="startsAt" type="datetime-local" label="Data e hora" required />
            <fieldset><legend class="mb-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">Serviços</legend><div class="max-h-48 space-y-2 overflow-auto rounded-xl border border-zinc-200 p-3 dark:border-violet-900">@forelse($this->services as $service)<label class="flex cursor-pointer items-center justify-between gap-3 text-sm"><span><input class="mr-2 accent-violet-600" type="checkbox" wire:model="serviceIds" value="{{ $service->id }}">{{ $service->name }}</span><span class="text-zinc-500 dark:text-zinc-400">{{ $service->duration_minutes }} min · R$ {{ number_format($service->price_cents / 100, 2, ',', '.') }}</span></label>@empty<p class="text-sm text-zinc-500 dark:text-zinc-400">Cadastre serviços antes de agendar.</p>@endforelse</div>@error('serviceIds')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</fieldset>
            <flux:textarea wire:model="notes" label="Observações" rows="2" />
            <flux:button class="w-full" variant="primary" type="submit" icon="calendar-days">Criar agendamento</flux:button>
        </form>
    </div>
</section>

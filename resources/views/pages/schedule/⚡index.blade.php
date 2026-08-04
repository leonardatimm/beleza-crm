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
use Carbon\CarbonInterface;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Agenda')] class extends Component
{
    public string $selectedDate = '';

    public string $viewMode = 'day';

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

    #[Computed]
    public function customers()
    {
        return Customer::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function services()
    {
        return Service::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function professionals()
    {
        return ProfessionalProfile::query()->where('is_active', true)->orderBy('display_name')->get();
    }

    #[Computed]
    public function appointments()
    {
        Gate::authorize('viewAny', Appointment::class);
        $timezone = Auth::user()->currentTeam->timezone;
        [$start, $end] = $this->periodRange($timezone);
        $query = Appointment::query()
            ->with(['customer', 'professional', 'items'])
            ->where('starts_at', '>=', $start)
            ->where('starts_at', '<', $end)
            ->orderBy('starts_at');
        if (in_array(Auth::user()->teamRole(Auth::user()->currentTeam), [TeamRole::Professional, TeamRole::Member], true)) {
            $query->whereHas('professional', fn ($query) => $query->where('user_id', Auth::id()));
        }

        return $query->get();
    }

    #[Computed]
    public function calendarDays(): array
    {
        $timezone = Auth::user()->currentTeam->timezone;
        $date = CarbonImmutable::parse($this->selectedDate, $timezone);
        $firstDay = match ($this->viewMode) {
            'week' => $date->startOfWeek(CarbonInterface::MONDAY),
            'month' => $date->startOfMonth()->startOfWeek(CarbonInterface::MONDAY),
            default => $date->startOfDay(),
        };
        $numberOfDays = match ($this->viewMode) {
            'week' => 7,
            'month' => $firstDay->diffInDays($date->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY)) + 1,
            default => 1,
        };

        return collect(range(0, $numberOfDays - 1))
            ->map(fn (int $offset): array => [
                'key' => $firstDay->addDays($offset)->toDateString(),
                'day' => $firstDay->addDays($offset)->day,
                'weekday' => ucfirst($firstDay->addDays($offset)->locale('pt_BR')->isoFormat('ddd')),
                'isCurrentMonth' => $firstDay->addDays($offset)->month === $date->month,
                'isToday' => $firstDay->addDays($offset)->isToday(),
            ])
            ->all();
    }

    #[Computed]
    public function appointmentsByDay()
    {
        $timezone = Auth::user()->currentTeam->timezone;

        return $this->appointments->groupBy(
            fn (Appointment $appointment): string => $appointment->starts_at->setTimezone($timezone)->toDateString(),
        );
    }

    public function previousPeriod(): void
    {
        $timezone = Auth::user()->currentTeam->timezone;
        $date = CarbonImmutable::parse($this->selectedDate, $timezone);
        $this->selectedDate = match ($this->viewMode) {
            'week' => $date->subWeek()->toDateString(),
            'month' => $date->subMonthNoOverflow()->toDateString(),
            default => $date->subDay()->toDateString(),
        };
        $this->refreshCalendar();
    }

    public function nextPeriod(): void
    {
        $timezone = Auth::user()->currentTeam->timezone;
        $date = CarbonImmutable::parse($this->selectedDate, $timezone);
        $this->selectedDate = match ($this->viewMode) {
            'week' => $date->addWeek()->toDateString(),
            'month' => $date->addMonthNoOverflow()->toDateString(),
            default => $date->addDay()->toDateString(),
        };
        $this->refreshCalendar();
    }

    public function goToToday(): void
    {
        $this->selectedDate = now(Auth::user()->currentTeam->timezone)->toDateString();
        $this->refreshCalendar();
    }

    public function showDay(string $date): void
    {
        abort_unless(preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date) === 1, 404);

        $this->selectedDate = CarbonImmutable::parse($date, Auth::user()->currentTeam->timezone)->toDateString();
        $this->viewMode = 'day';
        $this->refreshCalendar();
    }

    public function updatedSelectedDate(): void
    {
        $this->refreshCalendar();
    }

    public function updatedViewMode(): void
    {
        abort_unless(in_array($this->viewMode, ['day', 'week', 'month'], true), 404);
        $this->refreshCalendar();
    }

    private function periodRange(string $timezone): array
    {
        $date = CarbonImmutable::parse($this->selectedDate, $timezone);
        $start = match ($this->viewMode) {
            'week' => $date->startOfWeek(CarbonInterface::MONDAY),
            'month' => $date->startOfMonth()->startOfWeek(CarbonInterface::MONDAY),
            default => $date->startOfDay(),
        };
        $end = match ($this->viewMode) {
            'week' => $start->addWeek(),
            'month' => $date->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY)->startOfDay()->addDay(),
            default => $start->addDay(),
        };

        return [$start->utc(), $end->utc()];
    }

    private function refreshCalendar(): void
    {
        unset($this->appointments, $this->appointmentsByDay, $this->calendarDays);
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
        $this->refreshCalendar();
        Flux::toast(variant: 'success', text: 'Agendamento criado com sucesso.');
    }

    public function complete(int $id, CompleteAppointment $action): void
    {
        $appointment = Appointment::findOrFail($id);
        Gate::authorize('complete', $appointment);
        $action->handle($appointment, Auth::id());
        $this->refreshCalendar();
        Flux::toast(variant: 'success', text: 'Atendimento concluído e conta a receber gerada.');
    }

    public function cancel(int $id, CancelAppointment $action): void
    {
        $appointment = Appointment::findOrFail($id);
        Gate::authorize('cancel', $appointment);
        $reason = trim($this->cancelReasons[$id] ?? '');
        $action->handle($appointment, Auth::id(), $reason);
        $this->refreshCalendar();
        Flux::toast(variant: 'success', text: 'Agendamento cancelado.');
    }
}; ?>

<section class="mx-auto w-full max-w-7xl space-y-6">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <flux:heading size="xl">Agenda</flux:heading>
            <flux:text>Organize seus atendimentos por dia, semana ou mês.</flux:text>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="w-full sm:w-48">
                <flux:input wire:model.live="selectedDate" type="date" label="Data de referência" />
            </div>
            <div class="flex items-center gap-1">
                <flux:button size="sm" variant="ghost" square icon="chevron-left" wire:click="previousPeriod" aria-label="Período anterior" />
                <flux:button size="sm" variant="ghost" wire:click="goToToday">Hoje</flux:button>
                <flux:button size="sm" variant="ghost" square icon="chevron-right" wire:click="nextPeriod" aria-label="Próximo período" />
            </div>
        </div>
    </div>

    <flux:radio.group variant="segmented" class="w-full sm:w-fit" wire:model.live="viewMode">
        <flux:radio value="day" icon="calendar-days">Dia</flux:radio>
        <flux:radio value="week" icon="calendar">Semana</flux:radio>
        <flux:radio value="month" icon="squares-2x2">Mês</flux:radio>
    </flux:radio.group>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_26rem]">
        <div>
            @if($viewMode === 'day')
                <div class="space-y-3">
                    @forelse($this->appointments as $appointment)
                        <article class="rounded-2xl border border-violet-100 bg-white p-5 shadow-sm dark:border-violet-950 dark:bg-zinc-950" wire:key="appointment-{{ $appointment->id }}">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex gap-4">
                                    <div class="min-w-16 text-center">
                                        <p class="text-xl font-bold text-violet-700 dark:text-violet-400">{{ $appointment->starts_at->setTimezone(auth()->user()->currentTeam->timezone)->format('H:i') }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $appointment->duration_minutes }} min</p>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $appointment->customer->name }}</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $appointment->professional->display_name }}</p>
                                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $appointment->items->pluck('service_name')->join(' + ') }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <flux:badge :color="match($appointment->status){AppointmentStatus::Completed => 'green', AppointmentStatus::Cancelled => 'zinc', AppointmentStatus::Confirmed => 'blue', default => 'violet'}">{{ $appointment->status->label() }}</flux:badge>
                                    <p class="mt-2 font-semibold text-zinc-900 dark:text-zinc-50">R$ {{ number_format($appointment->total_cents / 100, 2, ',', '.') }}</p>
                                </div>
                            </div>
                            @if(!in_array($appointment->status, [AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::NoShow]))
                                <div class="mt-4 flex flex-col gap-2 border-t border-violet-50 pt-4 sm:flex-row sm:justify-end dark:border-violet-950">
                                    <flux:input wire:model="cancelReasons.{{ $appointment->id }}" size="sm" placeholder="Motivo para cancelar" class="sm:max-w-64" />
                                    <flux:button size="sm" variant="ghost" wire:click="cancel({{ $appointment->id }})">Cancelar</flux:button>
                                    <flux:button size="sm" variant="primary" icon="check" wire:click="complete({{ $appointment->id }})" wire:confirm="Concluir o atendimento e gerar a conta a receber?">Concluir</flux:button>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-violet-200 bg-white p-12 text-center dark:border-violet-900 dark:bg-zinc-950">
                            <p class="font-medium text-zinc-700 dark:text-zinc-200">Agenda livre neste dia</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Use o formulário ao lado para criar um atendimento.</p>
                        </div>
                    @endforelse
                </div>
            @elseif($viewMode === 'week')
                <div class="overflow-x-auto rounded-2xl border border-violet-100 bg-white shadow-sm dark:border-violet-950 dark:bg-zinc-950">
                    <div class="grid min-w-[52rem] grid-cols-7 divide-x divide-violet-100 dark:divide-violet-950">
                        @foreach($this->calendarDays as $day)
                            <section class="min-h-[26rem] p-3" wire:key="week-day-{{ $day['key'] }}">
                                <button type="button" wire:click="showDay('{{ $day['key'] }}')" class="mb-3 w-full rounded-lg p-2 text-left hover:bg-violet-50 dark:hover:bg-violet-950/40">
                                    <span class="block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $day['weekday'] }}</span>
                                    <span @class(['mt-1 inline-flex size-8 items-center justify-center rounded-full text-sm font-semibold', 'bg-violet-600 text-white' => $day['isToday'], 'text-zinc-900 dark:text-zinc-50' => ! $day['isToday']])>{{ $day['day'] }}</span>
                                </button>
                                <div class="space-y-2">
                                    @forelse($this->appointmentsByDay->get($day['key'], collect()) as $appointment)
                                        <button type="button" wire:click="showDay('{{ $day['key'] }}')" class="w-full rounded-xl border border-violet-100 bg-violet-50/60 p-2 text-left transition hover:border-violet-300 dark:border-violet-900 dark:bg-violet-950/30 dark:hover:border-violet-700" wire:key="week-appointment-{{ $appointment->id }}">
                                            <p class="text-xs font-bold text-violet-700 dark:text-violet-300">{{ $appointment->starts_at->setTimezone(auth()->user()->currentTeam->timezone)->format('H:i') }}</p>
                                            <p class="mt-1 truncate text-sm font-medium text-zinc-900 dark:text-zinc-50">{{ $appointment->customer->name }}</p>
                                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $appointment->professional->display_name }}</p>
                                        </button>
                                    @empty
                                        <p class="pt-4 text-center text-xs text-zinc-400 dark:text-zinc-600">Livre</p>
                                    @endforelse
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="overflow-x-auto rounded-2xl border border-violet-100 bg-white shadow-sm dark:border-violet-950 dark:bg-zinc-950">
                    <div class="grid min-w-[52rem] grid-cols-7 border-b border-violet-100 dark:border-violet-950">
                        @foreach(['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $weekday)
                            <p class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $weekday }}</p>
                        @endforeach
                    </div>
                    <div class="grid min-w-[52rem] grid-cols-7">
                        @foreach($this->calendarDays as $day)
                            <section @class(['min-h-36 border-b border-r border-violet-100 p-2 dark:border-violet-950', 'bg-zinc-50/70 dark:bg-zinc-900/40' => ! $day['isCurrentMonth']]) wire:key="month-day-{{ $day['key'] }}">
                                <button type="button" wire:click="showDay('{{ $day['key'] }}')" @class(['flex size-7 items-center justify-center rounded-full text-sm font-medium hover:bg-violet-100 dark:hover:bg-violet-900', 'bg-violet-600 text-white hover:bg-violet-600 dark:hover:bg-violet-600' => $day['isToday'], 'text-zinc-400 dark:text-zinc-600' => ! $day['isCurrentMonth'], 'text-zinc-900 dark:text-zinc-50' => $day['isCurrentMonth'] && ! $day['isToday']])>{{ $day['day'] }}</button>
                                <div class="mt-2 space-y-1">
                                    @foreach($this->appointmentsByDay->get($day['key'], collect())->take(3) as $appointment)
                                        <button type="button" wire:click="showDay('{{ $day['key'] }}')" class="flex w-full gap-1 rounded-md bg-violet-50 px-1.5 py-1 text-left text-xs hover:bg-violet-100 dark:bg-violet-950/50 dark:hover:bg-violet-900/60" wire:key="month-appointment-{{ $appointment->id }}">
                                            <span class="shrink-0 font-bold text-violet-700 dark:text-violet-300">{{ $appointment->starts_at->setTimezone(auth()->user()->currentTeam->timezone)->format('H:i') }}</span>
                                            <span class="truncate text-zinc-700 dark:text-zinc-200">{{ $appointment->customer->name }}</span>
                                        </button>
                                    @endforeach
                                    @if($this->appointmentsByDay->get($day['key'], collect())->count() > 3)
                                        <button type="button" wire:click="showDay('{{ $day['key'] }}')" class="text-xs font-medium text-violet-700 hover:underline dark:text-violet-300">+ {{ $this->appointmentsByDay->get($day['key'])->count() - 3 }} atendimentos</button>
                                    @endif
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            @endif
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

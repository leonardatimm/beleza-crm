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

    #[Computed]
    public function monthTitle(): string
    {
        return ucfirst(CarbonImmutable::parse(
            $this->selectedDate,
            Auth::user()->currentTeam->timezone,
        )->locale('pt_BR')->isoFormat('MMMM [de] YYYY'));
    }

    #[Computed]
    public function monthPickerLabel(): string
    {
        $timezone = Auth::user()->currentTeam->timezone;
        $date = CarbonImmutable::parse($this->selectedDate, $timezone)->locale('pt_BR');

        return $date->year === now($timezone)->year
            ? ucfirst($date->isoFormat('MMMM'))
            : ucfirst($date->isoFormat('MMMM [de] YYYY'));
    }

    #[Computed]
    public function monthsOfCurrentYear(): array
    {
        $timezone = Auth::user()->currentTeam->timezone;
        $currentYear = now($timezone)->year;
        $selectedDate = CarbonImmutable::parse($this->selectedDate, $timezone);

        return collect(range(1, 12))
            ->map(fn (int $month): array => [
                'number' => $month,
                'label' => ucfirst(CarbonImmutable::create($currentYear, $month, 1, 0, 0, 0, $timezone)->locale('pt_BR')->isoFormat('MMMM')),
                'isSelected' => $selectedDate->year === $currentYear && $selectedDate->month === $month,
            ])
            ->all();
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

    public function selectMonth(int $month): void
    {
        abort_unless($month >= 1 && $month <= 12, 404);

        $timezone = Auth::user()->currentTeam->timezone;
        $this->selectedDate = CarbonImmutable::create(now($timezone)->year, $month, 1, 0, 0, 0, $timezone)->toDateString();
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
        $this->dispatch('close-modal', name: 'schedule-appointment');
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
    <div>
        <flux:heading size="xl">Agenda</flux:heading>
        <flux:text>Organize seus atendimentos por dia, semana ou mês.</flux:text>
    </div>

    <flux:radio.group variant="segmented" class="w-full sm:w-fit" wire:model.live="viewMode">
        <flux:radio value="day" icon="calendar-days">Dia</flux:radio>
        <flux:radio value="week" icon="calendar">Semana</flux:radio>
        <flux:radio value="month" icon="squares-2x2">Mês</flux:radio>
    </flux:radio.group>

    @if($viewMode === 'month')
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="w-full max-w-sm">
                <p class="mb-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">Mês da agenda</p>
                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="ghost" square icon="chevron-left" wire:click="previousPeriod" aria-label="Mês anterior" />

                    <flux:dropdown position="bottom" align="center" class="min-w-0 flex-1">
                        <button type="button" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-violet-200 bg-white px-4 font-semibold text-zinc-900 shadow-sm transition hover:border-violet-400 hover:bg-violet-50 focus:outline-none focus:ring-2 focus:ring-violet-500 dark:border-violet-900 dark:bg-zinc-950 dark:text-zinc-50 dark:hover:border-violet-700 dark:hover:bg-violet-950/40">
                            <span>{{ $this->monthPickerLabel }}</span>
                            <flux:icon name="chevron-down" variant="mini" class="size-4 text-violet-600 dark:text-violet-400" />
                        </button>

                        <flux:menu class="min-w-52">
                            <flux:menu.heading>Meses de {{ now(auth()->user()->currentTeam->timezone)->year }}</flux:menu.heading>
                            @foreach($this->monthsOfCurrentYear as $month)
                                <flux:menu.item as="button" type="button" wire:click="selectMonth({{ $month['number'] }})" :class="$month['isSelected'] ? 'bg-violet-50 text-violet-800 dark:bg-violet-950/60 dark:text-violet-200' : ''">
                                    {{ $month['label'] }}
                                </flux:menu.item>
                            @endforeach
                        </flux:menu>
                    </flux:dropdown>

                    <flux:button size="sm" variant="ghost" square icon="chevron-right" wire:click="nextPeriod" aria-label="Próximo mês" />
                </div>
            </div>

            <flux:modal.trigger name="schedule-appointment">
                <flux:button variant="primary" icon="calendar-days">Novo agendamento</flux:button>
            </flux:modal.trigger>
        </div>
    @else
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
    @endif

    <div @class(['grid gap-6', 'xl:grid-cols-[minmax(0,1fr)_26rem]' => $viewMode !== 'month'])>
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
                    <div class="grid divide-x divide-violet-100 dark:divide-violet-950" style="grid-template-columns: repeat(7, minmax(7.5rem, 1fr)); min-width: 52rem;">
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
                    <div class="flex items-center justify-between border-b border-violet-100 px-4 py-3 dark:border-violet-950" style="min-width: 52rem;">
                        <div>
                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-50">{{ $this->monthTitle }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Clique em um dia para abrir os atendimentos detalhados.</p>
                        </div>
                        <flux:badge color="violet">{{ $this->appointments->count() }} atendimentos</flux:badge>
                    </div>
                    <div class="grid border-b border-violet-100 bg-zinc-50/70 dark:border-violet-950 dark:bg-zinc-900/40" style="grid-template-columns: repeat(7, minmax(7.5rem, 1fr)); min-width: 52rem;">
                        @foreach(['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'] as $weekday)
                            <p class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $weekday }}</p>
                        @endforeach
                    </div>
                    <div class="grid" style="grid-template-columns: repeat(7, minmax(7.5rem, 1fr)); min-width: 52rem;">
                        @foreach($this->calendarDays as $day)
                            <section @class(['min-h-40 border-b border-r border-violet-100 p-2 transition-colors hover:bg-violet-50/40 dark:border-violet-950 dark:hover:bg-violet-950/20', 'bg-zinc-50/70 dark:bg-zinc-900/40' => ! $day['isCurrentMonth']]) wire:key="month-day-{{ $day['key'] }}">
                                <button type="button" wire:click="showDay('{{ $day['key'] }}')" @class(['flex size-7 items-center justify-center rounded-full text-sm font-medium transition-colors hover:bg-violet-100 dark:hover:bg-violet-900', 'bg-violet-600 text-white hover:bg-violet-600 dark:hover:bg-violet-600' => $day['isToday'], 'text-zinc-400 dark:text-zinc-600' => ! $day['isCurrentMonth'], 'text-zinc-900 dark:text-zinc-50' => $day['isCurrentMonth'] && ! $day['isToday']])>{{ $day['day'] }}</button>
                                <div class="mt-2 space-y-1.5">
                                    @foreach($this->appointmentsByDay->get($day['key'], collect())->take(3) as $appointment)
                                        <button type="button" wire:click="showDay('{{ $day['key'] }}')" @class(['flex w-full items-center gap-1.5 rounded-md border-l-2 px-2 py-1.5 text-left text-xs transition-colors', 'border-emerald-500 bg-emerald-50 text-emerald-900 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-100 dark:hover:bg-emerald-950/70' => $appointment->status === AppointmentStatus::Completed, 'border-zinc-400 bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' => $appointment->status === AppointmentStatus::Cancelled, 'border-violet-500 bg-violet-50 text-violet-950 hover:bg-violet-100 dark:bg-violet-950/50 dark:text-violet-100 dark:hover:bg-violet-900/60' => ! in_array($appointment->status, [AppointmentStatus::Completed, AppointmentStatus::Cancelled], true)]) wire:key="month-appointment-{{ $appointment->id }}">
                                            <span class="shrink-0 font-bold">{{ $appointment->starts_at->setTimezone(auth()->user()->currentTeam->timezone)->format('H:i') }}</span>
                                            <span class="truncate font-medium">{{ $appointment->customer->name }}</span>
                                        </button>
                                    @endforeach
                                    @if($this->appointmentsByDay->get($day['key'], collect())->count() > 3)
                                        <button type="button" wire:click="showDay('{{ $day['key'] }}')" class="px-1 text-xs font-semibold text-violet-700 hover:underline dark:text-violet-300">+ {{ $this->appointmentsByDay->get($day['key'])->count() - 3 }} atendimentos</button>
                                    @endif
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        @if($viewMode !== 'month')
            <x-schedule.appointment-form class="h-fit rounded-2xl border border-violet-100 bg-white p-5 shadow-sm dark:border-violet-950 dark:bg-zinc-950" />
        @endif
    </div>

    @if($viewMode === 'month')
        <flux:modal name="schedule-appointment" :show="$errors->isNotEmpty()" focusable class="max-w-xl">
            <div class="mb-5">
                <flux:heading size="lg">Novo agendamento</flux:heading>
                <flux:subheading>Preencha os dados para reservar um horário.</flux:subheading>
            </div>

            <x-schedule.appointment-form :show-heading="false" />
        </flux:modal>
    @endif
</section>

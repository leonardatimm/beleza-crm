<?php

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Enums\FinancialEntryType;
use App\Domain\Scheduling\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\FinancialEntry;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Visão geral')] class extends Component {
    #[Computed] public function todayAppointments() { return Appointment::query()->with(['customer', 'professional'])->whereDate('starts_at', now()->toDateString())->where('status', '!=', AppointmentStatus::Cancelled)->orderBy('starts_at')->get(); }
    #[Computed] public function activeCustomers(): int { return Customer::query()->where('is_active', true)->count(); }
    #[Computed] public function activeServices(): int { return Service::query()->where('is_active', true)->count(); }
    #[Computed] public function pendingReceivables(): int { return (int) FinancialEntry::query()->where('type', FinancialEntryType::Income)->where('status', FinancialEntryStatus::Pending)->sum('amount_cents'); }
}; ?>

<section class="mx-auto w-full max-w-7xl space-y-6">
    <div><flux:heading size="xl">Olá, {{ str(auth()->user()->name)->before(' ') }}!</flux:heading><flux:text>Aqui está o resumo de {{ auth()->user()->currentTeam->name }}.</flux:text></div>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><div class="metric-card"><span>Atendimentos hoje</span><strong>{{ $this->todayAppointments->count() }}</strong></div><div class="metric-card"><span>Clientes ativos</span><strong>{{ $this->activeCustomers }}</strong></div><div class="metric-card"><span>Serviços ativos</span><strong>{{ $this->activeServices }}</strong></div><div class="metric-card"><span>A receber</span><strong class="text-violet-700 dark:text-violet-400">R$ {{ number_format($this->pendingReceivables / 100, 2, ',', '.') }}</strong></div></div>
    <div class="rounded-2xl border border-violet-100 bg-white p-5 shadow-sm dark:border-violet-950 dark:bg-zinc-950"><div class="mb-4 flex items-center justify-between"><flux:heading size="lg">Agenda de hoje</flux:heading><flux:button variant="ghost" :href="route('schedule.index')" wire:navigate>Ver agenda</flux:button></div><div class="space-y-2">@forelse($this->todayAppointments as $appointment)<div class="flex items-center justify-between rounded-xl bg-violet-50/70 px-4 py-3 dark:bg-violet-950/30"><div><p class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $appointment->customer->name }}</p><p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $appointment->professional->display_name }}</p></div><div class="text-right"><p class="font-bold text-violet-700 dark:text-violet-400">{{ $appointment->starts_at->setTimezone(auth()->user()->currentTeam->timezone)->format('H:i') }}</p><p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $appointment->status->label() }}</p></div></div>@empty<p class="py-8 text-center text-zinc-500 dark:text-zinc-400">Nenhum atendimento agendado para hoje.</p>@endforelse</div></div>
</section>

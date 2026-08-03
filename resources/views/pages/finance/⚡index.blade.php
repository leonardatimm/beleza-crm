<?php

use App\Domain\Finance\Actions\CreateManualFinancialEntry;
use App\Domain\Finance\Actions\MarkReceivableAsPaid;
use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Enums\FinancialEntryType;
use App\Models\FinancialCategory;
use App\Models\FinancialEntry;
use App\Models\PaymentMethod;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Financeiro')] class extends Component {
    public string $month = '';
    public string $type = 'expense';
    public string $description = '';
    public string $amount = '';
    public string $dueDate = '';
    public string $categoryId = '';
    public array $paymentMethodsByEntry = [];

    public function mount(): void
    {
        Gate::authorize('viewAny', FinancialEntry::class);
        $this->month = now(Auth::user()->currentTeam->timezone)->format('Y-m');
        $this->dueDate = now(Auth::user()->currentTeam->timezone)->toDateString();
        foreach (['Dinheiro', 'Pix', 'Cartão de débito', 'Cartão de crédito'] as $name) {
            PaymentMethod::firstOrCreate(['name' => $name]);
        }
        FinancialCategory::firstOrCreate(['name' => 'Atendimentos', 'type' => FinancialEntryType::Income]);
        FinancialCategory::firstOrCreate(['name' => 'Despesas gerais', 'type' => FinancialEntryType::Expense]);
    }

    #[Computed]
    public function entries()
    {
        [$year, $month] = array_pad(explode('-', $this->month), 2, null);
        return FinancialEntry::query()->with(['appointment.customer', 'paymentMethod'])
            ->when($year && $month, fn ($query) => $query->whereYear('due_date', $year)->whereMonth('due_date', $month))
            ->orderByDesc('due_date')->orderByDesc('id')->limit(200)->get();
    }

    #[Computed] public function methods() { return PaymentMethod::query()->where('is_active', true)->orderBy('name')->get(); }
    #[Computed] public function categories() { return FinancialCategory::query()->where('is_active', true)->where('type', $this->type)->orderBy('name')->get(); }
    #[Computed] public function expectedIncome(): int { return (int) $this->entries->where('type', FinancialEntryType::Income)->whereIn('status', [FinancialEntryStatus::Pending, FinancialEntryStatus::Paid])->sum('amount_cents'); }
    #[Computed] public function received(): int { return (int) $this->entries->where('type', FinancialEntryType::Income)->where('status', FinancialEntryStatus::Paid)->sum('amount_cents'); }
    #[Computed] public function expenses(): int { return (int) $this->entries->where('type', FinancialEntryType::Expense)->where('status', FinancialEntryStatus::Paid)->sum('amount_cents'); }

    public function create(CreateManualFinancialEntry $action): void
    {
        Gate::authorize('create', FinancialEntry::class);
        $data = $this->validate([
            'type' => ['required', 'in:income,expense'], 'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999'], 'dueDate' => ['required', 'date'],
            'categoryId' => ['nullable', 'integer'],
        ]);
        $action->handle(Auth::user()->current_team_id, Auth::id(), FinancialEntryType::from($data['type']), $data['description'], (int) round(((float) str_replace(',', '.', $data['amount'])) * 100), $data['dueDate'], $data['categoryId'] ? (int) $data['categoryId'] : null);
        $this->reset('description', 'amount', 'categoryId'); unset($this->entries); Flux::toast(variant: 'success', text: 'Lançamento criado.');
    }

    public function markPaid(int $id, MarkReceivableAsPaid $action): void
    {
        $entry = FinancialEntry::findOrFail($id); Gate::authorize('update', $entry);
        $methodId = (int) ($this->paymentMethodsByEntry[$id] ?? 0);
        $action->handle($entry, $methodId); unset($this->entries); Flux::toast(variant: 'success', text: 'Pagamento registrado.');
    }
}; ?>

<section class="mx-auto w-full max-w-7xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><flux:heading size="xl">Financeiro</flux:heading><flux:text>Contas a receber, receitas, despesas e fluxo de caixa.</flux:text></div><div class="w-full sm:w-48"><flux:input wire:model.live="month" type="month" label="Competência" /></div></div>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="metric-card"><span>Receita prevista</span><strong>R$ {{ number_format($this->expectedIncome / 100, 2, ',', '.') }}</strong></div>
        <div class="metric-card"><span>Recebido</span><strong class="text-emerald-700 dark:text-emerald-400">R$ {{ number_format($this->received / 100, 2, ',', '.') }}</strong></div>
        <div class="metric-card"><span>Despesas pagas</span><strong class="text-rose-700 dark:text-rose-400">R$ {{ number_format($this->expenses / 100, 2, ',', '.') }}</strong></div>
        <div class="metric-card"><span>Saldo realizado</span><strong class="text-violet-700 dark:text-violet-400">R$ {{ number_format(($this->received - $this->expenses) / 100, 2, ',', '.') }}</strong></div>
    </div>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="overflow-hidden rounded-2xl border border-violet-100 bg-white shadow-sm dark:border-violet-950 dark:bg-zinc-950">
            <div class="divide-y divide-violet-50 dark:divide-violet-950">
                @forelse($this->entries as $entry)
                    <div class="p-4" wire:key="entry-{{ $entry->id }}"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><div class="flex items-center gap-2"><p class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $entry->description }}</p><flux:badge :color="$entry->status === FinancialEntryStatus::Paid ? 'green' : ($entry->status === FinancialEntryStatus::Pending ? 'amber' : 'zinc')">{{ $entry->status->label() }}</flux:badge></div><p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Vencimento {{ $entry->due_date->format('d/m/Y') }} · {{ $entry->origin_type === 'appointment' ? 'Atendimento' : 'Manual' }}</p></div><p class="text-lg font-bold {{ $entry->type === FinancialEntryType::Income ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">{{ $entry->type === FinancialEntryType::Income ? '+' : '-' }} R$ {{ number_format($entry->amount_cents / 100, 2, ',', '.') }}</p></div>
                        @if($entry->status === FinancialEntryStatus::Pending)<div class="mt-3 flex justify-end gap-2"><select wire:model="paymentMethodsByEntry.{{ $entry->id }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-violet-900 dark:bg-zinc-950 dark:text-zinc-100"><option value="">Forma de pagamento</option>@foreach($this->methods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</select><flux:button size="sm" variant="primary" wire:click="markPaid({{ $entry->id }})">Dar baixa</flux:button></div>@endif
                    </div>
                @empty<div class="p-10 text-center text-zinc-500 dark:text-zinc-400">Nenhum lançamento nesta competência.</div>@endforelse
            </div>
        </div>
        <form wire:submit="create" class="h-fit space-y-4 rounded-2xl border border-violet-100 bg-white p-5 shadow-sm dark:border-violet-950 dark:bg-zinc-950">
            <flux:heading size="lg">Novo lançamento</flux:heading>
            <flux:select wire:model.live="type" label="Tipo"><flux:select.option value="income">Receita</flux:select.option><flux:select.option value="expense">Despesa</flux:select.option></flux:select>
            <flux:input wire:model="description" label="Descrição" required />
            <flux:input wire:model="amount" label="Valor (R$)" inputmode="decimal" required />
            <flux:input wire:model="dueDate" label="Vencimento" type="date" required />
            <flux:select wire:model="categoryId" label="Categoria" placeholder="Sem categoria">@foreach($this->categories as $category)<flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>@endforeach</flux:select>
            <flux:button class="w-full" variant="primary" type="submit">Adicionar lançamento</flux:button>
        </form>
    </div>
</section>

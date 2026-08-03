<?php

use App\Models\Customer;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Clientes')] class extends Component {
    public string $search = '';
    public ?int $editingId = null;
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $birthDate = '';
    public string $notes = '';

    #[Computed]
    public function customers()
    {
        Gate::authorize('viewAny', Customer::class);

        return Customer::query()
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('normalized_phone', 'like', '%'.preg_replace('/\D+/', '', $this->search).'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')))
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'birthDate' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $customer = $this->editingId ? Customer::findOrFail($this->editingId) : new Customer;
        Gate::authorize($customer->exists ? 'update' : 'create', $customer->exists ? $customer : Customer::class);
        $customer->fill([
            'name' => trim($data['name']),
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'birth_date' => $data['birthDate'] ?: null,
            'notes' => $data['notes'] ?: null,
        ])->save();

        $this->resetForm();
        unset($this->customers);
        Flux::toast(variant: 'success', text: 'Cliente salvo com sucesso.');
    }

    public function edit(int $id): void
    {
        $customer = Customer::findOrFail($id);
        Gate::authorize('update', $customer);
        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone ?? '';
        $this->email = $customer->email ?? '';
        $this->birthDate = $customer->birth_date?->format('Y-m-d') ?? '';
        $this->notes = $customer->notes ?? '';
    }

    public function deactivate(int $id): void
    {
        $customer = Customer::findOrFail($id);
        Gate::authorize('delete', $customer);
        $customer->update(['is_active' => false]);
        unset($this->customers);
        Flux::toast(variant: 'success', text: 'Cliente inativado.');
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'name', 'phone', 'email', 'birthDate', 'notes');
        $this->resetValidation();
    }
}; ?>

<section class="mx-auto w-full max-w-7xl space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div><flux:heading size="xl">Clientes</flux:heading><flux:text>Histórico e contatos compartilhados pelo estabelecimento.</flux:text></div>
        <div class="w-full sm:w-80"><flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar nome, telefone ou e-mail" /></div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="overflow-hidden rounded-2xl border border-violet-100 bg-white shadow-sm dark:border-violet-950 dark:bg-zinc-950">
            <div class="divide-y divide-violet-50 dark:divide-violet-950">
                @forelse($this->customers as $customer)
                    <div class="flex items-center justify-between gap-4 p-4" wire:key="customer-{{ $customer->id }}">
                        <div class="min-w-0"><p class="truncate font-semibold text-zinc-900 dark:text-zinc-50">{{ $customer->name }}</p><p class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $customer->phone ?: $customer->email ?: 'Sem contato informado' }}</p></div>
                        <div class="flex items-center gap-2">
                            @unless($customer->is_active)<flux:badge color="zinc">Inativo</flux:badge>@endunless
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="edit({{ $customer->id }})" aria-label="Editar cliente" />
                            @if($customer->is_active)<flux:button size="sm" variant="ghost" icon="archive-box" wire:click="deactivate({{ $customer->id }})" wire:confirm="Inativar este cliente?" aria-label="Inativar cliente" />@endif
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-zinc-500 dark:text-zinc-400">Nenhum cliente encontrado. Cadastre o primeiro ao lado.</div>
                @endforelse
            </div>
        </div>

        <form wire:submit="save" class="h-fit space-y-4 rounded-2xl border border-violet-100 bg-white p-5 shadow-sm dark:border-violet-950 dark:bg-zinc-950">
            <flux:heading size="lg">{{ $editingId ? 'Editar cliente' : 'Novo cliente' }}</flux:heading>
            <flux:input wire:model="name" label="Nome" required />
            <flux:input wire:model="phone" label="Telefone" placeholder="(11) 99999-9999" />
            <flux:input wire:model="email" label="E-mail" type="email" />
            <flux:input wire:model="birthDate" label="Nascimento" type="date" />
            <flux:textarea wire:model="notes" label="Observações" rows="3" />
            <div class="flex justify-end gap-2">@if($editingId)<flux:button type="button" variant="ghost" wire:click="resetForm">Cancelar</flux:button>@endif<flux:button variant="primary" type="submit">Salvar cliente</flux:button></div>
        </form>
    </div>
</section>

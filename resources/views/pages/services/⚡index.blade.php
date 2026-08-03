<?php

use App\Models\Service;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Serviços')] class extends Component {
    public ?int $editingId = null;
    public string $name = '';
    public string $description = '';
    public int $durationMinutes = 60;
    public string $price = '';

    #[Computed]
    public function services()
    {
        Gate::authorize('viewAny', Service::class);
        return Service::query()->orderByDesc('is_active')->orderBy('name')->get();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'durationMinutes' => ['required', 'integer', 'min:5', 'max:720'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999'],
        ]);
        $service = $this->editingId ? Service::findOrFail($this->editingId) : new Service;
        Gate::authorize($service->exists ? 'update' : 'create', $service->exists ? $service : Service::class);
        $service->fill([
            'name' => trim($data['name']),
            'description' => $data['description'] ?: null,
            'duration_minutes' => $data['durationMinutes'],
            'price_cents' => (int) round(((float) str_replace(',', '.', $data['price'])) * 100),
        ])->save();
        $this->resetForm();
        unset($this->services);
        Flux::toast(variant: 'success', text: 'Serviço salvo com sucesso.');
    }

    public function edit(int $id): void
    {
        $service = Service::findOrFail($id); Gate::authorize('update', $service);
        $this->editingId = $service->id; $this->name = $service->name; $this->description = $service->description ?? '';
        $this->durationMinutes = $service->duration_minutes; $this->price = number_format($service->price_cents / 100, 2, '.', '');
    }

    public function toggle(int $id): void
    {
        $service = Service::findOrFail($id); Gate::authorize('update', $service);
        $service->update(['is_active' => ! $service->is_active]); unset($this->services);
    }

    public function resetForm(): void { $this->reset('editingId', 'name', 'description', 'price'); $this->durationMinutes = 60; $this->resetValidation(); }
}; ?>

<section class="mx-auto w-full max-w-7xl space-y-6">
    <div><flux:heading size="xl">Serviços</flux:heading><flux:text>Catálogo com preço e duração usados como snapshot nos agendamentos.</flux:text></div>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="grid content-start gap-3 sm:grid-cols-2">
            @forelse($this->services as $service)
                <article class="rounded-2xl border border-violet-100 bg-white p-5 shadow-sm dark:border-violet-950 dark:bg-zinc-950" wire:key="service-{{ $service->id }}">
                    <div class="flex items-start justify-between"><div><h3 class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $service->name }}</h3><p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $service->duration_minutes }} min</p></div><flux:badge :color="$service->is_active ? 'violet' : 'zinc'">{{ $service->is_active ? 'Ativo' : 'Inativo' }}</flux:badge></div>
                    <p class="mt-4 text-2xl font-bold text-violet-700 dark:text-violet-400">R$ {{ number_format($service->price_cents / 100, 2, ',', '.') }}</p>
                    <p class="mt-2 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $service->description ?: 'Sem descrição' }}</p>
                    @can('update', $service)<div class="mt-4 flex justify-end gap-2"><flux:button size="sm" variant="ghost" wire:click="toggle({{ $service->id }})">{{ $service->is_active ? 'Inativar' : 'Ativar' }}</flux:button><flux:button size="sm" icon="pencil" wire:click="edit({{ $service->id }})">Editar</flux:button></div>@endcan
                </article>
            @empty<div class="col-span-full rounded-2xl border border-dashed border-violet-200 p-10 text-center text-zinc-500 dark:border-violet-900 dark:text-zinc-400">Cadastre os serviços oferecidos pelo estabelecimento.</div>@endforelse
        </div>
        @can('create', Service::class)
            <form wire:submit="save" class="h-fit space-y-4 rounded-2xl border border-violet-100 bg-white p-5 shadow-sm dark:border-violet-950 dark:bg-zinc-950">
                <flux:heading size="lg">{{ $editingId ? 'Editar serviço' : 'Novo serviço' }}</flux:heading>
                <flux:input wire:model="name" label="Nome" required />
                <flux:textarea wire:model="description" label="Descrição" rows="3" />
                <div class="grid grid-cols-2 gap-3"><flux:input wire:model="durationMinutes" label="Duração (min)" type="number" min="5" /><flux:input wire:model="price" label="Preço (R$)" inputmode="decimal" /></div>
                <div class="flex justify-end gap-2">@if($editingId)<flux:button type="button" variant="ghost" wire:click="resetForm">Cancelar</flux:button>@endif<flux:button variant="primary" type="submit">Salvar serviço</flux:button></div>
            </form>
        @endcan
    </div>
</section>

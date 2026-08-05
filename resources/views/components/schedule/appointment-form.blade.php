@props(['showHeading' => true])

<form wire:submit="save" {{ $attributes->class('space-y-4') }}>
    @if($showHeading)
        <flux:heading size="lg">Novo agendamento</flux:heading>
    @endif

    <flux:select wire:model="customerId" label="Cliente" placeholder="Selecione" required>
        @foreach($this->customers as $customer)
            <flux:select.option value="{{ $customer->id }}">{{ $customer->name }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:select wire:model="professionalProfileId" label="Profissional" required>
        @foreach($this->professionals as $professional)
            <flux:select.option value="{{ $professional->id }}">{{ $professional->display_name }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:input wire:model="startsAt" type="datetime-local" label="Data e hora" required />

    <fieldset>
        <legend class="mb-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">Serviços</legend>
        <div class="max-h-48 space-y-2 overflow-auto rounded-xl border border-zinc-200 p-3 dark:border-violet-900">
            @forelse($this->services as $service)
                <label class="flex cursor-pointer items-center justify-between gap-3 text-sm">
                    <span>
                        <input class="mr-2 accent-violet-600" type="checkbox" wire:model="serviceIds" value="{{ $service->id }}">
                        {{ $service->name }}
                    </span>
                    <span class="text-zinc-500 dark:text-zinc-400">{{ $service->duration_minutes }} min · R$ {{ number_format($service->price_cents / 100, 2, ',', '.') }}</span>
                </label>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Cadastre serviços antes de agendar.</p>
            @endforelse
        </div>
        @error('serviceIds')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </fieldset>

    <flux:textarea wire:model="notes" label="Observações" rows="2" />

    <flux:button class="w-full" variant="primary" type="submit" icon="calendar-days">Criar agendamento</flux:button>
</form>

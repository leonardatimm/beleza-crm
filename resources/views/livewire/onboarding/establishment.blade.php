<div class="flex flex-col gap-6">
    <div class="text-center">
        <span class="inline-flex rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700 dark:bg-violet-950 dark:text-violet-300">Passo 2 de 2</span>
        <flux:heading size="xl" class="mt-4">Configure seu estabelecimento</flux:heading>
        <flux:subheading class="mt-2">Esses dados identificam sua operação e não ficam visíveis para outros estabelecimentos.</flux:subheading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-5">
        @if ($errors->any())
            <div role="alert" aria-live="assertive" class="form-alert form-alert-error">
                <span class="form-alert-icon" aria-hidden="true">
                    <flux:icon.exclamation-triangle class="size-5" />
                </span>
                <div>
                    <p class="font-semibold">Revise os dados informados</p>
                    <ul class="mt-1.5 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <fieldset>
            <legend class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">Tipo de negócio</legend>
            <div class="grid grid-cols-2 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-900" role="group" aria-label="Tipo de negócio">
                <button
                    type="button"
                    wire:click="selectBusinessType('individual')"
                    @class([
                        'rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                        'bg-white text-violet-700 shadow-sm dark:bg-violet-600 dark:text-white' => $business_type === 'individual',
                        'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => $business_type !== 'individual',
                    ])
                    aria-pressed="{{ $business_type === 'individual' ? 'true' : 'false' }}"
                >
                    Pessoa Física
                </button>
                <button
                    type="button"
                    wire:click="selectBusinessType('company')"
                    @class([
                        'rounded-lg px-3 py-2.5 text-sm font-semibold transition',
                        'bg-white text-violet-700 shadow-sm dark:bg-violet-600 dark:text-white' => $business_type === 'company',
                        'text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' => $business_type !== 'company',
                    ])
                    aria-pressed="{{ $business_type === 'company' ? 'true' : 'false' }}"
                >
                    Pessoa Jurídica
                </button>
            </div>
            @error('business_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        @if ($business_type === 'individual')
            <flux:input wire:model="cpf" label="CPF" inputmode="numeric" placeholder="000.000.000-00" x-on:input="window.BelezaMasks.apply($event, 'cpf')" required />
            <flux:input wire:model="professional_name" label="Nome do profissional" autocomplete="name" required />
        @else
            <flux:input wire:model="cnpj" label="CNPJ" inputmode="numeric" placeholder="00.000.000/0000-00" x-on:input="window.BelezaMasks.apply($event, 'cnpj')" required />
            <flux:input wire:model="legal_name" label="Razão social" required />
            <flux:input wire:model="trade_name" label="Nome fantasia" required />
            <flux:input wire:model="responsible_cpf" label="CPF do responsável" inputmode="numeric" placeholder="000.000.000-00" x-on:input="window.BelezaMasks.apply($event, 'cpf')" required />
        @endif

        <flux:input wire:model="phone" label="Telefone/WhatsApp" inputmode="tel" placeholder="(00) 00000-0000" x-on:input="window.BelezaMasks.apply($event, 'phone')" required />

        <div class="rounded-xl border border-violet-100 bg-violet-50/60 p-3 text-xs text-zinc-600 dark:border-violet-950 dark:bg-violet-950/20 dark:text-zinc-300">
            CPF e CNPJ ficam bloqueados após o cadastro. Alterações documentais exigem atendimento do suporte técnico.
        </div>

        <flux:button type="submit" variant="primary" class="w-full" data-test="onboarding-establishment-button" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">Criar estabelecimento e acessar o painel</span>
            <span wire:loading wire:target="save">Criando estabelecimento...</span>
        </flux:button>
    </form>
</div>

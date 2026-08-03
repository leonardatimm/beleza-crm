<div class="flex flex-col gap-6">
    <div class="text-center">
        <span class="inline-flex rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700 dark:bg-violet-950 dark:text-violet-300">Passo 1 de 2</span>
        <flux:heading size="xl" class="mt-4">Crie sua conta</flux:heading>
        <flux:subheading class="mt-2">Informe seus dados de acesso para começar.</flux:subheading>
    </div>

    <form wire:submit="register" class="flex flex-col gap-5">
        <flux:input wire:model="name" label="Nome completo" type="text" autocomplete="name" autofocus required />
        <flux:input wire:model="email" label="E-mail" type="email" autocomplete="email" placeholder="voce@exemplo.com" required />
        <flux:input wire:model="password" label="Senha" type="password" autocomplete="new-password" viewable required />
        <flux:input wire:model="password_confirmation" label="Confirmação de senha" type="password" autocomplete="new-password" viewable required />

        <flux:button type="submit" variant="primary" class="w-full" data-test="register-step-one-button">
            Continuar para o estabelecimento
        </flux:button>
    </form>

    <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        Já possui uma conta?
        <flux:link :href="route('login')" wire:navigate>Entrar</flux:link>
    </p>
</div>

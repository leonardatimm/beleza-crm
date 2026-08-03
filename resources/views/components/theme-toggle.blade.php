<div
    x-data
    {{ $attributes->class('mb-2 rounded-xl border border-violet-100 bg-violet-50/60 p-3 dark:border-violet-950 dark:bg-violet-950/20') }}
    data-test="theme-toggle"
>
    <div class="flex items-center justify-between gap-3">
        <div class="flex min-w-0 items-center gap-2">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-violet-700 shadow-sm dark:bg-zinc-900 dark:text-violet-400">
                <flux:icon.sun class="size-4 dark:hidden" />
                <flux:icon.moon class="hidden size-4 dark:block" />
            </span>

            <div class="min-w-0 leading-tight">
                <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-100">Tema</p>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400" x-text="$flux.dark ? 'Dark' : 'Light'">Light</p>
            </div>
        </div>

        <button
            type="button"
            role="switch"
            :aria-checked="$flux.dark.toString()"
            :aria-label="$flux.dark ? 'Ativar tema claro' : 'Ativar tema escuro'"
            @click="$flux.appearance = $flux.dark ? 'light' : 'dark'"
            class="relative h-7 w-12 shrink-0 cursor-pointer rounded-full bg-zinc-300 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600 dark:bg-violet-600"
            data-test="theme-toggle-button"
        >
            <span class="absolute start-1 top-1 size-5 rounded-full bg-white shadow-md transition-transform dark:translate-x-5" aria-hidden="true"></span>
        </button>
    </div>
</div>

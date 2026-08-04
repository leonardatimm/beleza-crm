<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-violet-50/30 text-zinc-900 dark:bg-black dark:text-zinc-100">
        @php
            $routeTeam = request()->route('current_team');
            $currentTeamSlug = $routeTeam instanceof \App\Models\Team
                ? $routeTeam->slug
                : ($routeTeam ?: auth()->user()->currentTeam?->slug);
            $dashboardHref = $currentTeamSlug
                ? route('dashboard', ['current_team' => $currentTeamSlug])
                : route('onboarding.establishment');
            $clientsHref = $currentTeamSlug ? route('clients.index', ['current_team' => $currentTeamSlug]) : $dashboardHref;
            $servicesHref = $currentTeamSlug ? route('services.index', ['current_team' => $currentTeamSlug]) : $dashboardHref;
            $scheduleHref = $currentTeamSlug ? route('schedule.index', ['current_team' => $currentTeamSlug]) : $dashboardHref;
            $financeHref = $currentTeamSlug ? route('finance.index', ['current_team' => $currentTeamSlug]) : $dashboardHref;
        @endphp

        <flux:sidebar sticky collapsible="mobile" class="border-e border-violet-100 bg-white dark:border-violet-950 dark:bg-zinc-950">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ $dashboardHref }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <livewire:team-switcher />

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Operação" class="grid">
                    <flux:sidebar.item icon="home" :href="$dashboardHref" :current="request()->routeIs('dashboard')" wire:navigate>
                        Visão geral
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="$clientsHref" :current="request()->routeIs('clients.*')" wire:navigate>Clientes</flux:sidebar.item>
                    <flux:sidebar.item icon="sparkles" :href="$servicesHref" :current="request()->routeIs('services.*')" wire:navigate>Serviços</flux:sidebar.item>
                    <flux:sidebar.item icon="calendar-days" :href="$scheduleHref" :current="request()->routeIs('schedule.*')" wire:navigate>Agenda</flux:sidebar.item>
                    @can('viewAny', \App\Models\FinancialEntry::class)
                        <flux:sidebar.item icon="banknotes" :href="$financeHref" :current="request()->routeIs('finance.*')" wire:navigate>Financeiro</flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-theme-toggle class="hidden lg:block" />
            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            Configurações
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            Sair
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <livewire:create-team-modal />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>

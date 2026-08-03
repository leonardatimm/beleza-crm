<?php

use App\Http\Middleware\EnsureTeamMembership;
use App\Livewire\Auth\RegisterStepOne;
use App\Livewire\Onboarding\Establishment;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('register', RegisterStepOne::class)
    ->middleware('guest')
    ->name('register');

Route::get('onboarding/estabelecimento', Establishment::class)
    ->middleware('auth')
    ->name('onboarding.establishment');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::livewire('dashboard', 'pages::dashboard.index')->name('dashboard');
        Route::livewire('clientes', 'pages::clients.index')->name('clients.index');
        Route::livewire('servicos', 'pages::services.index')->name('services.index');
        Route::livewire('agenda', 'pages::schedule.index')->name('schedule.index');
        Route::livewire('financeiro', 'pages::finance.index')->name('finance.index');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');
});

require __DIR__.'/settings.php';

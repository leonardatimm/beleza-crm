<?php

namespace App\Providers;

use App\Domain\Finance\Contracts\PaymentGateway;
use App\Domain\Finance\Gateways\NullPaymentGateway;
use App\Domain\Scheduling\Events\AppointmentCompleted;
use App\Domain\Scheduling\Listeners\CreateReceivableForCompletedAppointment;
use App\Domain\Tenancy\TenantContext;
use App\Models\Membership;
use App\Observers\MembershipObserver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->bind(PaymentGateway::class, NullPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Membership::observe(MembershipObserver::class);
        Event::listen(AppointmentCompleted::class, CreateReceivableForCompletedAppointment::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

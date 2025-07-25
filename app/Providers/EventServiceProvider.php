<?php

namespace App\Providers;

use App\Listeners\AuthenticationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Events are registered via the AuthenticationListener subscriber
        \App\Events\IncomeCreated::class => [
            \App\Listeners\SendTelegramNotification::class,
        ],
        \App\Events\ExpenseCreated::class => [
            \App\Listeners\SendTelegramNotification::class,
        ],
        \App\Events\PatientCreated::class => [
            \App\Listeners\SendTelegramNotification::class,
        ],
        \App\Events\UserCreated::class => [
            \App\Listeners\SendTelegramNotification::class,
        ],
        \App\Events\WorkLocationUpdated::class => [
            \App\Listeners\ClearWorkLocationCache::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        AuthenticationListener::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
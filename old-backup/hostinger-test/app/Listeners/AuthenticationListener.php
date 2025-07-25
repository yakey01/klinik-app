<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AuthenticationListener
{
    /**
     * Handle user login events.
     */
    public function handleLogin(Login $event)
    {
        AuditLog::log('login', $event->user);
    }

    /**
     * Handle user logout events.
     */
    public function handleLogout(Logout $event)
    {
        AuditLog::log('logout', $event->user);
    }

    /**
     * Handle failed login attempts.
     */
    public function handleFailed(Failed $event)
    {
        AuditLog::create([
            'user_id' => null,
            'action' => 'login_failed',
            'model_type' => null,
            'model_id' => null,
            'old_values' => [],
            'new_values' => ['email' => $event->credentials['email'] ?? null],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events)
    {
        $events->listen(
            Login::class,
            [AuthenticationListener::class, 'handleLogin']
        );

        $events->listen(
            Logout::class,
            [AuthenticationListener::class, 'handleLogout']
        );

        $events->listen(
            Failed::class,
            [AuthenticationListener::class, 'handleFailed']
        );
    }
}
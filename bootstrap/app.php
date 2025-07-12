<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'petugas' => \App\Http\Middleware\PetugasMiddleware::class,
            'paramedis' => \App\Http\Middleware\ParamedisMiddleware::class,
            'dokter' => \App\Http\Middleware\EnsureDokterRole::class,
            'device.binding' => \App\Http\Middleware\DeviceBindingMiddleware::class,
            'anti.gps.spoofing' => \App\Http\Middleware\AntiGpsSpoofingMiddleware::class,
        ]);
        
        // Add security headers to all responses
        // Temporarily disabled for debugging
        // $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
        
        // Add rate limiting to authentication routes
        $middleware->group('auth', [
            'throttle:60,1',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

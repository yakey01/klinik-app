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
            'enhanced.role' => \App\Http\Middleware\EnhancedRoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'petugas' => \App\Http\Middleware\PetugasMiddleware::class,
            'paramedis' => \App\Http\Middleware\ParamedisMiddleware::class,
            'device.binding' => \App\Http\Middleware\DeviceBindingMiddleware::class,
            'anti.gps.spoofing' => \App\Http\Middleware\AntiGpsSpoofingMiddleware::class,
            // API v2 middleware
            'api.rate.limit' => \App\Http\Middleware\Api\ApiRateLimitMiddleware::class,
            'api.response.headers' => \App\Http\Middleware\Api\ApiResponseHeadersMiddleware::class,
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

<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Temporary: Allow login route to bypass CSRF for debugging 419 error
        // Remove this after fixing CSRF token issue
        'login',
        'api/*', // Allow API routes to bypass CSRF
    ];
}
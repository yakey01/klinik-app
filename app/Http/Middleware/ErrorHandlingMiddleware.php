<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ErrorHandlingService;
use Exception;

class ErrorHandlingMiddleware
{
    protected ErrorHandlingService $errorHandler;

    public function __construct(ErrorHandlingService $errorHandler)
    {
        $this->errorHandler = $errorHandler;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (Exception $e) {
            $dokterkunException = $this->errorHandler->handleException($e);
            
            // For API requests, always return JSON response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => true,
                    'message' => $dokterkunException->getUserMessage(),
                    'error_code' => $dokterkunException->getErrorCode(),
                    'timestamp' => now()->toISOString(),
                    'path' => $request->path(),
                    'method' => $request->method(),
                ], $dokterkunException->getCode() ?: 500);
            }

            // For web requests, use default handling
            return $dokterkunException->render($request);
        }
    }
}
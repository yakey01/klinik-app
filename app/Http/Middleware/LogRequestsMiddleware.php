<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LoggingService;
use Illuminate\Support\Facades\Log;

class LogRequestsMiddleware
{
    protected LoggingService $loggingService;

    public function __construct(LoggingService $loggingService)
    {
        $this->loggingService = $loggingService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log request start
        $this->logRequestStart($request);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Log request completion
        $this->logRequestComplete($request, $response, $duration);
        
        return $response;
    }

    protected function logRequestStart(Request $request): void
    {
        // Only log API requests and important web requests
        if ($this->shouldLogRequest($request)) {
            Log::info('Request started', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
                'session_id' => $request->session()->getId(),
                'request_id' => $request->header('X-Request-ID') ?? uniqid(),
            ]);
        }
    }

    protected function logRequestComplete(Request $request, Response $response, float $duration): void
    {
        if ($this->shouldLogRequest($request)) {
            $statusCode = $response->getStatusCode();
            
            // Log API requests
            if ($request->is('api/*')) {
                $this->loggingService->logApiRequest(
                    $request->method(),
                    $request->path(),
                    $this->getRequestData($request),
                    $this->getResponseData($response),
                    $statusCode,
                    $duration
                );
            }
            
            // Log performance for slow requests
            if ($duration > 2.0) {
                $this->loggingService->logPerformance(
                    'http_request',
                    $duration,
                    [
                        'method' => $request->method(),
                        'url' => $request->fullUrl(),
                        'status_code' => $statusCode,
                        'memory_usage' => memory_get_usage(true),
                        'memory_peak' => memory_get_peak_usage(true),
                    ],
                    'warning'
                );
            }
            
            // Log security events
            $this->logSecurityEvents($request, $response);
        }
    }

    protected function shouldLogRequest(Request $request): bool
    {
        // Skip logging for certain routes
        $skipRoutes = [
            'horizon/*',
            'telescope/*',
            '_debugbar/*',
            'livewire/*',
            'filament/assets/*',
            'css/*',
            'js/*',
            'images/*',
            'favicon.ico',
        ];
        
        foreach ($skipRoutes as $route) {
            if ($request->is($route)) {
                return false;
            }
        }
        
        return true;
    }

    protected function getRequestData(Request $request): array
    {
        $data = [
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query' => $request->query(),
            'input' => $request->input(),
        ];
        
        // Remove sensitive data
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
        foreach ($sensitiveKeys as $key) {
            if (isset($data['input'][$key])) {
                $data['input'][$key] = '[HIDDEN]';
            }
        }
        
        return $data;
    }

    protected function getResponseData(Response $response): array
    {
        $data = [
            'status_code' => $response->getStatusCode(),
            'headers' => $this->sanitizeHeaders($response->headers->all()),
        ];
        
        // Add response content for JSON responses (if not too large)
        if ($response->headers->get('content-type') === 'application/json') {
            $content = $response->getContent();
            if (strlen($content) < 10000) { // Max 10KB
                $data['content'] = json_decode($content, true);
            } else {
                $data['content'] = '[CONTENT TOO LARGE]';
            }
        }
        
        return $data;
    }

    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie', 'set-cookie'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = '[HIDDEN]';
            }
        }
        
        return $headers;
    }

    protected function logSecurityEvents(Request $request, Response $response): void
    {
        $statusCode = $response->getStatusCode();
        
        // Log failed authentication attempts
        if ($statusCode === 401) {
            $this->loggingService->logSecurity(
                'authentication_failed',
                null,
                'Failed authentication attempt',
                [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );
        }
        
        // Log authorization failures
        if ($statusCode === 403) {
            $this->loggingService->logSecurity(
                'authorization_failed',
                auth()->user(),
                'Authorization failed',
                [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_role' => auth()->user()?->roles?->first()?->name,
                ]
            );
        }
        
        // Log suspicious activity
        if ($this->isSuspiciousRequest($request)) {
            $this->loggingService->logSecurity(
                'suspicious_activity',
                auth()->user(),
                'Potentially suspicious request detected',
                [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'suspicion_reason' => $this->getSuspicionReason($request),
                ]
            );
        }
    }

    protected function isSuspiciousRequest(Request $request): bool
    {
        // Check for SQL injection patterns
        $sqlPatterns = [
            'union select',
            'drop table',
            'delete from',
            'update.*set',
            'insert into',
            'exec(',
            'xp_cmdshell',
        ];
        
        $input = strtolower(json_encode($request->all()));
        foreach ($sqlPatterns as $pattern) {
            if (str_contains($input, $pattern)) {
                return true;
            }
        }
        
        // Check for XSS patterns
        $xssPatterns = [
            '<script',
            'javascript:',
            'onerror=',
            'onload=',
            'eval(',
            'alert(',
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (str_contains($input, $pattern)) {
                return true;
            }
        }
        
        // Check for path traversal
        if (str_contains($input, '../') || str_contains($input, '..\\')) {
            return true;
        }
        
        return false;
    }

    protected function getSuspicionReason(Request $request): string
    {
        $input = strtolower(json_encode($request->all()));
        
        if (str_contains($input, 'union select') || str_contains($input, 'drop table')) {
            return 'SQL injection attempt';
        }
        
        if (str_contains($input, '<script') || str_contains($input, 'javascript:')) {
            return 'XSS attempt';
        }
        
        if (str_contains($input, '../') || str_contains($input, '..\\')) {
            return 'Path traversal attempt';
        }
        
        return 'Unknown suspicious pattern';
    }
}
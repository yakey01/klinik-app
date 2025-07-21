<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixCookieMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Fix cookie settings for better browser compatibility
        if ($response instanceof \\Illuminate\\Http\\Response) {
            $headers = $response->headers;
            
            // Get all Set-Cookie headers
            $cookies = $headers->getCookies();
            
            foreach ($cookies as $cookie) {
                if ($cookie->getName() === 'laravel_session') {
                    // Remove the problematic cookie and set a new one
                    $headers->removeCookie($cookie->getName());
                    
                    // Set cookie with better compatibility
                    $headers->setCookie(
                        $cookie->getName(),
                        $cookie->getValue(),
                        $cookie->getExpiresTime(),
                        '/', // path
                        null, // domain (let browser decide)
                        false, // secure
                        true,  // httpOnly
                        false, // raw
                        'Lax'  // sameSite
                    );
                }
            }
        }
        
        return $response;
    }
}

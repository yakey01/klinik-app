<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ValidateEmailMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check if this is a user update request
        if ($request->isMethod('put') || $request->isMethod('patch')) {
            $routeName = $request->route()->getName();
            
            if (str_contains($routeName, 'user') || str_contains($routeName, 'pegawai')) {
                // Validate email if present
                if ($request->has('email')) {
                    $validator = Validator::make($request->all(), [
                        'email' => 'required|email|max:255'
                    ]);
                    
                    if ($validator->fails()) {
                        return response()->json([
                            'error' => 'Invalid email format',
                            'details' => $validator->errors()
                        ], 422);
                    }
                }
            }
        }
        
        return $next($request);
    }
}

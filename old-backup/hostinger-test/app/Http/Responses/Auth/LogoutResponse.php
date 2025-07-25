<?php

namespace App\Http\Responses\Auth;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // Log the logout event for audit purposes
        $user = $request->user();
        if ($user) {
            Log::info('User logged out from Filament panel', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : 'no_role',
                'panel' => $request->route()?->parameter('panel') ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        // Ensure complete logout by clearing all guards
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect to unified login page
        return redirect()->to('/login');
    }
}
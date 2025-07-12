<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class UnifiedAuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.unified-login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        Log::info('Login attempt started', [
            'email' => $request->input('email'),
            'has_token' => $request->has('_token'),
            'session_id' => $request->session()->getId(),
            'csrf_token' => $request->input('_token'),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $request->validate([
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed during login', [
                'errors' => $e->errors(),
                'email' => $request->input('email')
            ]);
            throw $e;
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();
            
            Log::info('User logged in', [
                'user_id' => $user->id,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->toArray()
            ]);
            
            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                Log::warning('Inactive user attempted login', ['email' => $user->email]);
                throw ValidationException::withMessages([
                    'email' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
                ]);
            }
            
            // Redirect based on user role
            if ($user->hasRole('admin')) {
                Log::info('Redirecting admin user to /admin');
                return redirect()->intended('/admin');
            } elseif ($user->hasRole('petugas')) {
                Log::info('Redirecting petugas user to /petugas');
                return redirect()->intended('/petugas');
            } elseif ($user->hasRole('manajer')) {
                Log::info('Redirecting manajer user to /manager/dashboard');
                return redirect()->intended('/manager/dashboard');
            } elseif ($user->hasRole('bendahara')) {
                Log::info('Redirecting bendahara user to /treasurer/dashboard');
                return redirect()->intended('/treasurer/dashboard');
            } elseif ($user->hasRole('dokter')) {
                Log::info('Redirecting dokter user to /doctor/dashboard');
                return redirect()->intended('/doctor/dashboard');
            } elseif ($user->hasRole('paramedis')) {
                Log::info('Redirecting paramedis user to /paramedis');
                return redirect()->intended('/paramedis');
            } elseif ($user->hasRole('non_paramedis')) {
                Log::info('Redirecting non_paramedis user to /non-paramedic/dashboard');
                return redirect()->intended('/non-paramedic/dashboard');
            }

            // Default fallback
            Log::info('Redirecting user to default /dashboard');
            return redirect()->intended('/dashboard');
        }

        Log::warning('Failed login attempt', ['email' => $request->input('email')]);
        throw ValidationException::withMessages([
            'email' => 'Email atau password salah.',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
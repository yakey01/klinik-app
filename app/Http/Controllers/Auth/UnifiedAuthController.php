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
        // Rate limiting check
        $key = 'login_attempts:' . $request->ip();
        $maxAttempts = 5;
        $decayTime = 60; // 1 minute

        Log::info('Login attempt started', [
            'email_or_username' => $request->input('email_or_username'),
            'ip' => $request->ip(),
            'has_token' => $request->has('_token'),
            'session_id' => $request->session()->getId(),
            'csrf_token' => $request->input('_token'),
            'user_agent' => $request->userAgent()
        ]);

        try {
            $request->validate([
                'email_or_username' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:6'],
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed during login', [
                'errors' => $e->errors(),
                'email_or_username' => $request->input('email_or_username')
            ]);
            throw $e;
        }

        $identifier = $request->input('email_or_username');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Find user by email or username
        $user = \App\Models\User::findForAuth($identifier);
        
        $originalPassword = $request->input('password');
        Log::info('Debug: User lookup', [
            'identifier' => $identifier,
            'user_found' => $user ? 'YES' : 'NO',
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'user_active' => $user ? $user->is_active : null,
            'password_provided' => !empty($password),
            'original_password_length' => strlen($originalPassword),
            'trimmed_password_length' => strlen($password),
            'password_was_trimmed' => $originalPassword !== $password,
            'password_raw' => $password,
            'password_hex' => bin2hex($password),
            'original_password_hex' => bin2hex($originalPassword),
            'password_ord_last' => ord(substr($password, -1)),
        ]);
        
        // Debug: test password manually before Auth::attempt
        if ($user) {
            $manualPasswordCheck = \Illuminate\Support\Facades\Hash::check($password, $user->password);
            $trimmedPasswordCheck = \Illuminate\Support\Facades\Hash::check(trim($password), $user->password);
            Log::info('Debug: Manual password check', [
                'user_id' => $user->id,
                'manual_check_result' => $manualPasswordCheck,
                'trimmed_check_result' => $trimmedPasswordCheck,
                'password_hash_exists' => !empty($user->password),
                'password_hash_preview' => substr($user->password, 0, 20) . '...',
                'password_needs_trim' => $password !== trim($password),
            ]);
        }

        if ($user && Auth::attempt(['email' => $user->email, 'password' => $password], $remember)) {
            // Clear failed attempts on successful login
            \Illuminate\Support\Facades\RateLimiter::clear($key);
            
            $request->session()->regenerate();

            $user = Auth::user();
            
            Log::info('User logged in', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : 'no_role'
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
                Log::info('Redirecting dokter user to /dokter');
                return redirect()->intended('/dokter');
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

        // Record failed attempt
        \Illuminate\Support\Facades\RateLimiter::hit($key, $decayTime);
        
        // Additional debug info for failed login
        if ($user) {
            Log::warning('Failed login attempt - User found but auth failed', [
                'email_or_username' => $request->input('email_or_username'),
                'user_id' => $user->id,
                'user_email' => $user->email,
                'is_active' => $user->is_active,
                'has_password' => !empty($user->password),
                'password_check' => \Illuminate\Support\Facades\Hash::check($password, $user->password),
                'ip' => $request->ip(),
                'attempts' => \Illuminate\Support\Facades\RateLimiter::attempts($key)
            ]);
        } else {
            Log::warning('Failed login attempt - User not found', [
                'email_or_username' => $request->input('email_or_username'),
                'ip' => $request->ip(),
                'attempts' => \Illuminate\Support\Facades\RateLimiter::attempts($key)
            ]);
        }
        
        // Add delay for failed attempts to prevent brute force
        sleep(rand(1, 3));
        
        throw ValidationException::withMessages([
            'email_or_username' => 'Email/username atau password salah.',
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
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

Route::get('/test-manajer-auth', function () {
    $user = User::where('email', 'manajer@dokterku.com')->first();
    
    if (!$user) {
        return response()->json([
            'error' => 'User manajer@dokterku.com not found',
            'suggestion' => 'Run: php artisan db:seed --class=SimpleManajerSeeder'
        ]);
    }
    
    $passwordCheck = Hash::check('password', $user->password);
    $hasRole = $user->hasRole('manajer');
    
    return response()->json([
        'user_exists' => true,
        'email' => $user->email,
        'name' => $user->name,
        'is_active' => $user->is_active,
        'password_valid' => $passwordCheck,
        'has_manajer_role' => $hasRole,
        'roles' => $user->roles->pluck('name'),
        'login_url' => url('/login'),
        'dashboard_url' => url('/manajer'),
        'enhanced_dashboard_url' => url('/manajer/enhanced-manajer-dashboard'),
    ]);
});

Route::get('/test-manajer-login', function () {
    $user = User::where('email', 'manajer@dokterku.com')->first();
    
    if ($user && Hash::check('password', $user->password)) {
        Auth::login($user);
        return redirect('/manajer')->with('success', 'Login successful! Welcome to Executive Dashboard');
    }
    
    return redirect('/login')->with('error', 'Login failed');
})->name('test.manajer.login');

Route::get('/test-session', function () {
    return response()->json([
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
        'session_driver' => config('session.driver'),
        'session_lifetime' => config('session.lifetime'),
        'app_key' => config('app.key') ? 'SET' : 'NOT SET',
        'app_env' => config('app.env'),
        'session_working' => session()->isStarted(),
    ]);
});

Route::get('/fix-session', function () {
    // Clear all sessions and start fresh
    session()->flush();
    session()->regenerate(true);
    
    return response()->json([
        'message' => 'Session cleared and regenerated',
        'new_session_id' => session()->getId(),
        'new_csrf_token' => csrf_token(),
    ]);
});
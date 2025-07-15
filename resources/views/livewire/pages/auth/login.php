<?php

use function Livewire\Volt\{state, form, computed};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

state(['email_or_username' => '', 'password' => '', 'remember' => false]);

form([
    'email_or_username' => 'required|string|max:255',
    'password' => 'required|string|min:6',
    'remember' => 'boolean',
]);

$login = function () {
    $this->validate();
    
    // Use the existing unified auth logic
    $identifier = $this->email_or_username;
    $password = $this->password;
    $remember = $this->remember;

    // Find user by email or username from User table first
    $user = \App\Models\User::findForAuth($identifier);
    
    // If not found in User table, try to find in Pegawai table
    if (!$user) {
        $pegawai = \App\Models\Pegawai::where('username', $identifier)
            ->whereNotNull('username')
            ->whereNotNull('password')
            ->where('status_akun', 'Aktif')
            ->first();
            
        if ($pegawai) {
            // Check password for pegawai
            if (\Illuminate\Support\Facades\Hash::check($password, $pegawai->password)) {
                // Create or get associated User for the pegawai
                if ($pegawai->user_id && $pegawai->user) {
                    $user = $pegawai->user;
                } else {
                    // Create temporary User object for pegawai login
                    $roleName = match($pegawai->jenis_pegawai) {
                        'Paramedis' => 'paramedis',
                        'Non-Paramedis' => 'non_paramedis',
                        default => 'petugas'
                    };
                    $role = \App\Models\Role::where('name', $roleName)->first();
                    
                    if ($role) {
                        // Create a real User record in database for pegawai
                        $userEmail = $pegawai->nik . '@pegawai.local';
                        
                        // Check if user already exists
                        $existingUser = \App\Models\User::where('email', $userEmail)->first();
                        
                        if (!$existingUser) {
                            // Create real user record in database
                            $user = \App\Models\User::create([
                                'name' => $pegawai->nama_lengkap,
                                'username' => $pegawai->username,
                                'email' => $userEmail,
                                'role_id' => $role->id,
                                'is_active' => $pegawai->aktif,
                                'password' => $pegawai->password,
                            ]);
                            
                            // Update pegawai with user_id
                            $pegawai->update(['user_id' => $user->id]);
                        } else {
                            $user = $existingUser;
                            // Update existing user data
                            $user->update([
                                'name' => $pegawai->nama_lengkap,
                                'username' => $pegawai->username,
                                'role_id' => $role->id,
                                'is_active' => $pegawai->aktif,
                            ]);
                        }
                    }
                }
            }
        }
    }

    $loginSuccessful = false;
    
    if ($user) {
        // Check if this is a pegawai user (password already verified above)
        if (isset($pegawai)) {
            // Manual login for pegawai - password already verified
            Auth::login($user, $remember);
            $loginSuccessful = true;
        } else {
            // Regular Auth::attempt for normal users
            $loginSuccessful = Auth::attempt(['email' => $user->email, 'password' => $password], $remember);
        }
    }
    
    if ($loginSuccessful) {
        request()->session()->regenerate();

        $user = Auth::user();
        
        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email_or_username' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
            ]);
        }
        
        // Redirect based on user role
        if ($user->hasRole('admin')) {
            return redirect('/admin');
        } elseif ($user->hasRole('petugas')) {
            // Check if this petugas user should be redirected to non-paramedis interface
            if ($user->username && str_contains($user->email, '@pegawai.local')) {
                return redirect()->route('nonparamedis.dashboard');
            }
            return redirect('/petugas');
        } elseif ($user->hasRole('manajer')) {
            return redirect('/manager/dashboard');
        } elseif ($user->hasRole('bendahara')) {
            return redirect('/bendahara');
        } elseif ($user->hasRole('dokter')) {
            return redirect('/dokter');
        } elseif ($user->hasRole('paramedis')) {
            return redirect('/paramedis');
        } elseif ($user->hasRole('non_paramedis')) {
            return redirect()->route('nonparamedis.dashboard');
        }

        // Default fallback
        return redirect()->intended('/dashboard');
    }

    throw ValidationException::withMessages([
        'email_or_username' => 'Email/username atau password salah.',
    ]);
};

?>

<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-4 py-8">
    <div class="w-full max-w-md">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4 shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">DOKTERKU</h1>
            <p class="text-slate-300 text-sm">Sistem Manajemen Klinik</p>
        </div>

        <!-- Login Form -->
        <div class="bg-slate-800/80 backdrop-blur-sm rounded-2xl p-8 shadow-2xl border border-slate-700">
            @if (session('status'))
                <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 text-green-400 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form wire:submit="login" class="space-y-6">
                <div>
                    <label for="email_or_username" class="block text-sm font-medium text-slate-200 mb-2">
                        Email atau Username
                    </label>
                    <input 
                        wire:model="email_or_username"
                        id="email_or_username" 
                        type="text" 
                        required 
                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        autofocus 
                        placeholder="Masukkan email atau username"
                        autocomplete="username"
                    >
                    @error('email_or_username') 
                        <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> 
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-200 mb-2">
                        Password
                    </label>
                    <input 
                        wire:model="password"
                        id="password" 
                        type="password" 
                        required 
                        class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-lg text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                    >
                    @error('password') 
                        <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> 
                    @enderror
                </div>

                <div class="flex items-center">
                    <input 
                        wire:model="remember"
                        type="checkbox" 
                        id="remember"
                        class="w-4 h-4 text-blue-600 bg-slate-700 border-slate-600 rounded focus:ring-blue-500 focus:ring-2"
                    >
                    <label for="remember" class="ml-2 text-sm text-slate-300">
                        Ingat saya
                    </label>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-800 transition-all transform hover:-translate-y-1 hover:shadow-lg"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove>Masuk ke Sistem</span>
                    <span wire:loading>Loading...</span>
                </button>
            </form>
        </div>

        <!-- Clinic Motto -->
        <div class="text-center mt-8">
            <div class="inline-flex items-center space-x-2 px-6 py-3 bg-blue-600/20 rounded-full border border-blue-500/30">
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <span class="text-blue-400 font-medium text-sm tracking-wide">
                    SAHABAT MENUJU SEHAT
                </span>
                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-slate-400 text-xs">
                © {{ date('Y') }} Dokterku. Sistem manajemen klinik terpercaya.
            </p>
        </div>
    </div>
</div>
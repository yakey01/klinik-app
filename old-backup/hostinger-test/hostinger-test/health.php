<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use App\Models\Pegawai;

Route::get('/health', function () {
    try {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'checks' => []
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = 'connected';
        } catch (Exception $e) {
            $health['checks']['database'] = 'failed: ' . $e->getMessage();
            $health['status'] = 'unhealthy';
        }

        // Roles check
        try {
            $roles = Role::all(['id', 'name']);
            $health['checks']['roles'] = [
                'count' => $roles->count(),
                'available' => $roles->pluck('name')->toArray()
            ];
        } catch (Exception $e) {
            $health['checks']['roles'] = 'failed: ' . $e->getMessage();
            $health['status'] = 'unhealthy';
        }

        // Paramedis users check
        try {
            $paramedisRole = Role::where('name', 'paramedis')->first();
            $paramedisUsers = User::where('role_id', $paramedisRole?->id)->count();
            $health['checks']['paramedis_users'] = $paramedisUsers;
        } catch (Exception $e) {
            $health['checks']['paramedis_users'] = 'failed: ' . $e->getMessage();
        }

        // Storage check
        $health['checks']['storage_writable'] = is_writable(storage_path());
        
        // Cache check
        try {
            cache()->put('health_test', 'ok', 60);
            $health['checks']['cache'] = cache()->get('health_test') === 'ok' ? 'working' : 'failed';
        } catch (Exception $e) {
            $health['checks']['cache'] = 'failed: ' . $e->getMessage();
        }

        return response()->json($health);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'critical',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});

Route::get('/health/paramedis', function () {
    try {
        $data = [
            'status' => 'checking',
            'timestamp' => now()->toISOString(),
            'tests' => []
        ];

        // Test paramedis role exists
        $paramedisRole = Role::where('name', 'paramedis')->first();
        $data['tests']['paramedis_role_exists'] = $paramedisRole ? 'yes' : 'no';

        // Test paramedis users
        if ($paramedisRole) {
            $paramedisUsers = User::where('role_id', $paramedisRole->id)->get();
            $data['tests']['paramedis_users'] = [
                'count' => $paramedisUsers->count(),
                'users' => $paramedisUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_active' => $user->is_active,
                        'role_name' => $user->role?->name
                    ];
                })
            ];
        }

        // Test pegawai with paramedis type
        $paramedisPegawai = Pegawai::where('jenis_pegawai', 'Paramedis')->get();
        $data['tests']['paramedis_pegawai'] = [
            'count' => $paramedisPegawai->count(),
            'samples' => $paramedisPegawai->take(3)->map(function($pegawai) {
                return [
                    'id' => $pegawai->id,
                    'nama' => $pegawai->nama_lengkap,
                    'username' => $pegawai->username,
                    'user_id' => $pegawai->user_id,
                    'status_akun' => $pegawai->status_akun
                ];
            })
        ];

        // Test middleware
        try {
            $middleware = app(\App\Http\Middleware\RoleMiddleware::class);
            $data['tests']['role_middleware'] = 'loaded';
        } catch (Exception $e) {
            $data['tests']['role_middleware'] = 'error: ' . $e->getMessage();
        }

        return response()->json($data);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/health/auth-test', function () {
    try {
        // Test authentication flow simulation
        $data = [
            'status' => 'testing',
            'timestamp' => now()->toISOString(),
            'auth_tests' => []
        ];

        // Test if we can find paramedis users by username
        $testUsernames = ['paramedis1', 'paramedis', 'nurse'];
        foreach ($testUsernames as $username) {
            $user = User::where('username', $username)->first();
            if ($user) {
                $data['auth_tests']['found_user_' . $username] = [
                    'exists' => true,
                    'role' => $user->role?->name,
                    'has_paramedis_role' => $user->hasRole('paramedis')
                ];
            }
        }

        // Test pegawai auth lookup
        $paramedisPegawai = Pegawai::where('jenis_pegawai', 'Paramedis')
            ->whereNotNull('username')
            ->whereNotNull('password')
            ->where('status_akun', 'Aktif')
            ->first();

        if ($paramedisPegawai) {
            $data['auth_tests']['sample_paramedis_pegawai'] = [
                'id' => $paramedisPegawai->id,
                'username' => $paramedisPegawai->username,
                'has_user_id' => !is_null($paramedisPegawai->user_id),
                'linked_user' => $paramedisPegawai->user ? [
                    'id' => $paramedisPegawai->user->id,
                    'role' => $paramedisPegawai->user->role?->name
                ] : null
            ];
        }

        return response()->json($data);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});
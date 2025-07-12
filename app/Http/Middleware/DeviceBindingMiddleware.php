<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Log;

class DeviceBindingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip untuk guest users
        if (!$request->user()) {
            return $next($request);
        }

        $user = $request->user();
        $deviceInfo = UserDevice::extractDeviceInfo($request);
        
        // Pastikan device_id ada
        if (empty($deviceInfo['device_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Device ID diperlukan untuk akses.',
                'error_code' => 'DEVICE_ID_REQUIRED',
                'required_headers' => [
                    'X-Device-ID' => 'Unique device identifier (IMEI/UUID)',
                    'X-Device-Name' => 'Device name (optional)',
                    'X-Platform' => 'Platform (iOS/Android/Web)',
                ]
            ], 400);
        }

        $deviceFingerprint = UserDevice::generateFingerprint($deviceInfo);
        
        // Cek apakah device sudah terdaftar untuk user ini
        $existingDevice = UserDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $deviceFingerprint)
            ->first();

        if ($existingDevice) {
            // Device sudah terdaftar, cek status
            if (!$existingDevice->is_active || $existingDevice->status !== 'active') {
                Log::warning('Blocked access from inactive device', [
                    'user_id' => $user->id,
                    'device_id' => $deviceInfo['device_id'],
                    'status' => $existingDevice->status,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Akses dari device ini telah diblokir.',
                    'error_code' => 'DEVICE_BLOCKED',
                    'contact_admin' => true
                ], 403);
            }

            // Update activity
            $existingDevice->updateActivity();
            
        } else {
            // Device baru, cek apakah user sudah memiliki device lain (STRICT mode)
            $userActiveDevice = UserDevice::getUserActiveDevice($user->id);
            
            if ($userActiveDevice) {
                Log::warning('User trying to access from new device in STRICT mode', [
                    'user_id' => $user->id,
                    'existing_device' => $userActiveDevice->device_id,
                    'new_device' => $deviceInfo['device_id'],
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah terdaftar di device lain. Sistem hanya mengizinkan satu device per pengguna.',
                    'error_code' => 'DEVICE_LIMIT_EXCEEDED',
                    'current_device' => [
                        'name' => $userActiveDevice->formatted_device_info,
                        'last_login' => $userActiveDevice->last_login_at?->format('d M Y H:i'),
                        'verified' => $userActiveDevice->isTrusted()
                    ],
                    'action_required' => 'Hubungi admin untuk mengganti device.'
                ], 403);
            }

            // Bind device baru (first login)
            try {
                $newDevice = UserDevice::bindDevice($user->id, $deviceInfo);
                
                Log::info('New device bound to user', [
                    'user_id' => $user->id,
                    'device_id' => $deviceInfo['device_id'],
                    'device_name' => $deviceInfo['device_name'],
                    'platform' => $deviceInfo['platform'],
                    'ip' => $request->ip()
                ]);

                // Tambahkan header response untuk notifikasi client
                $request->attributes->set('device_binding', [
                    'new_device' => true,
                    'device_id' => $newDevice->id,
                    'requires_verification' => !$newDevice->isTrusted()
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to bind device', [
                    'user_id' => $user->id,
                    'device_info' => $deviceInfo,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mendaftarkan device baru.',
                    'error_code' => 'DEVICE_BINDING_FAILED'
                ], 500);
            }
        }

        return $next($request);
    }
}
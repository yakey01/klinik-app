<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'service' => 'Dokter Dashboard API',
            'version' => '1.0.0'
        ];
        
        // Database health check
        try {
            DB::connection()->getPdo();
            $health['database'] = 'connected';
        } catch (\Exception $e) {
            $health['database'] = 'disconnected';
            $health['status'] = 'degraded';
        }
        
        // Cache health check  
        try {
            Cache::put('health_check', 'ok', 60);
            $health['cache'] = Cache::get('health_check') === 'ok' ? 'working' : 'failed';
        } catch (\Exception $e) {
            $health['cache'] = 'failed';
        }
        
        $httpStatus = $health['status'] === 'healthy' ? 200 : 503;
        
        return response()->json($health, $httpStatus);
    }
    
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString()
        ]);
    }
}

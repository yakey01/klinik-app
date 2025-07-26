<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\EnhancedJaspelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MobileDashboardController extends Controller
{
    protected $enhancedJaspelService;

    public function __construct(EnhancedJaspelService $enhancedJaspelService)
    {
        $this->enhancedJaspelService = $enhancedJaspelService;
    }

    /**
     * Get Jaspel summary for mobile dashboard - FIXED VERSION
     * Endpoint: /api/mobile-dashboard/jaspel-summary
     */
    public function getJaspelSummary(Request $request)
    {
        try {
            // Get authenticated user
            $user = Auth::guard('web')->user() ?? Auth::guard('sanctum')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Verify user has access to Jaspel data
            if (!$user->hasRole(['paramedis', 'dokter', 'admin', 'bendahara'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to access Jaspel data'
                ], 403);
            }

            // Get month and year from request
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            // Log the request for debugging
            Log::info('NEW MOBILE DASHBOARD - Jaspel Summary Request', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'month' => $month,
                'year' => $year,
                'endpoint' => '/api/mobile-dashboard/jaspel-summary',
                'timestamp' => now()->toISOString()
            ]);

            // Use EnhancedJaspelService for consistent calculation
            $result = $this->enhancedJaspelService->getComprehensiveJaspelData($user, $month, $year, null);

            // Format response for mobile dashboard
            $response = [
                'success' => true,
                'message' => 'NEW FIXED: Jaspel summary retrieved successfully',
                'data' => [
                    'bulan_ini' => [
                        'pendapatan_layanan_medis' => $result['summary']['total_paid'] ?? 0,
                        'formatted' => 'Rp ' . number_format($result['summary']['total_paid'] ?? 0, 0, ',', '.'),
                    ],
                    'bulan_lalu' => [
                        'pendapatan_layanan_medis' => $result['summary']['last_month_paid'] ?? 0,
                        'formatted' => 'Rp ' . number_format($result['summary']['last_month_paid'] ?? 0, 0, ',', '.'),
                    ],
                    'growth' => [
                        'percentage' => $result['summary']['growth_percentage'] ?? 0,
                        'formatted' => '+' . ($result['summary']['growth_percentage'] ?? 0) . '%',
                        'direction' => ($result['summary']['growth_percentage'] ?? 0) >= 0 ? 'up' : 'down'
                    ],
                    'summary' => $result['summary'],
                    'raw_data' => $result['jaspel_items'] ?? []
                ],
                'meta' => [
                    'month' => $month,
                    'year' => $year,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'endpoint_version' => 'FIXED_V2',
                    'calculation_method' => 'EnhancedJaspelService',
                    'timestamp' => now()->toISOString()
                ]
            ];

            // Log the response for debugging
            Log::info('NEW MOBILE DASHBOARD - Jaspel Summary Response', [
                'user_id' => $user->id,
                'bulan_ini' => $response['data']['bulan_ini']['pendapatan_layanan_medis'],
                'bulan_lalu' => $response['data']['bulan_lalu']['pendapatan_layanan_medis'],
                'growth' => $response['data']['growth']['percentage'],
                'endpoint' => '/api/mobile-dashboard/jaspel-summary',
                'timestamp' => now()->toISOString()
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('NEW MOBILE DASHBOARD - Error retrieving Jaspel summary', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'endpoint' => '/api/mobile-dashboard/jaspel-summary',
                'timestamp' => now()->toISOString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Jaspel summary',
                'error' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}
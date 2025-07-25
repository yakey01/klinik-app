<?php

namespace App\Http\Controllers\Api\V2\Jaspel;

use App\Http\Controllers\Controller;
use App\Services\JaspelCalculationService;
use App\Models\Jaspel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class JaspelController extends Controller
{
    protected $jaspelService;

    public function __construct(JaspelCalculationService $jaspelService)
    {
        $this->jaspelService = $jaspelService;
    }

    /**
     * Get Jaspel summary for authenticated user
     */
    public function summary(Request $request)
    {
        try {
            $user = Auth::user();
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            $summary = $this->jaspelService->getJaspelSummary($user, $month, $year);
            $statistics = $this->jaspelService->getJaspelStatistics($user);

            return response()->json([
                'success' => true,
                'message' => 'Jaspel summary retrieved successfully',
                'data' => [
                    'summary' => $summary,
                    'trend' => $statistics['trend'],
                    'recent' => $statistics['recent']->map(function($jaspel) {
                        return [
                            'id' => $jaspel->id,
                            'date' => $jaspel->tanggal->format('Y-m-d'),
                            'type' => $jaspel->jenis_jaspel,
                            'amount' => $jaspel->nominal,
                            'status' => $jaspel->status_validasi,
                            'description' => $jaspel->keterangan,
                            'validated_by' => $jaspel->validasiBy ? $jaspel->validasiBy->name : null,
                            'validated_at' => $jaspel->validasi_at ? $jaspel->validasi_at->format('Y-m-d H:i:s') : null
                        ];
                    })
                ],
                'meta' => [
                    'month' => $month,
                    'year' => $year,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Jaspel summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed Jaspel history
     */
    public function history(Request $request)
    {
        try {
            $user = Auth::user();
            
            $filters = [
                'status' => $request->get('status'),
                'month' => $request->get('month'),
                'year' => $request->get('year'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            $jaspelQuery = $this->jaspelService->getJaspelHistory($user, $filters);
            
            $perPage = min($request->get('per_page', 15), 100);
            $jaspelHistory = $jaspelQuery->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Jaspel history retrieved successfully',
                'data' => $jaspelHistory->items(),
                'pagination' => [
                    'current_page' => $jaspelHistory->currentPage(),
                    'last_page' => $jaspelHistory->lastPage(),
                    'per_page' => $jaspelHistory->perPage(),
                    'total' => $jaspelHistory->total(),
                    'from' => $jaspelHistory->firstItem(),
                    'to' => $jaspelHistory->lastItem(),
                ],
                'meta' => [
                    'filters' => $filters,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Jaspel history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly Jaspel report
     */
    public function monthlyReport(Request $request, $year, $month)
    {
        try {
            $user = Auth::user();
            
            // Validate month and year
            if ($month < 1 || $month > 12 || $year < 2020 || $year > 2050) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid month or year'
                ], 422);
            }

            $summary = $this->jaspelService->getJaspelSummary($user, $month, $year);
            
            // Get detailed breakdown by date
            $dailyBreakdown = Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->selectRaw('DATE(tanggal) as date, status_validasi, COUNT(*) as count, SUM(nominal) as total')
                ->groupBy('date', 'status_validasi')
                ->orderBy('date')
                ->get()
                ->groupBy('date')
                ->map(function($dateGroup) {
                    $result = [
                        'date' => $dateGroup->first()->date,
                        'total' => 0,
                        'disetujui' => 0,
                        'pending' => 0,
                        'ditolak' => 0,
                        'count' => 0
                    ];
                    
                    foreach ($dateGroup as $status) {
                        $result['total'] += $status->total;
                        $result['count'] += $status->count;
                        $result[$status->status_validasi] = $status->total;
                    }
                    
                    return $result;
                })->values();

            // Get breakdown by type
            $typeBreakdown = Jaspel::where('user_id', $user->id)
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->selectRaw('jenis_jaspel, status_validasi, COUNT(*) as count, SUM(nominal) as total')
                ->groupBy('jenis_jaspel', 'status_validasi')
                ->get()
                ->groupBy('jenis_jaspel')
                ->map(function($typeGroup) {
                    $result = [
                        'type' => $typeGroup->first()->jenis_jaspel,
                        'total' => 0,
                        'disetujui' => 0,
                        'pending' => 0,
                        'ditolak' => 0,
                        'count' => 0
                    ];
                    
                    foreach ($typeGroup as $status) {
                        $result['total'] += $status->total;
                        $result['count'] += $status->count;
                        $result[$status->status_validasi] = $status->total;
                    }
                    
                    return $result;
                })->values();

            return response()->json([
                'success' => true,
                'message' => 'Monthly report retrieved successfully',
                'data' => [
                    'summary' => $summary,
                    'daily_breakdown' => $dailyBreakdown,
                    'type_breakdown' => $typeBreakdown,
                    'month_name' => Carbon::createFromDate($year, $month, 1)->format('F Y')
                ],
                'meta' => [
                    'month' => $month,
                    'year' => $year,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve monthly report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get yearly Jaspel summary
     */
    public function yearlySummary(Request $request, $year)
    {
        try {
            $user = Auth::user();
            
            // Validate year
            if ($year < 2020 || $year > 2050) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid year'
                ], 422);
            }

            $monthlySummaries = [];
            $yearlyTotal = 0;
            $yearlyApproved = 0;
            $yearlyPending = 0;
            $yearlyRejected = 0;

            // Get summary for each month
            for ($month = 1; $month <= 12; $month++) {
                $summary = $this->jaspelService->getJaspelSummary($user, $month, $year);
                $monthlySummaries[] = [
                    'month' => $month,
                    'month_name' => Carbon::createFromDate($year, $month, 1)->format('F'),
                    'total' => $summary['total'],
                    'approved' => $summary['approved'],
                    'pending' => $summary['pending'],
                    'rejected' => $summary['rejected'],
                    'count' => $summary['count']['total']
                ];
                
                $yearlyTotal += $summary['total'];
                $yearlyApproved += $summary['approved'];
                $yearlyPending += $summary['pending'];
                $yearlyRejected += $summary['rejected'];
            }

            return response()->json([
                'success' => true,
                'message' => 'Yearly summary retrieved successfully',
                'data' => [
                    'yearly_total' => [
                        'total' => $yearlyTotal,
                        'approved' => $yearlyApproved,
                        'pending' => $yearlyPending,
                        'rejected' => $yearlyRejected
                    ],
                    'monthly_summaries' => $monthlySummaries
                ],
                'meta' => [
                    'year' => $year,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve yearly summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Jaspel data from validated tindakan for mobile app
     */
    public function getMobileJaspelData(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get Jaspel records from validated tindakan
            $jaspelQuery = Jaspel::where('user_id', $user->id)
                ->with(['tindakan.jenisTindakan', 'tindakan.pasien', 'validasiBy'])
                ->whereHas('tindakan', function($query) {
                    $query->where('status_validasi', 'approved');
                });

            // Apply filters
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);
            $status = $request->get('status');

            if ($month && $year) {
                $jaspelQuery->whereMonth('tanggal', $month)
                           ->whereYear('tanggal', $year);
            }

            if ($status) {
                $jaspelQuery->where('status_validasi', $status);
            }

            $jaspelData = $jaspelQuery->orderBy('tanggal', 'desc')->get();

            // Calculate summaries
            $totalPaid = $jaspelData->where('status_validasi', 'disetujui')->sum('nominal');
            $totalPending = $jaspelData->where('status_validasi', 'pending')->sum('nominal');
            $totalRejected = $jaspelData->where('status_validasi', 'ditolak')->sum('nominal');

            // Format data for mobile app
            $formattedData = $jaspelData->map(function($jaspel) {
                $tindakan = $jaspel->tindakan;
                $jenisTindakan = $tindakan ? $tindakan->jenisTindakan : null;
                $pasien = $tindakan ? $tindakan->pasien : null;

                return [
                    'id' => (string) $jaspel->id,
                    'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                    'jenis' => $jenisTindakan ? $jenisTindakan->nama : 'Jaspel ' . ucwords(str_replace('_', ' ', $jaspel->jenis_jaspel)),
                    'jumlah' => (int) $jaspel->nominal,
                    'status' => $jaspel->status_validasi === 'disetujui' ? 'paid' : 
                               ($jaspel->status_validasi === 'pending' ? 'pending' : 'rejected'),
                    'keterangan' => $jaspel->keterangan ?: (
                        $pasien ? "Pasien: {$pasien->nama}" : 
                        ($jenisTindakan ? $jenisTindakan->nama : 'Jaspel medis')
                    ),
                    'validated_by' => $jaspel->validasiBy ? $jaspel->validasiBy->name : null,
                    'validated_at' => $jaspel->validasi_at ? $jaspel->validasi_at->format('Y-m-d H:i:s') : null
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Mobile Jaspel data retrieved successfully',
                'data' => [
                    'jaspel_items' => $formattedData,
                    'summary' => [
                        'total_paid' => (int) $totalPaid,
                        'total_pending' => (int) $totalPending,
                        'total_rejected' => (int) $totalRejected,
                        'count_paid' => $jaspelData->where('status_validasi', 'disetujui')->count(),
                        'count_pending' => $jaspelData->where('status_validasi', 'pending')->count(),
                        'count_rejected' => $jaspelData->where('status_validasi', 'ditolak')->count(),
                    ]
                ],
                'meta' => [
                    'month' => $month,
                    'year' => $year,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve mobile Jaspel data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate Jaspel from validated tindakan (admin only)
     */
    public function calculateFromTindakan(Request $request)
    {
        try {
            // Check if user has permission
            if (!Auth::user()->hasRole(['admin', 'bendahara'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $createdJaspel = $this->jaspelService->bulkCalculateFromValidatedTindakan(
                $request->get('start_date'),
                $request->get('end_date')
            );

            return response()->json([
                'success' => true,
                'message' => 'Jaspel calculated successfully',
                'data' => [
                    'created_count' => count($createdJaspel),
                    'total_amount' => collect($createdJaspel)->sum('nominal')
                ],
                'meta' => [
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate Jaspel',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Jaspel;
use App\Services\Jaspel\JaspelService;
use App\Repositories\JaspelRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ParamedisJaspelController extends Controller
{
    protected $jaspelRepository;
    protected $jaspelService;

    public function __construct(
        JaspelRepository $jaspelRepository,
        JaspelService $jaspelService
    ) {
        $this->jaspelRepository = $jaspelRepository;
        $this->jaspelService = $jaspelService;
    }

    public function dashboard()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect('/login')->with('error', 'Please login first');
        }

        // Get current user's jaspel data
        $userId = $user->id;
        
        // Get monthly data (current month)
        $currentMonth = Carbon::now();
        $monthlyFilters = [
            'tanggal_dari' => $currentMonth->startOfMonth()->format('Y-m-d'),
            'tanggal_sampai' => $currentMonth->endOfMonth()->format('Y-m-d')
        ];
        
        // Get weekly data (last 7 days)
        $weeklyFilters = [
            'tanggal_dari' => Carbon::now()->subDays(6)->format('Y-m-d'),
            'tanggal_sampai' => Carbon::now()->format('Y-m-d')
        ];
        
        // Get daily data (today)
        $dailyFilters = [
            'tanggal_dari' => Carbon::today()->format('Y-m-d'),
            'tanggal_sampai' => Carbon::today()->format('Y-m-d')
        ];
        
        // Get previous month data for comparison
        $prevMonth = Carbon::now()->subMonth();
        $prevMonthFilters = [
            'tanggal_dari' => $prevMonth->startOfMonth()->format('Y-m-d'),
            'tanggal_sampai' => $prevMonth->endOfMonth()->format('Y-m-d')
        ];

        try {
            // Get approved jaspel data only
            $monthlyJaspel = Jaspel::where('user_id', $userId)
                ->where('jenis_jaspel', 'paramedis')
                ->approved()
                ->whereBetween('tanggal', [$monthlyFilters['tanggal_dari'], $monthlyFilters['tanggal_sampai']])
                ->sum('nominal');

            $weeklyJaspel = Jaspel::where('user_id', $userId)
                ->where('jenis_jaspel', 'paramedis')
                ->approved()
                ->whereBetween('tanggal', [$weeklyFilters['tanggal_dari'], $weeklyFilters['tanggal_sampai']])
                ->sum('nominal');

            $dailyJaspel = Jaspel::where('user_id', $userId)
                ->where('jenis_jaspel', 'paramedis')
                ->approved()
                ->whereBetween('tanggal', [$dailyFilters['tanggal_dari'], $dailyFilters['tanggal_sampai']])
                ->sum('nominal');

            $prevMonthJaspel = Jaspel::where('user_id', $userId)
                ->where('jenis_jaspel', 'paramedis')
                ->approved()
                ->whereBetween('tanggal', [$prevMonthFilters['tanggal_dari'], $prevMonthFilters['tanggal_sampai']])
                ->sum('nominal');

            // Calculate percentage change
            $changePercentage = 0;
            if ($prevMonthJaspel > 0) {
                $changePercentage = (($monthlyJaspel - $prevMonthJaspel) / $prevMonthJaspel) * 100;
            } elseif ($monthlyJaspel > 0) {
                $changePercentage = 100;
            }

            // Get recent jaspel records for activity
            $recentJaspel = Jaspel::where('user_id', $userId)
                ->where('jenis_jaspel', 'paramedis')
                ->approved()
                ->with(['tindakan', 'tindakan.jenisTindakan', 'tindakan.pasien'])
                ->orderBy('tanggal', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($jaspel) {
                    return [
                        'id' => $jaspel->id,
                        'tanggal' => $jaspel->tanggal->format('Y-m-d'),
                        'nominal' => $jaspel->nominal,
                        'tindakan' => $jaspel->tindakan ? [
                            'nama' => $jaspel->tindakan->jenisTindakan->nama_tindakan ?? null,
                            'pasien' => $jaspel->tindakan->pasien->nama ?? null
                        ] : null,
                        'status' => $jaspel->status_validasi
                    ];
                });

            // Calculate daily average
            $daysInMonth = $currentMonth->daysInMonth;
            $dailyAverage = $monthlyJaspel / $daysInMonth;

            // Estimate weekly (7 days average)
            $weeklyEstimate = $dailyAverage * 7;

            $laravelJaspelData = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => 'Paramedis'
                ],
                'jaspel' => [
                    'monthly' => (float) $monthlyJaspel,
                    'weekly' => (float) $weeklyJaspel,
                    'daily' => (float) $dailyJaspel,
                    'previous_month' => (float) $prevMonthJaspel,
                    'change_percentage' => round($changePercentage, 1),
                    'daily_average' => round($dailyAverage, 0),
                    'weekly_estimate' => round($weeklyEstimate, 0),
                    'recent' => $recentJaspel
                ],
                'statistics' => [
                    'total_records' => Jaspel::where('user_id', $userId)
                        ->where('jenis_jaspel', 'paramedis')
                        ->approved()
                        ->count(),
                    'pending_records' => Jaspel::where('user_id', $userId)
                        ->where('jenis_jaspel', 'paramedis')
                        ->pending()
                        ->count()
                ]
            ];

        } catch (\Exception $e) {
            // Fallback data in case of error
            $laravelJaspelData = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => 'Paramedis'
                ],
                'jaspel' => [
                    'monthly' => 0,
                    'weekly' => 0,
                    'daily' => 0,
                    'previous_month' => 0,
                    'change_percentage' => 0,
                    'daily_average' => 0,
                    'weekly_estimate' => 0,
                    'recent' => []
                ],
                'statistics' => [
                    'total_records' => 0,
                    'pending_records' => 0
                ],
                'error' => $e->getMessage()
            ];
        }

        return view('paramedis.jaspel-dashboard', compact('laravelJaspelData'));
    }

    public function apiData(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userId = $user->id;
        
        // Get filters from request
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai']);
        
        // Default to current month if no filters
        if (empty($filters['tanggal_dari']) || empty($filters['tanggal_sampai'])) {
            $currentMonth = Carbon::now();
            $filters = [
                'tanggal_dari' => $currentMonth->startOfMonth()->format('Y-m-d'),
                'tanggal_sampai' => $currentMonth->endOfMonth()->format('Y-m-d')
            ];
        }

        try {
            $summary = $this->jaspelRepository->getSummaryByUser($userId, $filters);
            $recentJaspel = $this->jaspelRepository->getByUser($userId, array_merge($filters, ['limit' => 10]));

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'recent' => $recentJaspel,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => 'Paramedis'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
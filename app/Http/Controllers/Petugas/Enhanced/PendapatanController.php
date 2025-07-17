<?php

namespace App\Http\Controllers\Petugas\Enhanced;

use App\Http\Controllers\Controller;
use App\Models\Pendapatan;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use App\Models\Pasien;
use App\Models\Dokter;
use App\Models\Pegawai;
use App\Services\PetugasDataService;
use App\Services\PetugasStatsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PendapatanController extends Controller
{
    protected $dataService;
    protected $statsService;

    public function __construct(PetugasDataService $dataService, PetugasStatsService $statsService)
    {
        $this->dataService = $dataService;
        $this->statsService = $statsService;
        
        // Apply role-based middleware
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->check() || !auth()->user()->hasRole('petugas')) {
                abort(403, 'Access denied. Petugas role required.');
            }
            return $next($request);
        });
    }

    /**
     * Display enhanced pendapatan list with analytics
     */
    public function index(): View
    {
        // Get revenue statistics
        $stats = $this->getRevenueStats();
        
        // Get recent revenue entries
        $recentPendapatan = $this->getRecentPendapatan(5);
        
        // Get revenue trends for chart
        $trends = $this->getRevenueTrends();
        
        return view('petugas.enhanced.pendapatan.index', compact('stats', 'recentPendapatan', 'trends'));
    }

    /**
     * Get paginated pendapatan data for AJAX
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 100);
            $search = $request->get('search');
            $filters = $request->only(['tanggal_from', 'tanggal_to', 'sumber', 'kategori', 'min_jumlah', 'max_jumlah']);
            $sort = $request->get('sort', 'tanggal_pendapatan');
            $direction = $request->get('direction', 'desc');

            $query = Pendapatan::with([
                'tindakan.pasien:id,nama_pasien,nomor_pasien',
                'tindakan.jenisTindakan:id,nama_tindakan',
                'inputBy:id,name'
            ]);

            // Apply search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('sumber_pendapatan', 'like', "%{$search}%")
                      ->orWhere('keterangan', 'like', "%{$search}%")
                      ->orWhereHas('tindakan.pasien', function ($q) use ($search) {
                          $q->where('nama_pasien', 'like', "%{$search}%")
                            ->orWhere('nomor_pasien', 'like', "%{$search}%");
                      })
                      ->orWhereHas('tindakan.jenisTindakan', function ($q) use ($search) {
                          $q->where('nama_tindakan', 'like', "%{$search}%");
                      });
                });
            }

            // Apply filters
            if (!empty($filters['tanggal_from'])) {
                $query->where('tanggal_pendapatan', '>=', $filters['tanggal_from']);
            }

            if (!empty($filters['tanggal_to'])) {
                $query->where('tanggal_pendapatan', '<=', $filters['tanggal_to']);
            }

            if (!empty($filters['sumber'])) {
                $query->where('sumber_pendapatan', 'like', "%{$filters['sumber']}%");
            }

            if (!empty($filters['kategori'])) {
                $kategoriesMap = [
                    'tindakan' => 'Tindakan Medis',
                    'konsultasi' => 'Konsultasi',
                    'obat' => 'Obat-obatan',
                    'alat' => 'Alat Medis',
                    'lainnya' => 'Lainnya'
                ];
                if (isset($kategoriesMap[$filters['kategori']])) {
                    $query->where('sumber_pendapatan', 'like', "%{$kategoriesMap[$filters['kategori']]}%");
                }
            }

            if (!empty($filters['min_jumlah'])) {
                $query->where('jumlah', '>=', $filters['min_jumlah']);
            }

            if (!empty($filters['max_jumlah'])) {
                $query->where('jumlah', '<=', $filters['max_jumlah']);
            }

            // Apply sorting
            $allowedSorts = ['tanggal_pendapatan', 'jumlah', 'created_at', 'sumber_pendapatan'];
            if (in_array($sort, $allowedSorts)) {
                $query->orderBy($sort, $direction);
            }

            $pendapatan = $query->paginate($perPage);

            // Transform data for better frontend handling
            $transformedData = $pendapatan->through(function ($item) {
                return [
                    'id' => $item->id,
                    'tanggal_pendapatan' => $item->tanggal_pendapatan,
                    'sumber_pendapatan' => $item->sumber_pendapatan,
                    'jumlah' => $item->jumlah,
                    'keterangan' => $item->keterangan,
                    'tindakan' => $item->tindakan ? [
                        'id' => $item->tindakan->id,
                        'jenis' => $item->tindakan->jenisTindakan?->nama_tindakan,
                        'pasien' => $item->tindakan->pasien?->nama_pasien,
                        'nomor_pasien' => $item->tindakan->pasien?->nomor_pasien,
                    ] : null,
                    'input_by' => $item->inputBy?->name,
                    'created_at' => $item->created_at,
                    'formatted_date' => Carbon::parse($item->tanggal_pendapatan)->format('d M Y'),
                    'formatted_jumlah' => 'Rp ' . number_format($item->jumlah, 0, ',', '.'),
                    'kategori' => $this->categorizeRevenue($item->sumber_pendapatan),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'meta' => [
                    'current_page' => $pendapatan->currentPage(),
                    'last_page' => $pendapatan->lastPage(),
                    'per_page' => $pendapatan->perPage(),
                    'total' => $pendapatan->total(),
                    'from' => $pendapatan->firstItem(),
                    'to' => $pendapatan->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Pendapatan getData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data pendapatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show smart create form
     */
    public function create(): View
    {
        // Get data for smart suggestions
        $recentTindakan = Tindakan::with(['pasien:id,nama_pasien,nomor_pasien', 'jenisTindakan:id,nama_tindakan'])
                                 ->whereDoesntHave('pendapatan')
                                 ->where('status_validasi', 'approved')
                                 ->where('tanggal_tindakan', '>=', Carbon::now()->subDays(30))
                                 ->orderByDesc('tanggal_tindakan')
                                 ->limit(20)
                                 ->get();

        $sumberSuggestions = $this->getRevenueSources();
        $revenueTemplates = $this->getRevenueTemplates();
        
        return view('petugas.enhanced.pendapatan.create', compact(
            'recentTindakan', 'sumberSuggestions', 'revenueTemplates'
        ));
    }

    /**
     * Store new pendapatan with smart validation
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tanggal_pendapatan' => 'required|date',
                'sumber_pendapatan' => 'required|string|max:255',
                'jumlah' => 'required|numeric|min:0',
                'keterangan' => 'nullable|string|max:1000',
                'tindakan_id' => 'nullable|exists:tindakans,id',
                'auto_create_from_tindakan' => 'nullable|boolean',
            ]);

            // Smart validation: prevent duplicate entries
            if ($validated['tindakan_id']) {
                $existingPendapatan = Pendapatan::where('tindakan_id', $validated['tindakan_id'])->first();
                if ($existingPendapatan) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pendapatan untuk tindakan ini sudah ada.',
                        'existing_id' => $existingPendapatan->id
                    ], 422);
                }
            }

            $validated['input_by'] = auth()->id();

            // Auto-populate from tindakan if requested
            if ($validated['auto_create_from_tindakan'] && $validated['tindakan_id']) {
                $tindakan = Tindakan::find($validated['tindakan_id']);
                if ($tindakan) {
                    $validated['jumlah'] = $tindakan->tarif;
                    $validated['sumber_pendapatan'] = $tindakan->jenisTindakan->nama_tindakan ?? 'Tindakan Medis';
                    $validated['keterangan'] = "Pendapatan dari tindakan #{$tindakan->id} untuk pasien {$tindakan->pasien->nama_pasien}";
                }
            }

            $pendapatan = Pendapatan::create($validated);

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Pendapatan berhasil disimpan',
                'data' => $pendapatan->load(['tindakan.pasien', 'tindakan.jenisTindakan'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Enhanced Pendapatan store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pendapatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific pendapatan detail
     */
    public function show($id): View
    {
        $pendapatan = Pendapatan::with([
            'tindakan.pasien',
            'tindakan.jenisTindakan',
            'tindakan.dokter',
            'inputBy'
        ])->findOrFail($id);

        // Get related revenue entries for context
        $relatedRevenue = $this->getRelatedRevenue($pendapatan);

        return view('petugas.enhanced.pendapatan.show', compact('pendapatan', 'relatedRevenue'));
    }

    /**
     * Show edit form
     */
    public function edit($id): View
    {
        $pendapatan = Pendapatan::with([
            'tindakan.pasien',
            'tindakan.jenisTindakan'
        ])->findOrFail($id);

        $sumberSuggestions = $this->getRevenueSources();
        $revenueTemplates = $this->getRevenueTemplates();
        
        return view('petugas.enhanced.pendapatan.edit', compact(
            'pendapatan', 'sumberSuggestions', 'revenueTemplates'
        ));
    }

    /**
     * Update existing pendapatan
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $pendapatan = Pendapatan::findOrFail($id);

            $validated = $request->validate([
                'tanggal_pendapatan' => 'required|date',
                'sumber_pendapatan' => 'required|string|max:255',
                'jumlah' => 'required|numeric|min:0',
                'keterangan' => 'nullable|string|max:1000',
                'tindakan_id' => 'nullable|exists:tindakans,id',
            ]);

            // Smart validation: prevent duplicate tindakan assignments
            if ($validated['tindakan_id'] && $validated['tindakan_id'] != $pendapatan->tindakan_id) {
                $existingPendapatan = Pendapatan::where('tindakan_id', $validated['tindakan_id'])
                                               ->where('id', '!=', $id)
                                               ->first();
                if ($existingPendapatan) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tindakan ini sudah memiliki pendapatan lain.',
                        'existing_id' => $existingPendapatan->id
                    ], 422);
                }
            }

            $pendapatan->update($validated);

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Pendapatan berhasil diperbarui',
                'data' => $pendapatan->load(['tindakan.pasien', 'tindakan.jenisTindakan'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Enhanced Pendapatan update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pendapatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete pendapatan
     */
    public function destroy($id): JsonResponse
    {
        try {
            $pendapatan = Pendapatan::findOrFail($id);
            $pendapatan->delete();

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Pendapatan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Pendapatan destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pendapatan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk create from approved tindakan
     */
    public function bulkCreateFromTindakan(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tindakan_ids' => 'required|array',
                'tindakan_ids.*' => 'exists:tindakans,id',
                'auto_calculate' => 'boolean'
            ]);

            $created = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($validated['tindakan_ids'] as $tindakanId) {
                // Check if pendapatan already exists
                if (Pendapatan::where('tindakan_id', $tindakanId)->exists()) {
                    $skipped++;
                    continue;
                }

                $tindakan = Tindakan::with(['pasien', 'jenisTindakan'])->find($tindakanId);
                if (!$tindakan || $tindakan->status_validasi !== 'approved') {
                    $skipped++;
                    continue;
                }

                try {
                    Pendapatan::create([
                        'tanggal_pendapatan' => $tindakan->tanggal_tindakan,
                        'sumber_pendapatan' => $tindakan->jenisTindakan->nama_tindakan ?? 'Tindakan Medis',
                        'jumlah' => $tindakan->tarif,
                        'keterangan' => "Auto-generated dari tindakan #{$tindakan->id} untuk pasien {$tindakan->pasien->nama_pasien}",
                        'tindakan_id' => $tindakan->id,
                        'input_by' => auth()->id(),
                    ]);
                    $created++;
                } catch (\Exception $e) {
                    $errors[] = "Tindakan #{$tindakanId}: " . $e->getMessage();
                }
            }

            DB::commit();

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => "Berhasil membuat {$created} pendapatan, {$skipped} dilewati",
                'data' => [
                    'created' => $created,
                    'skipped' => $skipped,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Enhanced Pendapatan bulk create error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pendapatan massal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue analytics data
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // week, month, quarter, year
            $analytics = $this->generateRevenueAnalytics($period);

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Pendapatan analytics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export pendapatan data
     */
    public function export(Request $request): JsonResponse
    {
        try {
            // This would typically generate an Excel/CSV file
            // For now, return success message
            return response()->json([
                'success' => true,
                'message' => 'Export akan segera tersedia',
                'download_url' => '#'
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Pendapatan export error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get smart suggestions for revenue input
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'sumber');
            $query = $request->get('q', '');

            $suggestions = [];

            switch ($type) {
                case 'sumber':
                    $suggestions = $this->getRevenueSources($query);
                    break;
                case 'tindakan':
                    $suggestions = $this->getUnpaidTindakan($query);
                    break;
                case 'template':
                    $suggestions = $this->getRevenueTemplates($query);
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Pendapatan suggestions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat suggestions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get revenue statistics
     */
    private function getRevenueStats(): array
    {
        return Cache::remember('enhanced_pendapatan_stats', 300, function () {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

            $todayRevenue = Pendapatan::whereDate('tanggal_pendapatan', $today)->sum('jumlah');
            $monthRevenue = Pendapatan::where('tanggal_pendapatan', '>=', $thisMonth)->sum('jumlah');
            $lastMonthRevenue = Pendapatan::whereBetween('tanggal_pendapatan', [$lastMonth, $lastMonthEnd])->sum('jumlah');

            $growth = $lastMonthRevenue > 0 ? (($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

            return [
                'today' => $todayRevenue,
                'month' => $monthRevenue,
                'last_month' => $lastMonthRevenue,
                'growth_percentage' => round($growth, 2),
                'total_entries_today' => Pendapatan::whereDate('tanggal_pendapatan', $today)->count(),
                'total_entries_month' => Pendapatan::where('tanggal_pendapatan', '>=', $thisMonth)->count(),
                'avg_transaction' => Pendapatan::where('tanggal_pendapatan', '>=', $thisMonth)->avg('jumlah') ?? 0,
                'highest_transaction' => Pendapatan::where('tanggal_pendapatan', '>=', $thisMonth)->max('jumlah') ?? 0,
                'categories' => $this->getRevenueByCategory(),
                'unpaid_tindakan_count' => $this->getUnpaidTindakanCount(),
            ];
        });
    }

    /**
     * Get recent pendapatan entries
     */
    private function getRecentPendapatan(int $limit = 10): array
    {
        return Pendapatan::with(['tindakan.pasien:id,nama_pasien', 'tindakan.jenisTindakan:id,nama_tindakan'])
                         ->orderByDesc('created_at')
                         ->limit($limit)
                         ->get()
                         ->map(function ($item) {
                             return [
                                 'id' => $item->id,
                                 'sumber' => $item->sumber_pendapatan,
                                 'jumlah' => $item->jumlah,
                                 'tanggal' => $item->tanggal_pendapatan,
                                 'tindakan' => $item->tindakan ? [
                                     'jenis' => $item->tindakan->jenisTindakan?->nama_tindakan,
                                     'pasien' => $item->tindakan->pasien?->nama_pasien,
                                 ] : null,
                                 'created_at' => $item->created_at,
                             ];
                         })
                         ->toArray();
    }

    /**
     * Get revenue trends for charts
     */
    private function getRevenueTrends(): array
    {
        return Cache::remember('revenue_trends_7days', 900, function () {
            $trends = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $revenue = Pendapatan::whereDate('tanggal_pendapatan', $date)->sum('jumlah');
                $trends[] = [
                    'date' => $date->format('Y-m-d'),
                    'formatted_date' => $date->format('d M'),
                    'revenue' => $revenue,
                    'count' => Pendapatan::whereDate('tanggal_pendapatan', $date)->count(),
                ];
            }
            return $trends;
        });
    }

    /**
     * Get revenue by category
     */
    private function getRevenueByCategory(): array
    {
        $categories = [
            'Tindakan Medis' => 0,
            'Konsultasi' => 0,
            'Obat-obatan' => 0,
            'Alat Medis' => 0,
            'Lainnya' => 0,
        ];

        $thisMonth = Carbon::now()->startOfMonth();
        $revenues = Pendapatan::where('tanggal_pendapatan', '>=', $thisMonth)
                             ->get(['sumber_pendapatan', 'jumlah']);

        foreach ($revenues as $revenue) {
            $category = $this->categorizeRevenue($revenue->sumber_pendapatan);
            if (isset($categories[$category])) {
                $categories[$category] += $revenue->jumlah;
            } else {
                $categories['Lainnya'] += $revenue->jumlah;
            }
        }

        return $categories;
    }

    /**
     * Categorize revenue source
     */
    private function categorizeRevenue(string $sumber): string
    {
        $sumber = strtolower($sumber);
        
        if (str_contains($sumber, 'konsultasi') || str_contains($sumber, 'konsul')) {
            return 'Konsultasi';
        } elseif (str_contains($sumber, 'obat') || str_contains($sumber, 'farmasi')) {
            return 'Obat-obatan';
        } elseif (str_contains($sumber, 'alat') || str_contains($sumber, 'peralatan')) {
            return 'Alat Medis';
        } elseif (str_contains($sumber, 'tindakan') || str_contains($sumber, 'medis') || str_contains($sumber, 'operasi')) {
            return 'Tindakan Medis';
        }
        
        return 'Lainnya';
    }

    /**
     * Get common revenue sources for suggestions
     */
    private function getRevenueSources(string $query = ''): array
    {
        $baseSources = [
            'Tindakan Medis Umum',
            'Konsultasi Dokter',
            'Pemeriksaan Laboratorium',
            'Radiologi/Rontgen',
            'USG',
            'EKG',
            'Vaksinasi',
            'Medical Check Up',
            'Obat-obatan',
            'Alat Medis',
            'Konsultasi Spesialis',
            'Tindakan Gigi',
            'Fisioterapi',
            'Rawat Luka',
        ];

        // Add recent unique sources from database
        $recentSources = Pendapatan::select('sumber_pendapatan')
                                  ->where('created_at', '>=', Carbon::now()->subDays(30))
                                  ->distinct()
                                  ->pluck('sumber_pendapatan')
                                  ->toArray();

        $allSources = array_unique(array_merge($baseSources, $recentSources));

        if ($query) {
            $allSources = array_filter($allSources, function ($source) use ($query) {
                return stripos($source, $query) !== false;
            });
        }

        return array_values($allSources);
    }

    /**
     * Get revenue templates for quick input
     */
    private function getRevenueTemplates(string $query = ''): array
    {
        $templates = [
            [
                'name' => 'Konsultasi Dokter Umum',
                'sumber' => 'Konsultasi Dokter Umum',
                'jumlah' => 50000,
                'keterangan' => 'Konsultasi medis umum'
            ],
            [
                'name' => 'Pemeriksaan Darah Lengkap',
                'sumber' => 'Pemeriksaan Laboratorium',
                'jumlah' => 75000,
                'keterangan' => 'Lab darah lengkap'
            ],
            [
                'name' => 'USG',
                'sumber' => 'USG',
                'jumlah' => 150000,
                'keterangan' => 'Pemeriksaan USG'
            ],
            [
                'name' => 'Rontgen Thorax',
                'sumber' => 'Radiologi/Rontgen',
                'jumlah' => 100000,
                'keterangan' => 'Foto rontgen dada'
            ],
            [
                'name' => 'Vaksinasi COVID-19',
                'sumber' => 'Vaksinasi',
                'jumlah' => 85000,
                'keterangan' => 'Vaksin COVID-19'
            ],
        ];

        if ($query) {
            $templates = array_filter($templates, function ($template) use ($query) {
                return stripos($template['name'], $query) !== false || stripos($template['sumber'], $query) !== false;
            });
        }

        return array_values($templates);
    }

    /**
     * Get unpaid tindakan for suggestions
     */
    private function getUnpaidTindakan(string $query = ''): array
    {
        $queryBuilder = Tindakan::with(['pasien:id,nama_pasien,nomor_pasien', 'jenisTindakan:id,nama_tindakan'])
                                ->whereDoesntHave('pendapatan')
                                ->where('status_validasi', 'approved')
                                ->where('tanggal_tindakan', '>=', Carbon::now()->subDays(30));

        if ($query) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->whereHas('pasien', function ($q) use ($query) {
                    $q->where('nama_pasien', 'like', "%{$query}%")
                      ->orWhere('nomor_pasien', 'like', "%{$query}%");
                })
                ->orWhereHas('jenisTindakan', function ($q) use ($query) {
                    $q->where('nama_tindakan', 'like', "%{$query}%");
                });
            });
        }

        return $queryBuilder->limit(10)
                           ->get()
                           ->map(function ($tindakan) {
                               return [
                                   'id' => $tindakan->id,
                                   'text' => "#{$tindakan->id} - {$tindakan->jenisTindakan?->nama_tindakan} - {$tindakan->pasien?->nama_pasien}",
                                   'tarif' => $tindakan->tarif,
                                   'tanggal' => $tindakan->tanggal_tindakan,
                                   'pasien' => $tindakan->pasien?->nama_pasien,
                                   'jenis' => $tindakan->jenisTindakan?->nama_tindakan,
                               ];
                           })
                           ->toArray();
    }

    /**
     * Get count of unpaid tindakan
     */
    private function getUnpaidTindakanCount(): int
    {
        return Tindakan::whereDoesntHave('pendapatan')
                      ->where('status_validasi', 'approved')
                      ->where('tanggal_tindakan', '>=', Carbon::now()->subDays(30))
                      ->count();
    }

    /**
     * Get related revenue entries
     */
    private function getRelatedRevenue($pendapatan): array
    {
        if (!$pendapatan->tindakan_id) {
            return [];
        }

        // Get other revenue from same patient
        return Pendapatan::whereHas('tindakan', function ($q) use ($pendapatan) {
                            $q->where('pasien_id', $pendapatan->tindakan->pasien_id);
                        })
                        ->where('id', '!=', $pendapatan->id)
                        ->with(['tindakan.jenisTindakan'])
                        ->orderByDesc('tanggal_pendapatan')
                        ->limit(5)
                        ->get()
                        ->toArray();
    }

    /**
     * Generate revenue analytics
     */
    private function generateRevenueAnalytics(string $period): array
    {
        // This would generate comprehensive analytics based on the period
        // For now, return basic structure
        return [
            'period' => $period,
            'total_revenue' => 0,
            'transaction_count' => 0,
            'avg_transaction' => 0,
            'growth_rate' => 0,
            'top_sources' => [],
            'trends' => [],
            'forecasts' => [],
        ];
    }
}
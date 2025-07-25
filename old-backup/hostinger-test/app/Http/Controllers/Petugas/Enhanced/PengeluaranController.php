<?php

namespace App\Http\Controllers\Petugas\Enhanced;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use App\Services\PetugasDataService;
use App\Services\PetugasStatsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PengeluaranController extends Controller
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
     * Display enhanced pengeluaran list with budget analytics
     */
    public function index(): View
    {
        // Get expense statistics
        $stats = $this->getExpenseStats();
        
        // Get recent expenses
        $recentPengeluaran = $this->getRecentPengeluaran(5);
        
        // Get budget analysis
        $budgetAnalysis = $this->getBudgetAnalysis();
        
        // Get expense trends for chart
        $trends = $this->getExpenseTrends();
        
        return view('petugas.enhanced.pengeluaran.index', compact('stats', 'recentPengeluaran', 'budgetAnalysis', 'trends'));
    }

    /**
     * Get paginated pengeluaran data for AJAX
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 100);
            $search = $request->get('search');
            $filters = $request->only(['tanggal_from', 'tanggal_to', 'kategori', 'priority', 'min_jumlah', 'max_jumlah', 'status']);
            $sort = $request->get('sort', 'tanggal_pengeluaran');
            $direction = $request->get('direction', 'desc');

            $query = Pengeluaran::with(['inputBy:id,name']);

            // Apply search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_pengeluaran', 'like', "%{$search}%")
                      ->orWhere('keterangan', 'like', "%{$search}%")
                      ->orWhere('kategori', 'like', "%{$search}%");
                });
            }

            // Apply filters
            if (!empty($filters['tanggal_from'])) {
                $query->where('tanggal_pengeluaran', '>=', $filters['tanggal_from']);
            }

            if (!empty($filters['tanggal_to'])) {
                $query->where('tanggal_pengeluaran', '<=', $filters['tanggal_to']);
            }

            if (!empty($filters['kategori'])) {
                $query->where('kategori', $filters['kategori']);
            }

            if (!empty($filters['priority'])) {
                $query->where('priority', $filters['priority']);
            }

            if (!empty($filters['min_jumlah'])) {
                $query->where('jumlah', '>=', $filters['min_jumlah']);
            }

            if (!empty($filters['max_jumlah'])) {
                $query->where('jumlah', '<=', $filters['max_jumlah']);
            }

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Apply sorting
            $allowedSorts = ['tanggal_pengeluaran', 'jumlah', 'created_at', 'nama_pengeluaran', 'kategori'];
            if (in_array($sort, $allowedSorts)) {
                $query->orderBy($sort, $direction);
            }

            $pengeluaran = $query->paginate($perPage);

            // Transform data for better frontend handling
            $transformedData = $pengeluaran->through(function ($item) {
                return [
                    'id' => $item->id,
                    'tanggal_pengeluaran' => $item->tanggal_pengeluaran,
                    'nama_pengeluaran' => $item->nama_pengeluaran,
                    'kategori' => $item->kategori,
                    'jumlah' => $item->jumlah,
                    'keterangan' => $item->keterangan,
                    'priority' => $item->priority ?? 'medium',
                    'status' => $item->status ?? 'pending',
                    'input_by' => $item->inputBy?->name,
                    'created_at' => $item->created_at,
                    'formatted_date' => Carbon::parse($item->tanggal_pengeluaran)->format('d M Y'),
                    'formatted_jumlah' => 'Rp ' . number_format($item->jumlah, 0, ',', '.'),
                    'kategori_color' => $this->getCategoryColor($item->kategori),
                    'priority_color' => $this->getPriorityColor($item->priority ?? 'medium'),
                    'status_color' => $this->getStatusColor($item->status ?? 'pending'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'meta' => [
                    'current_page' => $pengeluaran->currentPage(),
                    'last_page' => $pengeluaran->lastPage(),
                    'per_page' => $pengeluaran->perPage(),
                    'total' => $pengeluaran->total(),
                    'from' => $pengeluaran->firstItem(),
                    'to' => $pengeluaran->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Pengeluaran getData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data pengeluaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show smart create form
     */
    public function create(): View
    {
        // Get expense categories and templates
        $categories = $this->getExpenseCategories();
        $templates = $this->getExpenseTemplates();
        $budgetLimits = $this->getBudgetLimits();
        
        return view('petugas.enhanced.pengeluaran.create', compact(
            'categories', 'templates', 'budgetLimits'
        ));
    }

    /**
     * Store new pengeluaran with budget validation
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tanggal_pengeluaran' => 'required|date',
                'nama_pengeluaran' => 'required|string|max:255',
                'kategori' => 'required|string|max:100',
                'jumlah' => 'required|numeric|min:0',
                'keterangan' => 'nullable|string|max:1000',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'status' => 'nullable|in:pending,approved,rejected,paid',
                'budget_check' => 'nullable|boolean',
            ]);

            // Smart budget validation
            if ($validated['budget_check'] ?? true) {
                $budgetValidation = $this->validateBudget($validated['kategori'], $validated['jumlah'], $validated['tanggal_pengeluaran']);
                if (!$budgetValidation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Budget Warning: ' . $budgetValidation['message'],
                        'budget_info' => $budgetValidation,
                        'force_save' => true
                    ], 422);
                }
            }

            $validated['input_by'] = auth()->id();
            $validated['priority'] = $validated['priority'] ?? 'medium';
            $validated['status'] = $validated['status'] ?? 'pending';

            $pengeluaran = Pengeluaran::create($validated);

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil disimpan',
                'data' => $pengeluaran->load('inputBy')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Enhanced Pengeluaran store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pengeluaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific pengeluaran detail
     */
    public function show($id): View
    {
        $pengeluaran = Pengeluaran::with(['inputBy'])->findOrFail($id);

        // Get related expenses for context
        $relatedExpenses = $this->getRelatedExpenses($pengeluaran);
        
        // Get budget context
        $budgetContext = $this->getBudgetContext($pengeluaran);

        return view('petugas.enhanced.pengeluaran.show', compact('pengeluaran', 'relatedExpenses', 'budgetContext'));
    }

    /**
     * Show edit form
     */
    public function edit($id): View
    {
        $pengeluaran = Pengeluaran::findOrFail($id);
        
        $categories = $this->getExpenseCategories();
        $templates = $this->getExpenseTemplates();
        $budgetLimits = $this->getBudgetLimits();
        
        return view('petugas.enhanced.pengeluaran.edit', compact(
            'pengeluaran', 'categories', 'templates', 'budgetLimits'
        ));
    }

    /**
     * Update existing pengeluaran
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $pengeluaran = Pengeluaran::findOrFail($id);

            $validated = $request->validate([
                'tanggal_pengeluaran' => 'required|date',
                'nama_pengeluaran' => 'required|string|max:255',
                'kategori' => 'required|string|max:100',
                'jumlah' => 'required|numeric|min:0',
                'keterangan' => 'nullable|string|max:1000',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'status' => 'nullable|in:pending,approved,rejected,paid',
                'budget_check' => 'nullable|boolean',
            ]);

            // Smart budget validation for amount changes
            if (($validated['budget_check'] ?? true) && $validated['jumlah'] != $pengeluaran->jumlah) {
                $budgetValidation = $this->validateBudget($validated['kategori'], $validated['jumlah'], $validated['tanggal_pengeluaran'], $id);
                if (!$budgetValidation['valid']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Budget Warning: ' . $budgetValidation['message'],
                        'budget_info' => $budgetValidation,
                        'force_save' => true
                    ], 422);
                }
            }

            $pengeluaran->update($validated);

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil diperbarui',
                'data' => $pengeluaran->load('inputBy')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Enhanced Pengeluaran update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pengeluaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete pengeluaran
     */
    public function destroy($id): JsonResponse
    {
        try {
            $pengeluaran = Pengeluaran::findOrFail($id);
            $pengeluaran->delete();

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Pengeluaran destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengeluaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:pengeluarans,id',
                'status' => 'required|in:pending,approved,rejected,paid'
            ]);

            $updated = Pengeluaran::whereIn('id', $validated['ids'])
                                 ->update(['status' => $validated['status']]);

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => "Status {$updated} pengeluaran berhasil diperbarui"
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Pengeluaran bulk update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get budget analysis
     */
    public function getBudgetAnalysisData(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // week, month, quarter, year
            $analysis = $this->generateBudgetAnalysis($period);

            return response()->json([
                'success' => true,
                'data' => $analysis
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Pengeluaran budget analysis error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat analisis budget: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export pengeluaran data
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
            Log::error('Enhanced Pengeluaran export error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get smart suggestions
     */
    public function getSuggestions(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'nama');
            $query = $request->get('q', '');
            $kategori = $request->get('kategori', '');

            $suggestions = [];

            switch ($type) {
                case 'nama':
                    $suggestions = $this->getExpenseNameSuggestions($query, $kategori);
                    break;
                case 'kategori':
                    $suggestions = $this->getExpenseCategories($query);
                    break;
                case 'template':
                    $suggestions = $this->getExpenseTemplates($query);
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $suggestions
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Pengeluaran suggestions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat suggestions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expense statistics
     */
    private function getExpenseStats(): array
    {
        return Cache::remember('enhanced_pengeluaran_stats', 300, function () {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $lastMonth = Carbon::now()->subMonth()->startOfMonth();
            $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

            $todayExpense = Pengeluaran::whereDate('tanggal_pengeluaran', $today)->sum('jumlah');
            $monthExpense = Pengeluaran::where('tanggal_pengeluaran', '>=', $thisMonth)->sum('jumlah');
            $lastMonthExpense = Pengeluaran::whereBetween('tanggal_pengeluaran', [$lastMonth, $lastMonthEnd])->sum('jumlah');

            $growth = $lastMonthExpense > 0 ? (($monthExpense - $lastMonthExpense) / $lastMonthExpense) * 100 : 0;

            return [
                'today' => $todayExpense,
                'month' => $monthExpense,
                'last_month' => $lastMonthExpense,
                'growth_percentage' => round($growth, 2),
                'total_entries_today' => Pengeluaran::whereDate('tanggal_pengeluaran', $today)->count(),
                'total_entries_month' => Pengeluaran::where('tanggal_pengeluaran', '>=', $thisMonth)->count(),
                'avg_transaction' => Pengeluaran::where('tanggal_pengeluaran', '>=', $thisMonth)->avg('jumlah') ?? 0,
                'highest_transaction' => Pengeluaran::where('tanggal_pengeluaran', '>=', $thisMonth)->max('jumlah') ?? 0,
                'pending_count' => Pengeluaran::where('status', 'pending')->count(),
                'approved_count' => Pengeluaran::where('status', 'approved')->count(),
                'categories' => $this->getExpenseByCategory(),
            ];
        });
    }

    /**
     * Get recent pengeluaran entries
     */
    private function getRecentPengeluaran(int $limit = 10): array
    {
        return Pengeluaran::with(['inputBy:id,name'])
                         ->orderByDesc('created_at')
                         ->limit($limit)
                         ->get()
                         ->map(function ($item) {
                             return [
                                 'id' => $item->id,
                                 'nama' => $item->nama_pengeluaran,
                                 'kategori' => $item->kategori,
                                 'jumlah' => $item->jumlah,
                                 'tanggal' => $item->tanggal_pengeluaran,
                                 'status' => $item->status ?? 'pending',
                                 'priority' => $item->priority ?? 'medium',
                                 'created_at' => $item->created_at,
                             ];
                         })
                         ->toArray();
    }

    /**
     * Get expense trends for charts
     */
    private function getExpenseTrends(): array
    {
        return Cache::remember('expense_trends_7days', 900, function () {
            $trends = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $expense = Pengeluaran::whereDate('tanggal_pengeluaran', $date)->sum('jumlah');
                $trends[] = [
                    'date' => $date->format('Y-m-d'),
                    'formatted_date' => $date->format('d M'),
                    'expense' => $expense,
                    'count' => Pengeluaran::whereDate('tanggal_pengeluaran', $date)->count(),
                ];
            }
            return $trends;
        });
    }

    /**
     * Get expense by category
     */
    private function getExpenseByCategory(): array
    {
        $thisMonth = Carbon::now()->startOfMonth();
        
        return Pengeluaran::where('tanggal_pengeluaran', '>=', $thisMonth)
                         ->groupBy('kategori')
                         ->selectRaw('kategori, sum(jumlah) as total')
                         ->orderByDesc('total')
                         ->limit(10)
                         ->pluck('total', 'kategori')
                         ->toArray();
    }

    /**
     * Get budget analysis
     */
    private function getBudgetAnalysis(): array
    {
        // This would integrate with a budget system
        // For now, return mock data
        return [
            'monthly_budget' => 50000000,
            'spent_this_month' => $this->getExpenseStats()['month'],
            'remaining_budget' => 50000000 - $this->getExpenseStats()['month'],
            'budget_utilization' => ($this->getExpenseStats()['month'] / 50000000) * 100,
            'categories_over_budget' => [],
            'forecast_end_month' => 0,
        ];
    }

    /**
     * Get expense categories
     */
    private function getExpenseCategories(string $query = ''): array
    {
        $baseCategories = [
            'Operasional',
            'Peralatan Medis',
            'Obat-obatan',
            'Konsumsi',
            'Utilitas',
            'Pemeliharaan',
            'Transportasi',
            'Komunikasi',
            'Pelatihan',
            'Asuransi',
            'Pajak',
            'Legal',
            'Marketing',
            'ATK',
            'Lainnya'
        ];

        // Add recent categories from database
        $recentCategories = Pengeluaran::select('kategori')
                                     ->where('created_at', '>=', Carbon::now()->subDays(30))
                                     ->distinct()
                                     ->pluck('kategori')
                                     ->toArray();

        $allCategories = array_unique(array_merge($baseCategories, $recentCategories));

        if ($query) {
            $allCategories = array_filter($allCategories, function ($category) use ($query) {
                return stripos($category, $query) !== false;
            });
        }

        return array_values($allCategories);
    }

    /**
     * Get expense templates
     */
    private function getExpenseTemplates(string $query = ''): array
    {
        $templates = [
            [
                'name' => 'Listrik Bulanan',
                'kategori' => 'Utilitas',
                'jumlah' => 2500000,
                'keterangan' => 'Pembayaran listrik bulanan klinik'
            ],
            [
                'name' => 'Air PDAM',
                'kategori' => 'Utilitas',
                'jumlah' => 500000,
                'keterangan' => 'Pembayaran air PDAM'
            ],
            [
                'name' => 'Internet & Telepon',
                'kategori' => 'Komunikasi',
                'jumlah' => 800000,
                'keterangan' => 'Biaya internet dan telepon'
            ],
            [
                'name' => 'Alat Tulis Kantor',
                'kategori' => 'ATK',
                'jumlah' => 300000,
                'keterangan' => 'Pembelian ATK bulanan'
            ],
            [
                'name' => 'Obat Generik',
                'kategori' => 'Obat-obatan',
                'jumlah' => 5000000,
                'keterangan' => 'Stock obat generik'
            ],
        ];

        if ($query) {
            $templates = array_filter($templates, function ($template) use ($query) {
                return stripos($template['name'], $query) !== false || stripos($template['kategori'], $query) !== false;
            });
        }

        return array_values($templates);
    }

    /**
     * Get expense name suggestions
     */
    private function getExpenseNameSuggestions(string $query = '', string $kategori = ''): array
    {
        $queryBuilder = Pengeluaran::select('nama_pengeluaran')
                                  ->distinct();

        if ($kategori) {
            $queryBuilder->where('kategori', $kategori);
        }

        if ($query) {
            $queryBuilder->where('nama_pengeluaran', 'like', "%{$query}%");
        }

        return $queryBuilder->limit(10)
                           ->pluck('nama_pengeluaran')
                           ->toArray();
    }

    /**
     * Get budget limits
     */
    private function getBudgetLimits(): array
    {
        // This would come from a budget configuration
        return [
            'Operasional' => 10000000,
            'Peralatan Medis' => 15000000,
            'Obat-obatan' => 20000000,
            'Utilitas' => 5000000,
            'default' => 3000000
        ];
    }

    /**
     * Validate budget limits
     */
    private function validateBudget(string $kategori, float $jumlah, string $tanggal, int $excludeId = null): array
    {
        $month = Carbon::parse($tanggal)->startOfMonth();
        $budgetLimits = $this->getBudgetLimits();
        $categoryLimit = $budgetLimits[$kategori] ?? $budgetLimits['default'];
        
        $currentSpent = Pengeluaran::where('kategori', $kategori)
                                  ->where('tanggal_pengeluaran', '>=', $month)
                                  ->when($excludeId, function ($q) use ($excludeId) {
                                      $q->where('id', '!=', $excludeId);
                                  })
                                  ->sum('jumlah');
        
        $totalAfterExpense = $currentSpent + $jumlah;
        $utilizationPercentage = ($totalAfterExpense / $categoryLimit) * 100;
        
        if ($utilizationPercentage > 100) {
            return [
                'valid' => false,
                'message' => "Melebihi budget kategori {$kategori}. Limit: Rp " . number_format($categoryLimit, 0, ',', '.'),
                'current_spent' => $currentSpent,
                'budget_limit' => $categoryLimit,
                'utilization' => $utilizationPercentage,
                'over_budget' => $totalAfterExpense - $categoryLimit
            ];
        } elseif ($utilizationPercentage > 80) {
            return [
                'valid' => true,
                'message' => "Mendekati limit budget kategori {$kategori} ({$utilizationPercentage}%)",
                'current_spent' => $currentSpent,
                'budget_limit' => $categoryLimit,
                'utilization' => $utilizationPercentage,
                'warning' => true
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Budget tersedia',
            'current_spent' => $currentSpent,
            'budget_limit' => $categoryLimit,
            'utilization' => $utilizationPercentage
        ];
    }

    /**
     * Get related expenses
     */
    private function getRelatedExpenses($pengeluaran): array
    {
        return Pengeluaran::where('kategori', $pengeluaran->kategori)
                         ->where('id', '!=', $pengeluaran->id)
                         ->orderByDesc('tanggal_pengeluaran')
                         ->limit(5)
                         ->get()
                         ->toArray();
    }

    /**
     * Get budget context for expense
     */
    private function getBudgetContext($pengeluaran): array
    {
        return $this->validateBudget($pengeluaran->kategori, 0, $pengeluaran->tanggal_pengeluaran);
    }

    /**
     * Get category color
     */
    private function getCategoryColor(string $kategori): string
    {
        $colors = [
            'Operasional' => 'blue',
            'Peralatan Medis' => 'green',
            'Obat-obatan' => 'purple',
            'Utilitas' => 'yellow',
            'Pemeliharaan' => 'red',
            'default' => 'gray'
        ];
        
        return $colors[$kategori] ?? $colors['default'];
    }

    /**
     * Get priority color
     */
    private function getPriorityColor(string $priority): string
    {
        $colors = [
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'urgent' => 'red'
        ];
        
        return $colors[$priority] ?? $colors['medium'];
    }

    /**
     * Get status color
     */
    private function getStatusColor(string $status): string
    {
        $colors = [
            'pending' => 'yellow',
            'approved' => 'blue',
            'rejected' => 'red',
            'paid' => 'green'
        ];
        
        return $colors[$status] ?? $colors['pending'];
    }

    /**
     * Generate budget analysis
     */
    private function generateBudgetAnalysis(string $period): array
    {
        // This would generate comprehensive budget analysis
        // For now, return basic structure
        return [
            'period' => $period,
            'total_expenses' => 0,
            'budget_utilization' => 0,
            'categories_breakdown' => [],
            'over_budget_categories' => [],
            'forecasts' => [],
        ];
    }
}
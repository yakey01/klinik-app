<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Pengeluaran;
use App\Http\Resources\V1\PengeluaranResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PengeluaranController extends BaseApiController
{
    /**
     * Display a listing of expense entries
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'bendahara']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Get pagination parameters
            $pagination = $this->getPaginationParams($request);

            // Build query with relationships
            $query = Pengeluaran::with(['inputBy:id,name']);

            // Apply filters
            $query = $this->applyPengeluaranFilters($query, $request);

            // Apply sorting
            $allowedSorts = ['tanggal_pengeluaran', 'jumlah', 'created_at', 'nama_pengeluaran', 'kategori', 'priority', 'status'];
            $query = $this->applySorting($query, $request, $allowedSorts, 'tanggal_pengeluaran', 'desc');

            // Get paginated results
            $pengeluaran = $query->paginate($pagination['per_page']);

            // Transform data based on view type
            $viewType = $request->get('view', 'default'); // default, minimal, mobile, dashboard
            $transformedData = $pengeluaran->through(function ($item) use ($viewType, $request) {
                $resource = new PengeluaranResource($item);
                return match ($viewType) {
                    'minimal' => $resource->toArrayMinimal($request),
                    'mobile' => $resource->toArrayMobile($request),
                    'dashboard' => $resource->toArrayDashboard($request),
                    default => $resource->toArray($request),
                };
            });

            $this->logApiActivity('pengeluaran.index', ['count' => $pengeluaran->total()]);

            return $this->paginatedResponse($transformedData, 'Daftar pengeluaran berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching pengeluaran');
        }
    }

    /**
     * Store a newly created expense entry
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Validate request
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
                $budgetValidation = $this->validateBudget(
                    $validated['kategori'], 
                    $validated['jumlah'], 
                    $validated['tanggal_pengeluaran']
                );
                
                if (!$budgetValidation['valid']) {
                    return $this->errorResponse(
                        'Budget Warning: ' . $budgetValidation['message'],
                        422,
                        ['budget_info' => $budgetValidation]
                    );
                }
            }

            DB::beginTransaction();

            $validated['input_by'] = $this->getAuthUser()->id;
            $validated['priority'] = $validated['priority'] ?? 'medium';
            $validated['status'] = $validated['status'] ?? 'pending';

            // Create pengeluaran
            $pengeluaran = Pengeluaran::create($validated);

            // Load relationships
            $pengeluaran->load(['inputBy:id,name']);

            DB::commit();

            $this->logApiActivity('pengeluaran.store', ['pengeluaran_id' => $pengeluaran->id]);

            return $this->successResponse(
                new PengeluaranResource($pengeluaran),
                'Pengeluaran berhasil ditambahkan',
                201
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error creating pengeluaran');
        }
    }

    /**
     * Display the specified expense entry
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'bendahara']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find pengeluaran with relationships
            $pengeluaran = Pengeluaran::with(['inputBy:id,name'])->findOrFail($id);

            // Include budget context if requested
            if ($request->has('include_budget')) {
                $budgetContext = $this->getBudgetContext($pengeluaran);
                $pengeluaran->budget_context = $budgetContext;
            }

            $this->logApiActivity('pengeluaran.show', ['pengeluaran_id' => $id]);

            return $this->successResponse(
                new PengeluaranResource($pengeluaran),
                'Detail pengeluaran berhasil dimuat'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Pengeluaran tidak ditemukan', 404);
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching pengeluaran');
        }
    }

    /**
     * Update the specified expense entry
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find pengeluaran
            $pengeluaran = Pengeluaran::findOrFail($id);

            // Validate request
            $validated = $request->validate([
                'tanggal_pengeluaran' => 'sometimes|required|date',
                'nama_pengeluaran' => 'sometimes|required|string|max:255',
                'kategori' => 'sometimes|required|string|max:100',
                'jumlah' => 'sometimes|required|numeric|min:0',
                'keterangan' => 'nullable|string|max:1000',
                'priority' => 'nullable|in:low,medium,high,urgent',
                'status' => 'nullable|in:pending,approved,rejected,paid',
                'budget_check' => 'nullable|boolean',
            ]);

            // Smart budget validation for amount changes
            if (($validated['budget_check'] ?? true) && isset($validated['jumlah']) && $validated['jumlah'] != $pengeluaran->jumlah) {
                $kategori = $validated['kategori'] ?? $pengeluaran->kategori;
                $tanggal = $validated['tanggal_pengeluaran'] ?? $pengeluaran->tanggal_pengeluaran;
                
                $budgetValidation = $this->validateBudget($kategori, $validated['jumlah'], $tanggal, $id);
                if (!$budgetValidation['valid']) {
                    return $this->errorResponse(
                        'Budget Warning: ' . $budgetValidation['message'],
                        422,
                        ['budget_info' => $budgetValidation]
                    );
                }
            }

            DB::beginTransaction();

            // Update pengeluaran
            $pengeluaran->update($validated);

            // Load relationships
            $pengeluaran->load(['inputBy:id,name']);

            DB::commit();

            $this->logApiActivity('pengeluaran.update', ['pengeluaran_id' => $id]);

            return $this->successResponse(
                new PengeluaranResource($pengeluaran->fresh()),
                'Pengeluaran berhasil diperbarui'
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Pengeluaran tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error updating pengeluaran');
        }
    }

    /**
     * Remove the specified expense entry
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Validate permissions (only admin can delete)
            $permissionCheck = $this->validateApiPermissions(['admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find pengeluaran
            $pengeluaran = Pengeluaran::findOrFail($id);

            DB::beginTransaction();

            $pengeluaran->delete();

            DB::commit();

            $this->logApiActivity('pengeluaran.destroy', ['pengeluaran_id' => $id]);

            return $this->successResponse(null, 'Pengeluaran berhasil dihapus');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Pengeluaran tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error deleting pengeluaran');
        }
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin', 'bendahara']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Validate request
            $validated = $request->validate([
                'ids' => 'required|array|min:1',
                'ids.*' => 'exists:pengeluarans,id',
                'status' => 'required|in:pending,approved,rejected,paid',
            ]);

            DB::beginTransaction();

            $updated = Pengeluaran::whereIn('id', $validated['ids'])
                                 ->update([
                                     'status' => $validated['status'],
                                     'updated_at' => now(),
                                 ]);

            DB::commit();

            $this->logApiActivity('pengeluaran.bulkUpdateStatus', [
                'updated_count' => $updated,
                'new_status' => $validated['status']
            ]);

            return $this->successResponse([
                'updated_count' => $updated,
            ], "Status {$updated} pengeluaran berhasil diperbarui");

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error bulk updating status');
        }
    }

    /**
     * Get budget analysis
     */
    public function budgetAnalysis(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'bendahara']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $period = $request->get('period', 'month'); // week, month, quarter, year
            $analysis = $this->generateBudgetAnalysis($period);

            return $this->successResponse($analysis, 'Analisis budget berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching budget analysis');
        }
    }

    /**
     * Get expense suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $type = $request->get('type', 'category'); // category, name, template
            $query = $request->get('q', '');
            $kategori = $request->get('kategori', '');

            $suggestions = match ($type) {
                'category' => $this->getCategorySuggestions($query),
                'name' => $this->getNameSuggestions($query, $kategori),
                'template' => $this->getTemplateSuggestions($query),
                default => [],
            };

            return $this->successResponse($suggestions, 'Suggestions berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching suggestions');
        }
    }

    /**
     * Apply pengeluaran-specific filters
     */
    private function applyPengeluaranFilters($query, Request $request)
    {
        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pengeluaran', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        // Date range filters
        if ($dateFrom = $request->get('date_from')) {
            $query->where('tanggal_pengeluaran', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->where('tanggal_pengeluaran', '<=', $dateTo);
        }

        // Amount range filters
        if ($minAmount = $request->get('min_amount')) {
            $query->where('jumlah', '>=', $minAmount);
        }

        if ($maxAmount = $request->get('max_amount')) {
            $query->where('jumlah', '<=', $maxAmount);
        }

        // Category filter
        if ($category = $request->get('category')) {
            $query->where('kategori', $category);
        }

        // Priority filter
        if ($priority = $request->get('priority')) {
            $query->where('priority', $priority);
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return $query;
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
            'Pemeliharaan' => 3000000,
            'Transportasi' => 2000000,
            'Komunikasi' => 1000000,
            'default' => 3000000
        ];
    }

    /**
     * Get budget context for expense
     */
    private function getBudgetContext($pengeluaran): array
    {
        return $this->validateBudget($pengeluaran->kategori, 0, $pengeluaran->tanggal_pengeluaran);
    }

    /**
     * Generate budget analysis
     */
    private function generateBudgetAnalysis(string $period): array
    {
        $now = now();
        
        switch ($period) {
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                break;
            case 'quarter':
                $startDate = $now->copy()->startOfQuarter();
                $endDate = $now->copy()->endOfQuarter();
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;
            default: // month
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
        }

        $budgetLimits = $this->getBudgetLimits();
        $totalBudget = array_sum($budgetLimits) - $budgetLimits['default']; // Exclude default

        // Total spent in period
        $totalSpent = Pengeluaran::whereBetween('tanggal_pengeluaran', [$startDate, $endDate])
                                ->sum('jumlah');

        // Spending by category
        $spendingByCategory = Pengeluaran::whereBetween('tanggal_pengeluaran', [$startDate, $endDate])
                                        ->selectRaw('kategori, SUM(jumlah) as total, COUNT(*) as count')
                                        ->groupBy('kategori')
                                        ->get()
                                        ->keyBy('kategori')
                                        ->toArray();

        // Calculate utilization for each category
        $categoryAnalysis = [];
        foreach ($budgetLimits as $category => $limit) {
            if ($category === 'default') continue;
            
            $spent = $spendingByCategory[$category]['total'] ?? 0;
            $utilization = $limit > 0 ? ($spent / $limit) * 100 : 0;
            
            $categoryAnalysis[$category] = [
                'budget_limit' => $limit,
                'spent' => $spent,
                'remaining' => max(0, $limit - $spent),
                'utilization_percentage' => $utilization,
                'transaction_count' => $spendingByCategory[$category]['count'] ?? 0,
                'is_over_budget' => $utilization > 100,
                'is_warning' => $utilization > 80 && $utilization <= 100,
            ];
        }

        // Top expense categories
        $topCategories = collect($spendingByCategory)
                        ->sortByDesc('total')
                        ->take(5)
                        ->values()
                        ->toArray();

        return [
            'period' => $period,
            'period_dates' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
            'summary' => [
                'total_budget' => $totalBudget,
                'total_spent' => $totalSpent,
                'remaining_budget' => max(0, $totalBudget - $totalSpent),
                'budget_utilization' => $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0,
            ],
            'category_analysis' => $categoryAnalysis,
            'top_categories' => $topCategories,
            'over_budget_categories' => array_filter($categoryAnalysis, function ($analysis) {
                return $analysis['is_over_budget'];
            }),
            'warning_categories' => array_filter($categoryAnalysis, function ($analysis) {
                return $analysis['is_warning'];
            }),
        ];
    }

    /**
     * Get category suggestions
     */
    private function getCategorySuggestions(string $query = ''): array
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
                                     ->where('created_at', '>=', now()->subDays(30))
                                     ->when($query, function ($q) use ($query) {
                                         $q->where('kategori', 'like', "%{$query}%");
                                     })
                                     ->distinct()
                                     ->limit(10)
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
     * Get name suggestions
     */
    private function getNameSuggestions(string $query = '', string $kategori = ''): array
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
     * Get template suggestions
     */
    private function getTemplateSuggestions(string $query = ''): array
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
        ];

        if ($query) {
            $templates = array_filter($templates, function ($template) use ($query) {
                return stripos($template['name'], $query) !== false || 
                       stripos($template['kategori'], $query) !== false;
            });
        }

        return array_values($templates);
    }
}
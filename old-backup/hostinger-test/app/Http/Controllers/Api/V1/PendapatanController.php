<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Pendapatan;
use App\Models\Tindakan;
use App\Http\Resources\V1\PendapatanResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PendapatanController extends BaseApiController
{
    /**
     * Display a listing of revenue entries
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
            $query = Pendapatan::with([
                'tindakan.pasien:id,nama_pasien,nomor_pasien',
                'tindakan.jenisTindakan:id,nama_tindakan',
                'inputBy:id,name'
            ]);

            // Apply filters
            $query = $this->applyPendapatanFilters($query, $request);

            // Apply sorting
            $allowedSorts = ['tanggal_pendapatan', 'jumlah', 'created_at', 'sumber_pendapatan'];
            $query = $this->applySorting($query, $request, $allowedSorts, 'tanggal_pendapatan', 'desc');

            // Get paginated results
            $pendapatan = $query->paginate($pagination['per_page']);

            // Transform data based on view type
            $viewType = $request->get('view', 'default'); // default, minimal, mobile, dashboard
            $transformedData = $pendapatan->through(function ($item) use ($viewType, $request) {
                $resource = new PendapatanResource($item);
                return match ($viewType) {
                    'minimal' => $resource->toArrayMinimal($request),
                    'mobile' => $resource->toArrayMobile($request),
                    'dashboard' => $resource->toArrayDashboard($request),
                    default => $resource->toArray($request),
                };
            });

            $this->logApiActivity('pendapatan.index', ['count' => $pendapatan->total()]);

            return $this->paginatedResponse($transformedData, 'Daftar pendapatan berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching pendapatan');
        }
    }

    /**
     * Store a newly created revenue entry
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
                'kode_pendapatan' => 'nullable|string|max:50',
                'nama_pendapatan' => 'required|string|max:255',
                'sumber_pendapatan' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'nominal' => 'required|numeric|min:0',
                'kategori' => 'required|string|max:50',
                'keterangan' => 'nullable|string|max:1000',
                'tindakan_id' => 'nullable|exists:tindakan,id',
                'is_aktif' => 'boolean',
            ]);

            // Set default values if not provided
            $validated['is_aktif'] = $validated['is_aktif'] ?? true;
            $validated['status_validasi'] = 'pending';
            
            // Generate kode_pendapatan if not provided
            if (empty($validated['kode_pendapatan'])) {
                $lastPendapatan = Pendapatan::latest()->first();
                $lastNumber = $lastPendapatan ? (int) substr($lastPendapatan->kode_pendapatan, 4) : 0;
                $validated['kode_pendapatan'] = 'PND-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }

            // Check for duplicate tindakan if provided
            if (!empty($validated['tindakan_id'])) {
                $existingPendapatan = Pendapatan::where('tindakan_id', $validated['tindakan_id'])->first();
                if ($existingPendapatan) {
                    return $this->errorResponse(
                        'Pendapatan untuk tindakan ini sudah ada',
                        422,
                        ['tindakan_id' => ['Tindakan sudah memiliki pendapatan']]
                    );
                }
            }

            DB::beginTransaction();

            $validated['input_by'] = $this->getAuthUser()->id;

            // Create pendapatan
            $pendapatan = Pendapatan::create($validated);

            // Load relationships
            $pendapatan->load([
                'tindakan.pasien:id,nama_pasien,nomor_pasien',
                'tindakan.jenisTindakan:id,nama_tindakan',
                'inputBy:id,name'
            ]);

            DB::commit();

            $this->logApiActivity('pendapatan.store', ['pendapatan_id' => $pendapatan->id]);

            return $this->successResponse(
                new PendapatanResource($pendapatan),
                'Pendapatan berhasil ditambahkan',
                201
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error creating pendapatan');
        }
    }

    /**
     * Display the specified revenue entry
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'bendahara']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find pendapatan with relationships
            $pendapatan = Pendapatan::with([
                'tindakan.pasien:id,nama_pasien,nomor_pasien,tanggal_lahir,jenis_kelamin',
                'tindakan.jenisTindakan:id,nama_tindakan,kategori',
                'tindakan.dokter:id,nama_dokter,jabatan',
                'inputBy:id,name'
            ])->findOrFail($id);

            $this->logApiActivity('pendapatan.show', ['pendapatan_id' => $id]);

            return $this->successResponse(
                new PendapatanResource($pendapatan),
                'Detail pendapatan berhasil dimuat'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Pendapatan tidak ditemukan', 404);
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching pendapatan');
        }
    }

    /**
     * Update the specified revenue entry
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find pendapatan
            $pendapatan = Pendapatan::findOrFail($id);

            // Validate request
            $validated = $request->validate([
                'tanggal_pendapatan' => 'sometimes|required|date',
                'sumber_pendapatan' => 'sometimes|required|string|max:255',
                'jumlah' => 'sometimes|required|numeric|min:0',
                'keterangan' => 'nullable|string|max:1000',
                'tindakan_id' => 'nullable|exists:tindakans,id',
            ]);

            // Check for duplicate tindakan if changing
            if (isset($validated['tindakan_id']) && $validated['tindakan_id'] != $pendapatan->tindakan_id) {
                if (!empty($validated['tindakan_id'])) {
                    $existingPendapatan = Pendapatan::where('tindakan_id', $validated['tindakan_id'])
                                                  ->where('id', '!=', $id)
                                                  ->first();
                    if ($existingPendapatan) {
                        return $this->errorResponse(
                            'Tindakan ini sudah memiliki pendapatan lain',
                            422,
                            ['tindakan_id' => ['Tindakan sudah memiliki pendapatan']]
                        );
                    }
                }
            }

            DB::beginTransaction();

            // Update pendapatan
            $pendapatan->update($validated);

            // Load relationships
            $pendapatan->load([
                'tindakan.pasien:id,nama_pasien,nomor_pasien',
                'tindakan.jenisTindakan:id,nama_tindakan',
                'inputBy:id,name'
            ]);

            DB::commit();

            $this->logApiActivity('pendapatan.update', ['pendapatan_id' => $id]);

            return $this->successResponse(
                new PendapatanResource($pendapatan->fresh()),
                'Pendapatan berhasil diperbarui'
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Pendapatan tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error updating pendapatan');
        }
    }

    /**
     * Remove the specified revenue entry
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Validate permissions (only admin can delete)
            $permissionCheck = $this->validateApiPermissions(['admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find pendapatan
            $pendapatan = Pendapatan::findOrFail($id);

            DB::beginTransaction();

            $pendapatan->delete();

            DB::commit();

            $this->logApiActivity('pendapatan.destroy', ['pendapatan_id' => $id]);

            return $this->successResponse(null, 'Pendapatan berhasil dihapus');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Pendapatan tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error deleting pendapatan');
        }
    }

    /**
     * Bulk create revenue from approved tindakan
     */
    public function bulkCreateFromTindakan(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Validate request
            $validated = $request->validate([
                'tindakan_ids' => 'required|array|min:1',
                'tindakan_ids.*' => 'exists:tindakans,id',
            ]);

            DB::beginTransaction();

            $created = 0;
            $skipped = 0;
            $errors = [];

            foreach ($validated['tindakan_ids'] as $tindakanId) {
                try {
                    // Check if pendapatan already exists
                    if (Pendapatan::where('tindakan_id', $tindakanId)->exists()) {
                        $skipped++;
                        continue;
                    }

                    // Get tindakan with relationships
                    $tindakan = Tindakan::with(['pasien', 'jenisTindakan'])
                                       ->where('id', $tindakanId)
                                       ->where('status_validasi', 'approved')
                                       ->first();

                    if (!$tindakan) {
                        $skipped++;
                        continue;
                    }

                    // Generate kode_pendapatan
                    $lastPendapatan = Pendapatan::latest()->first();
                    $lastNumber = $lastPendapatan ? (int) substr($lastPendapatan->kode_pendapatan, 4) : 0;
                    $kodePendapatan = 'PND-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

                    // Create pendapatan
                    Pendapatan::create([
                        'kode_pendapatan' => $kodePendapatan,
                        'nama_pendapatan' => $tindakan->jenisTindakan->nama_tindakan ?? 'Tindakan Medis',
                        'sumber_pendapatan' => 'Pelayanan Medis',
                        'tanggal' => $tindakan->tanggal_tindakan,
                        'nominal' => $tindakan->tarif,
                        'kategori' => 'tindakan_medis',
                        'keterangan' => "Auto-generated dari tindakan #{$tindakan->id} untuk pasien {$tindakan->pasien->nama_pasien}",
                        'tindakan_id' => $tindakan->id,
                        'input_by' => $this->getAuthUser()->id,
                        'is_aktif' => true,
                        'status_validasi' => 'pending',
                    ]);

                    $created++;

                } catch (\Exception $e) {
                    $errors[] = "Tindakan #{$tindakanId}: " . $e->getMessage();
                }
            }

            DB::commit();

            $this->logApiActivity('pendapatan.bulkCreateFromTindakan', [
                'created' => $created,
                'skipped' => $skipped,
                'errors_count' => count($errors)
            ]);

            return $this->successResponse([
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
            ], "Berhasil membuat {$created} pendapatan, {$skipped} dilewati");

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error bulk creating pendapatan');
        }
    }

    /**
     * Get revenue analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'bendahara']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $period = $request->get('period', 'month'); // day, week, month, quarter, year
            $analytics = $this->generateRevenueAnalytics($period);

            return $this->successResponse($analytics, 'Analytics pendapatan berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching revenue analytics');
        }
    }

    /**
     * Get revenue suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $type = $request->get('type', 'source'); // source, template, unpaid_tindakan
            $query = $request->get('q', '');

            $suggestions = match ($type) {
                'source' => $this->getSourceSuggestions($query),
                'template' => $this->getTemplateSuggestions($query),
                'unpaid_tindakan' => $this->getUnpaidTindakanSuggestions($query),
                default => [],
            };

            return $this->successResponse($suggestions, 'Suggestions berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching suggestions');
        }
    }

    /**
     * Apply pendapatan-specific filters
     */
    private function applyPendapatanFilters($query, Request $request)
    {
        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('sumber_pendapatan', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('tindakan.pasien', function ($q) use ($search) {
                      $q->where('nama_pasien', 'like', "%{$search}%")
                        ->orWhere('nomor_pasien', 'like', "%{$search}%");
                  });
            });
        }

        // Date range filters
        if ($dateFrom = $request->get('date_from')) {
            $query->where('tanggal_pendapatan', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->where('tanggal_pendapatan', '<=', $dateTo);
        }

        // Amount range filters
        if ($minAmount = $request->get('min_amount')) {
            $query->where('jumlah', '>=', $minAmount);
        }

        if ($maxAmount = $request->get('max_amount')) {
            $query->where('jumlah', '<=', $maxAmount);
        }

        // Source filter
        if ($source = $request->get('source')) {
            $query->where('sumber_pendapatan', 'like', "%{$source}%");
        }

        // Category filter (categorized source)
        if ($category = $request->get('category')) {
            $categoryKeywords = match ($category) {
                'consultation' => ['konsultasi', 'konsul'],
                'medication' => ['obat', 'farmasi'],
                'equipment' => ['alat', 'peralatan'],
                'procedure' => ['tindakan', 'medis', 'operasi'],
                default => [],
            };

            if (!empty($categoryKeywords)) {
                $query->where(function ($q) use ($categoryKeywords) {
                    foreach ($categoryKeywords as $keyword) {
                        $q->orWhere('sumber_pendapatan', 'like', "%{$keyword}%");
                    }
                });
            }
        }

        // Patient filter
        if ($patientId = $request->get('patient_id')) {
            $query->whereHas('tindakan', function ($q) use ($patientId) {
                $q->where('pasien_id', $patientId);
            });
        }

        return $query;
    }

    /**
     * Generate revenue analytics
     */
    private function generateRevenueAnalytics(string $period): array
    {
        $now = now();
        
        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $groupBy = 'hour';
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                $groupBy = 'day';
                break;
            case 'quarter':
                $startDate = $now->copy()->startOfQuarter();
                $endDate = $now->copy()->endOfQuarter();
                $groupBy = 'month';
                break;
            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                $groupBy = 'month';
                break;
            default: // month
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $groupBy = 'day';
                break;
        }

        // Total revenue in period
        $totalRevenue = Pendapatan::whereBetween('tanggal_pendapatan', [$startDate, $endDate])
                                ->sum('jumlah');

        // Transaction count
        $transactionCount = Pendapatan::whereBetween('tanggal_pendapatan', [$startDate, $endDate])
                                    ->count();

        // Average transaction
        $avgTransaction = $transactionCount > 0 ? $totalRevenue / $transactionCount : 0;

        // Revenue by category
        $revenueByCategory = Pendapatan::whereBetween('tanggal_pendapatan', [$startDate, $endDate])
                                     ->get()
                                     ->groupBy(function ($item) {
                                         return $this->categorizeRevenue($item->sumber_pendapatan);
                                     })
                                     ->map(function ($group) {
                                         return [
                                             'total' => $group->sum('jumlah'),
                                             'count' => $group->count(),
                                         ];
                                     });

        // Top revenue sources
        $topSources = Pendapatan::whereBetween('tanggal_pendapatan', [$startDate, $endDate])
                               ->selectRaw('sumber_pendapatan, SUM(jumlah) as total, COUNT(*) as count')
                               ->groupBy('sumber_pendapatan')
                               ->orderByDesc('total')
                               ->limit(10)
                               ->get()
                               ->toArray();

        return [
            'period' => $period,
            'period_dates' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
            'summary' => [
                'total_revenue' => $totalRevenue,
                'transaction_count' => $transactionCount,
                'average_transaction' => $avgTransaction,
            ],
            'revenue_by_category' => $revenueByCategory,
            'top_sources' => $topSources,
        ];
    }

    /**
     * Categorize revenue source
     */
    private function categorizeRevenue(string $source): string
    {
        $source = strtolower($source);
        
        if (str_contains($source, 'konsultasi') || str_contains($source, 'konsul')) {
            return 'consultation';
        } elseif (str_contains($source, 'obat') || str_contains($source, 'farmasi')) {
            return 'medication';
        } elseif (str_contains($source, 'alat') || str_contains($source, 'peralatan')) {
            return 'equipment';
        } elseif (str_contains($source, 'tindakan') || str_contains($source, 'medis') || str_contains($source, 'operasi')) {
            return 'procedure';
        }
        
        return 'other';
    }

    /**
     * Get source suggestions
     */
    private function getSourceSuggestions(string $query = ''): array
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
        ];

        // Add recent sources from database
        $recentSources = Pendapatan::select('sumber_pendapatan')
                                 ->where('created_at', '>=', now()->subDays(30))
                                 ->when($query, function ($q) use ($query) {
                                     $q->where('sumber_pendapatan', 'like', "%{$query}%");
                                 })
                                 ->distinct()
                                 ->limit(10)
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
     * Get template suggestions
     */
    private function getTemplateSuggestions(string $query = ''): array
    {
        $templates = [
            [
                'name' => 'Konsultasi Dokter Umum',
                'source' => 'Konsultasi Dokter Umum',
                'amount' => 50000,
                'description' => 'Konsultasi medis umum'
            ],
            [
                'name' => 'Pemeriksaan Darah Lengkap',
                'source' => 'Pemeriksaan Laboratorium',
                'amount' => 75000,
                'description' => 'Lab darah lengkap'
            ],
            [
                'name' => 'USG',
                'source' => 'USG',
                'amount' => 150000,
                'description' => 'Pemeriksaan USG'
            ],
        ];

        if ($query) {
            $templates = array_filter($templates, function ($template) use ($query) {
                return stripos($template['name'], $query) !== false || 
                       stripos($template['source'], $query) !== false;
            });
        }

        return array_values($templates);
    }

    /**
     * Get unpaid tindakan suggestions
     */
    private function getUnpaidTindakanSuggestions(string $query = ''): array
    {
        $queryBuilder = Tindakan::with(['pasien:id,nama_pasien,nomor_pasien', 'jenisTindakan:id,nama_tindakan'])
                                ->whereDoesntHave('pendapatan')
                                ->where('status_validasi', 'approved')
                                ->where('tanggal_tindakan', '>=', now()->subDays(30));

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
                                   'display' => "#{$tindakan->id} - {$tindakan->jenisTindakan?->nama_tindakan} - {$tindakan->pasien?->nama_pasien}",
                                   'amount' => $tindakan->tarif,
                                   'date' => $tindakan->tanggal_tindakan,
                                   'patient' => $tindakan->pasien?->nama_pasien,
                                   'procedure_type' => $tindakan->jenisTindakan?->nama_tindakan,
                               ];
                           })
                           ->toArray();
    }
}
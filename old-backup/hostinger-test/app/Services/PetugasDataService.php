<?php

namespace App\Services;

use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Models\JenisTindakan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Exception;

class PetugasDataService
{
    protected int $cacheMinutes = 30;
    protected PetugasConfigService $configService;
    
    public function __construct(PetugasConfigService $configService)
    {
        $this->configService = $configService;
    }
    
    /**
     * Get paginated patients with advanced filtering
     */
    public function getPasienList(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        try {
            $query = Pasien::query()
                ->where('input_by', Auth::id())
                ->with(['tindakan' => function($q) {
                    $q->latest()->limit(3);
                }]);
                
            // Apply filters
            $this->applyPasienFilters($query, $filters);
            
            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'created_at';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);
            
            return $query->paginate($perPage);
            
        } catch (Exception $e) {
            Log::error('Failed to get pasien list', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            
            return new LengthAwarePaginator([], 0, $perPage);
        }
    }
    
    /**
     * Apply filters to pasien query
     */
    protected function applyPasienFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('nama_pasien', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%")
                  ->orWhere('nomor_telepon', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filters['jenis_kelamin'])) {
            $query->where('jenis_kelamin', $filters['jenis_kelamin']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        if (!empty($filters['age_min'])) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) >= ?', [$filters['age_min']]);
        }
        
        if (!empty($filters['age_max'])) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) <= ?', [$filters['age_max']]);
        }
    }
    
    /**
     * Create new patient with validation
     */
    public function createPasien(array $data): array
    {
        try {
            DB::beginTransaction();
            
            // Add metadata
            $data['input_by'] = Auth::id();
            $data['created_at'] = now();
            
            // Generate patient number if not provided
            if (empty($data['nomor_pasien'])) {
                $data['nomor_pasien'] = $this->generatePatientNumber();
            }
            
            $pasien = Pasien::create($data);
            
            DB::commit();
            
            // Clear related caches
            $this->clearPasienCaches();
            
            return [
                'success' => true,
                'data' => $pasien->load('tindakan'),
                'message' => 'Pasien berhasil ditambahkan'
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create pasien', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Gagal menambahkan pasien: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update patient data
     */
    public function updatePasien(int $id, array $data): array
    {
        try {
            DB::beginTransaction();
            
            $pasien = Pasien::where('id', $id)
                ->where('input_by', Auth::id())
                ->firstOrFail();
            
            // Add update metadata
            $data['updated_at'] = now();
            
            $pasien->update($data);
            
            DB::commit();
            
            // Clear related caches
            $this->clearPasienCaches();
            
            return [
                'success' => true,
                'data' => $pasien->fresh()->load('tindakan'),
                'message' => 'Data pasien berhasil diperbarui'
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update pasien', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Gagal memperbarui data pasien: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get patient details with related data
     */
    public function getPasienDetail(int $id): array
    {
        try {
            $pasien = Pasien::where('id', $id)
                ->where('input_by', Auth::id())
                ->with([
                    'tindakan.jenisTindakan',
                    'tindakan' => function($q) {
                        $q->orderBy('tanggal_tindakan', 'desc');
                    }
                ])
                ->firstOrFail();
                
            // Calculate additional stats
            $stats = $this->calculatePatientStats($pasien);
            
            return [
                'success' => true,
                'data' => $pasien,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get pasien detail', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Data pasien tidak ditemukan'
            ];
        }
    }
    
    /**
     * Get tindakan list with advanced filtering
     */
    public function getTindakanList(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        try {
            $query = Tindakan::query()
                ->where('input_by', Auth::id())
                ->with(['pasien', 'jenisTindakan']);
                
            // Apply filters
            $this->applyTindakanFilters($query, $filters);
            
            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'tanggal_tindakan';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);
            
            return $query->paginate($perPage);
            
        } catch (Exception $e) {
            Log::error('Failed to get tindakan list', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            
            return new LengthAwarePaginator([], 0, $perPage);
        }
    }
    
    /**
     * Apply filters to tindakan query
     */
    protected function applyTindakanFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('pasien', function($q) use ($search) {
                $q->where('nama_pasien', 'like', "%{$search}%");
            })->orWhereHas('jenisTindakan', function($q) use ($search) {
                $q->where('nama_tindakan', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filters['jenis_tindakan_id'])) {
            $query->where('jenis_tindakan_id', $filters['jenis_tindakan_id']);
        }
        
        if (!empty($filters['status_validasi'])) {
            $query->where('status_validasi', $filters['status_validasi']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->whereDate('tanggal_tindakan', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('tanggal_tindakan', '<=', $filters['date_to']);
        }
        
        if (!empty($filters['tarif_min'])) {
            $query->where('tarif', '>=', $filters['tarif_min']);
        }
        
        if (!empty($filters['tarif_max'])) {
            $query->where('tarif', '<=', $filters['tarif_max']);
        }
    }
    
    /**
     * Create new tindakan
     */
    public function createTindakan(array $data): array
    {
        try {
            DB::beginTransaction();
            
            // Add metadata
            $data['input_by'] = Auth::id();
            $data['created_at'] = now();
            $data['status_validasi'] = 'pending';
            
            // Auto-approve based on configuration
            $autoApprovalThreshold = $this->configService->getConfig('validation_config')['auto_approval_thresholds']['tindakan'] ?? 0;
            if (($data['tarif'] ?? 0) <= $autoApprovalThreshold) {
                $data['status_validasi'] = 'approved';
                $data['approved_at'] = now();
                $data['approved_by'] = Auth::id();
            }
            
            $tindakan = Tindakan::create($data);
            
            DB::commit();
            
            // Clear related caches
            $this->clearTindakanCaches();
            
            return [
                'success' => true,
                'data' => $tindakan->load(['pasien', 'jenisTindakan']),
                'message' => 'Tindakan berhasil ditambahkan'
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create tindakan', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Gagal menambahkan tindakan: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get pendapatan harian list
     */
    public function getPendapatanHarianList(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        try {
            $query = PendapatanHarian::query()
                ->where('user_id', Auth::id())
                ->with(['pendapatan']);
                
            // Apply filters
            $this->applyPendapatanFilters($query, $filters);
            
            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'tanggal_input';
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);
            
            return $query->paginate($perPage);
            
        } catch (Exception $e) {
            Log::error('Failed to get pendapatan harian list', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            
            return new LengthAwarePaginator([], 0, $perPage);
        }
    }
    
    /**
     * Apply filters to pendapatan query
     */
    protected function applyPendapatanFilters($query, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $query->where('tanggal_input', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('tanggal_input', '<=', $filters['date_to']);
        }
        
        if (!empty($filters['nominal_min'])) {
            $query->where('nominal', '>=', $filters['nominal_min']);
        }
        
        if (!empty($filters['nominal_max'])) {
            $query->where('nominal', '<=', $filters['nominal_max']);
        }
        
        if (!empty($filters['status_validasi'])) {
            $query->where('status_validasi', $filters['status_validasi']);
        }
    }
    
    /**
     * Create pendapatan harian
     */
    public function createPendapatanHarian(array $data): array
    {
        try {
            DB::beginTransaction();
            
            // Add metadata
            $data['user_id'] = Auth::id();
            $data['created_at'] = now();
            $data['status_validasi'] = 'pending';
            
            // Auto-approve based on configuration
            $autoApprovalThreshold = $this->configService->getConfig('validation_config')['auto_approval_thresholds']['pendapatan_harian'] ?? 0;
            if (($data['nominal'] ?? 0) <= $autoApprovalThreshold) {
                $data['status_validasi'] = 'approved';
                $data['approved_at'] = now();
                $data['approved_by'] = Auth::id();
            }
            
            $pendapatanHarian = PendapatanHarian::create($data);
            
            DB::commit();
            
            // Clear related caches
            $this->clearPendapatanCaches();
            
            return [
                'success' => true,
                'data' => $pendapatanHarian->load('pendapatan'),
                'message' => 'Pendapatan harian berhasil ditambahkan'
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create pendapatan harian', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Gagal menambahkan pendapatan harian: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get form data for create/edit operations
     */
    public function getFormData(): array
    {
        try {
            $cacheKey = 'petugas_form_data_' . Auth::id();
            
            return Cache::remember($cacheKey, now()->addMinutes($this->cacheMinutes), function () {
                return [
                    'jenis_tindakan' => JenisTindakan::select('id', 'nama_tindakan', 'tarif_default')
                        ->orderBy('nama_tindakan')
                        ->get(),
                    'pasien_recent' => Pasien::where('input_by', Auth::id())
                        ->select('id', 'nama_pasien', 'nomor_pasien')
                        ->latest()
                        ->limit(10)
                        ->get(),
                    'config' => [
                        'form_fields' => $this->configService->getFormFields(),
                        'validation_config' => $this->configService->getValidationConfig(),
                    ]
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get form data', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get dashboard summary data
     */
    public function getDashboardSummary(): array
    {
        try {
            $cacheKey = 'petugas_dashboard_summary_' . Auth::id();
            
            return Cache::remember($cacheKey, now()->addMinutes(10), function () {
                $today = Carbon::today();
                $thisMonth = Carbon::now()->startOfMonth();
                
                return [
                    'today_stats' => [
                        'pasien_count' => Pasien::where('input_by', Auth::id())
                            ->whereDate('created_at', $today)
                            ->count(),
                        'tindakan_count' => Tindakan::where('input_by', Auth::id())
                            ->whereDate('tanggal_tindakan', $today)
                            ->count(),
                        'pendapatan_sum' => PendapatanHarian::where('user_id', Auth::id())
                            ->where('tanggal_input', $today->format('Y-m-d'))
                            ->sum('nominal'),
                        'pengeluaran_sum' => PengeluaranHarian::where('user_id', Auth::id())
                            ->where('tanggal_input', $today->format('Y-m-d'))
                            ->sum('nominal'),
                    ],
                    'month_stats' => [
                        'pasien_count' => Pasien::where('input_by', Auth::id())
                            ->where('created_at', '>=', $thisMonth)
                            ->count(),
                        'tindakan_count' => Tindakan::where('input_by', Auth::id())
                            ->where('tanggal_tindakan', '>=', $thisMonth)
                            ->count(),
                    ],
                    'pending_validations' => [
                        'tindakan' => Tindakan::where('input_by', Auth::id())
                            ->where('status_validasi', 'pending')
                            ->count(),
                        'pendapatan' => PendapatanHarian::where('user_id', Auth::id())
                            ->where('status_validasi', 'pending')
                            ->count(),
                    ]
                ];
            });
            
        } catch (Exception $e) {
            Log::error('Failed to get dashboard summary', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Generate unique patient number
     */
    protected function generatePatientNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Get last number for this month
        $lastNumber = DB::table('pasien')
            ->where('nomor_pasien', 'like', "RM-{$year}{$month}-%")
            ->orderBy('nomor_pasien', 'desc')
            ->value('nomor_pasien');
            
        if ($lastNumber) {
            $lastSequence = (int) substr($lastNumber, -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }
        
        return sprintf('RM-%s%s-%04d', $year, $month, $newSequence);
    }
    
    /**
     * Calculate patient statistics
     */
    protected function calculatePatientStats(Pasien $pasien): array
    {
        $tindakanStats = $pasien->tindakan;
        
        return [
            'total_tindakan' => $tindakanStats->count(),
            'total_biaya' => $tindakanStats->sum('tarif'),
            'last_visit' => $tindakanStats->max('tanggal_tindakan'),
            'most_frequent_procedure' => $tindakanStats
                ->groupBy('jenis_tindakan_id')
                ->map->count()
                ->sortDesc()
                ->keys()
                ->first(),
            'age' => $pasien->tanggal_lahir ? 
                Carbon::parse($pasien->tanggal_lahir)->age : null,
        ];
    }
    
    /**
     * Clear related caches
     */
    protected function clearPasienCaches(): void
    {
        $userId = Auth::id();
        Cache::forget("petugas_form_data_{$userId}");
        Cache::forget("petugas_dashboard_summary_{$userId}");
    }
    
    protected function clearTindakanCaches(): void
    {
        $userId = Auth::id();
        Cache::forget("petugas_form_data_{$userId}");
        Cache::forget("petugas_dashboard_summary_{$userId}");
    }
    
    protected function clearPendapatanCaches(): void
    {
        $userId = Auth::id();
        Cache::forget("petugas_dashboard_summary_{$userId}");
    }
    
    /**
     * Bulk operations
     */
    public function bulkDeletePasien(array $ids): array
    {
        try {
            DB::beginTransaction();
            
            $deletedCount = Pasien::where('input_by', Auth::id())
                ->whereIn('id', $ids)
                ->delete();
            
            DB::commit();
            
            $this->clearPasienCaches();
            
            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => "{$deletedCount} pasien berhasil dihapus"
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to bulk delete pasien', [
                'ids' => $ids,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Gagal menghapus data pasien: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Export data to various formats
     */
    public function exportData(string $type, array $filters = [], string $format = 'excel'): array
    {
        try {
            switch ($type) {
                case 'pasien':
                    $data = $this->getPasienForExport($filters);
                    break;
                case 'tindakan':
                    $data = $this->getTindakanForExport($filters);
                    break;
                case 'pendapatan':
                    $data = $this->getPendapatanForExport($filters);
                    break;
                default:
                    throw new Exception('Invalid export type');
            }
            
            // Generate export file (placeholder - implement actual export logic)
            $filename = "{$type}_export_" . date('Y-m-d_H-i-s') . ".{$format}";
            
            return [
                'success' => true,
                'filename' => $filename,
                'download_url' => "/exports/{$filename}",
                'record_count' => count($data)
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to export data', [
                'type' => $type,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Gagal mengekspor data: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get data for export
     */
    protected function getPasienForExport(array $filters): Collection
    {
        $query = Pasien::where('input_by', Auth::id());
        $this->applyPasienFilters($query, $filters);
        return $query->get();
    }
    
    protected function getTindakanForExport(array $filters): Collection
    {
        $query = Tindakan::where('input_by', Auth::id())->with(['pasien', 'jenisTindakan']);
        $this->applyTindakanFilters($query, $filters);
        return $query->get();
    }
    
    protected function getPendapatanForExport(array $filters): Collection
    {
        $query = PendapatanHarian::where('user_id', Auth::id())->with('pendapatan');
        $this->applyPendapatanFilters($query, $filters);
        return $query->get();
    }
}
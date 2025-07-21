<?php

namespace App\Http\Controllers\Petugas\Enhanced;

use App\Http\Controllers\Controller;
use App\Models\Tindakan;
use App\Models\Pasien;
use App\Models\JenisTindakan;
use App\Models\Dokter;
use App\Models\Pegawai;
use App\Models\User;
use App\Services\PetugasDataService;
use App\Services\PetugasStatsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TindakanController extends Controller
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
     * Display enhanced tindakan list with timeline view
     */
    public function index(): View
    {
        // Get statistical overview
        $stats = $this->getTindakanStats();
        
        // Get recent activity for quick view
        $recentTindakan = $this->getRecentTindakan(5);
        
        return view('petugas.enhanced.tindakan.index', compact('stats', 'recentTindakan'));
    }

    /**
     * Get paginated tindakan data for AJAX
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->get('per_page', 15), 100);
            $search = $request->get('search');
            $filters = $request->only(['status', 'tanggal_from', 'tanggal_to', 'jenis_tindakan', 'dokter', 'pasien']);
            $sort = $request->get('sort', 'tanggal_tindakan');
            $direction = $request->get('direction', 'desc');

            $query = Tindakan::with([
                'pasien:id,nama_pasien,nomor_pasien',
                'dokter:id,nama_dokter',
                'paramedis:id,nama_pegawai',
                'nonParamedis:id,nama_pegawai',
                'jenisTindakan:id,nama_tindakan',
                'inputBy:id,name'
            ]);

            // Apply search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('pasien', function ($q) use ($search) {
                        $q->where('nama_pasien', 'like', "%{$search}%")
                          ->orWhere('nomor_pasien', 'like', "%{$search}%");
                    })
                    ->orWhereHas('dokter', function ($q) use ($search) {
                        $q->where('nama_dokter', 'like', "%{$search}%");
                    })
                    ->orWhereHas('jenisTindakan', function ($q) use ($search) {
                        $q->where('nama_tindakan', 'like', "%{$search}%");
                    })
                    ->orWhere('keterangan', 'like', "%{$search}%");
                });
            }

            // Apply filters
            if (!empty($filters['status'])) {
                $query->where('status_validasi', $filters['status']);
            }

            if (!empty($filters['tanggal_from'])) {
                $query->where('tanggal_tindakan', '>=', $filters['tanggal_from']);
            }

            if (!empty($filters['tanggal_to'])) {
                $query->where('tanggal_tindakan', '<=', $filters['tanggal_to']);
            }

            if (!empty($filters['jenis_tindakan'])) {
                $query->where('jenis_tindakan_id', $filters['jenis_tindakan']);
            }

            if (!empty($filters['dokter'])) {
                $query->where('dokter_id', $filters['dokter']);
            }

            if (!empty($filters['pasien'])) {
                $query->where('pasien_id', $filters['pasien']);
            }

            // Apply sorting
            $allowedSorts = ['tanggal_tindakan', 'tarif', 'created_at', 'status_validasi'];
            if (in_array($sort, $allowedSorts)) {
                $query->orderBy($sort, $direction);
            }

            $tindakan = $query->paginate($perPage);

            // Transform data for better frontend handling
            $transformedData = $tindakan->through(function ($item) {
                return [
                    'id' => $item->id,
                    'pasien' => [
                        'id' => $item->pasien?->id,
                        'nama' => $item->pasien?->nama_pasien,
                        'nomor' => $item->pasien?->nomor_pasien,
                    ],
                    'dokter' => [
                        'id' => $item->dokter?->id,
                        'nama' => $item->dokter?->nama_dokter,
                    ],
                    'paramedis' => [
                        'id' => $item->paramedis?->id,
                        'nama' => $item->paramedis?->nama_pegawai,
                    ],
                    'non_paramedis' => [
                        'id' => $item->nonParamedis?->id,
                        'nama' => $item->nonParamedis?->nama_pegawai,
                    ],
                    'jenis_tindakan' => [
                        'id' => $item->jenisTindakan?->id,
                        'nama' => $item->jenisTindakan?->nama_tindakan,
                    ],
                    'tanggal_tindakan' => $item->tanggal_tindakan,
                    'tarif' => $item->tarif,
                    'jasa_dokter' => $item->jasa_dokter,
                    'jasa_paramedis' => $item->jasa_paramedis,
                    'jasa_non_paramedis' => $item->jasa_non_paramedis,
                    'status_validasi' => $item->status_validasi,
                    'keterangan' => $item->keterangan,
                    'created_at' => $item->created_at,
                    'input_by' => $item->inputBy?->name,
                    'formatted_date' => Carbon::parse($item->tanggal_tindakan)->format('d M Y'),
                    'formatted_time' => Carbon::parse($item->created_at)->format('H:i'),
                    'formatted_tarif' => 'Rp ' . number_format($item->tarif, 0, ',', '.'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'meta' => [
                    'current_page' => $tindakan->currentPage(),
                    'last_page' => $tindakan->lastPage(),
                    'per_page' => $tindakan->perPage(),
                    'total' => $tindakan->total(),
                    'from' => $tindakan->firstItem(),
                    'to' => $tindakan->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Tindakan getData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data tindakan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show create form
     */
    public function create(): View
    {
        // Get required data for dropdowns
        $pasienList = Pasien::select('id', 'nama_pasien', 'nomor_pasien')
                           ->orderBy('nama_pasien')
                           ->get();
        
        $dokterList = Dokter::select('id', 'nama_dokter', 'jabatan')
                           ->orderBy('nama_dokter')
                           ->get();
        
        $paramedisList = Pegawai::where('jenis_pegawai', 'Paramedis')
                               ->select('id', 'nama_pegawai')
                               ->orderBy('nama_pegawai')
                               ->get();
        
        $nonParamedisList = Pegawai::where('jenis_pegawai', 'Non-Paramedis')
                                  ->select('id', 'nama_pegawai')
                                  ->orderBy('nama_pegawai')
                                  ->get();
        
        $jenisTindakanList = JenisTindakan::select('id', 'nama_tindakan', 'tarif_standar')
                                        ->orderBy('nama_tindakan')
                                        ->get();

        return view('petugas.enhanced.tindakan.create', compact(
            'pasienList', 'dokterList', 'paramedisList', 'nonParamedisList', 'jenisTindakanList'
        ));
    }

    /**
     * Store new tindakan
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pasien_id' => 'required|exists:pasiens,id',
                'jenis_tindakan_id' => 'required|exists:jenis_tindakans,id',
                'dokter_id' => 'nullable|exists:dokters,id',
                'paramedis_id' => 'nullable|exists:pegawais,id',
                'non_paramedis_id' => 'nullable|exists:pegawais,id',
                'tanggal_tindakan' => 'required|date',
                'tarif' => 'required|numeric|min:0',
                'jasa_dokter' => 'nullable|numeric|min:0',
                'jasa_paramedis' => 'nullable|numeric|min:0',
                'jasa_non_paramedis' => 'nullable|numeric|min:0',
                'keterangan' => 'nullable|string|max:1000',
                'status_validasi' => 'nullable|in:pending,approved,rejected',
            ]);

            // Auto-set staff validation
            $validated['status_validasi'] = $validated['status_validasi'] ?? 'pending';
            $validated['input_by'] = auth()->id();

            $tindakan = Tindakan::create($validated);

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Tindakan berhasil disimpan',
                'data' => $tindakan->load(['pasien', 'dokter', 'jenisTindakan'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Enhanced Tindakan store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan tindakan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific tindakan detail
     */
    public function show($id): View
    {
        $tindakan = Tindakan::with([
            'pasien',
            'dokter',
            'paramedis',
            'nonParamedis',
            'jenisTindakan',
            'inputBy'
        ])->findOrFail($id);

        // Get timeline for this patient (related procedures)
        $timeline = $this->getPatientTimeline($tindakan->pasien_id, $id);

        return view('petugas.enhanced.tindakan.show', compact('tindakan', 'timeline'));
    }

    /**
     * Show edit form
     */
    public function edit($id): View
    {
        $tindakan = Tindakan::with([
            'pasien',
            'dokter',
            'paramedis',
            'nonParamedis',
            'jenisTindakan'
        ])->findOrFail($id);

        // Get required data for dropdowns (same as create)
        $pasienList = Pasien::select('id', 'nama_pasien', 'nomor_pasien')
                           ->orderBy('nama_pasien')
                           ->get();
        
        $dokterList = Dokter::select('id', 'nama_dokter', 'jabatan')
                           ->orderBy('nama_dokter')
                           ->get();
        
        $paramedisList = Pegawai::where('jenis_pegawai', 'Paramedis')
                               ->select('id', 'nama_pegawai')
                               ->orderBy('nama_pegawai')
                               ->get();
        
        $nonParamedisList = Pegawai::where('jenis_pegawai', 'Non-Paramedis')
                                  ->select('id', 'nama_pegawai')
                                  ->orderBy('nama_pegawai')
                                  ->get();
        
        $jenisTindakanList = JenisTindakan::select('id', 'nama_tindakan', 'tarif_standar')
                                        ->orderBy('nama_tindakan')
                                        ->get();

        return view('petugas.enhanced.tindakan.edit', compact(
            'tindakan', 'pasienList', 'dokterList', 'paramedisList', 'nonParamedisList', 'jenisTindakanList'
        ));
    }

    /**
     * Update existing tindakan
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $tindakan = Tindakan::findOrFail($id);

            $validated = $request->validate([
                'pasien_id' => 'required|exists:pasiens,id',
                'jenis_tindakan_id' => 'required|exists:jenis_tindakans,id',
                'dokter_id' => 'nullable|exists:dokters,id',
                'paramedis_id' => 'nullable|exists:pegawais,id',
                'non_paramedis_id' => 'nullable|exists:pegawais,id',
                'tanggal_tindakan' => 'required|date',
                'tarif' => 'required|numeric|min:0',
                'jasa_dokter' => 'nullable|numeric|min:0',
                'jasa_paramedis' => 'nullable|numeric|min:0',
                'jasa_non_paramedis' => 'nullable|numeric|min:0',
                'keterangan' => 'nullable|string|max:1000',
                'status_validasi' => 'nullable|in:pending,approved,rejected',
            ]);

            $tindakan->update($validated);

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Tindakan berhasil diperbarui',
                'data' => $tindakan->load(['pasien', 'dokter', 'jenisTindakan'])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Enhanced Tindakan update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui tindakan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete tindakan
     */
    public function destroy($id): JsonResponse
    {
        try {
            $tindakan = Tindakan::findOrFail($id);
            $tindakan->delete();

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'Tindakan berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Tindakan destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus tindakan: ' . $e->getMessage()
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
                'ids.*' => 'exists:tindakans,id',
                'status' => 'required|in:pending,approved,rejected'
            ]);

            $updated = Tindakan::whereIn('id', $validated['ids'])
                              ->update(['status_validasi' => $validated['status']]);

            // Clear relevant caches
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => "Status {$updated} tindakan berhasil diperbarui"
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Tindakan bulk update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get timeline for specific patient
     */
    public function getTimeline($patientId): JsonResponse
    {
        try {
            $timeline = $this->getPatientTimeline($patientId);

            return response()->json([
                'success' => true,
                'data' => $timeline
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Tindakan timeline error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat timeline: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export tindakan data
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
            Log::error('Enhanced Tindakan export error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengekspor data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get autocomplete suggestions for search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $type = $request->get('type', 'all'); // pasien, dokter, tindakan

            $results = [];

            if ($type === 'all' || $type === 'pasien') {
                $pasien = Pasien::where('nama_pasien', 'like', "%{$query}%")
                               ->orWhere('nomor_pasien', 'like', "%{$query}%")
                               ->limit(5)
                               ->get(['id', 'nama_pasien', 'nomor_pasien']);
                
                $results['pasien'] = $pasien->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'text' => "{$p->nama_pasien} ({$p->nomor_pasien})",
                        'type' => 'pasien'
                    ];
                });
            }

            if ($type === 'all' || $type === 'dokter') {
                $dokter = Dokter::where('nama_dokter', 'like', "%{$query}%")
                               ->limit(5)
                               ->get(['id', 'nama_dokter', 'jabatan']);
                
                $results['dokter'] = $dokter->map(function ($d) {
                    return [
                        'id' => $d->id,
                        'text' => "{$d->nama_dokter} ({$d->jabatan})",
                        'type' => 'dokter'
                    ];
                });
            }

            if ($type === 'all' || $type === 'tindakan') {
                $tindakan = JenisTindakan::where('nama_tindakan', 'like', "%{$query}%")
                                        ->limit(5)
                                        ->get(['id', 'nama_tindakan', 'tarif_standar']);
                
                $results['tindakan'] = $tindakan->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'text' => $t->nama_tindakan,
                        'tarif' => $t->tarif_standar,
                        'type' => 'tindakan'
                    ];
                });
            }

            return response()->json([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Enhanced Tindakan search error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tindakan statistics
     */
    private function getTindakanStats(): array
    {
        return Cache::remember('enhanced_tindakan_stats', 300, function () {
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();

            return [
                'total_today' => Tindakan::whereDate('tanggal_tindakan', $today)->count(),
                'total_month' => Tindakan::where('tanggal_tindakan', '>=', $thisMonth)->count(),
                'total_pending' => Tindakan::where('status_validasi', 'pending')->count(),
                'total_approved' => Tindakan::where('status_validasi', 'approved')->count(),
                'revenue_today' => Tindakan::whereDate('tanggal_tindakan', $today)->sum('tarif'),
                'revenue_month' => Tindakan::where('tanggal_tindakan', '>=', $thisMonth)->sum('tarif'),
                'avg_tarif' => Tindakan::avg('tarif') ?? 0,
                'top_procedures' => $this->getTopProcedures(),
            ];
        });
    }

    /**
     * Get top procedures by frequency
     */
    private function getTopProcedures(): array
    {
        return Tindakan::select('jenis_tindakan_id')
                      ->with('jenisTindakan:id,nama_tindakan')
                      ->groupBy('jenis_tindakan_id')
                      ->selectRaw('count(*) as total')
                      ->orderByDesc('total')
                      ->limit(5)
                      ->get()
                      ->map(function ($item) {
                          return [
                              'name' => $item->jenisTindakan?->nama_tindakan ?? 'Unknown',
                              'count' => $item->total
                          ];
                      })
                      ->toArray();
    }

    /**
     * Get recent tindakan
     */
    private function getRecentTindakan(int $limit = 10): array
    {
        return Tindakan::with(['pasien:id,nama_pasien', 'jenisTindakan:id,nama_tindakan'])
                      ->orderByDesc('created_at')
                      ->limit($limit)
                      ->get()
                      ->map(function ($item) {
                          return [
                              'id' => $item->id,
                              'pasien_nama' => $item->pasien?->nama_pasien,
                              'tindakan_nama' => $item->jenisTindakan?->nama_tindakan,
                              'tarif' => $item->tarif,
                              'status' => $item->status_validasi,
                              'tanggal' => $item->tanggal_tindakan,
                              'created_at' => $item->created_at,
                          ];
                      })
                      ->toArray();
    }

    /**
     * Get patient timeline (all procedures for a patient)
     */
    private function getPatientTimeline(int $patientId, int $excludeId = null): array
    {
        $query = Tindakan::where('pasien_id', $patientId)
                         ->with(['jenisTindakan:id,nama_tindakan', 'dokter:id,nama_dokter'])
                         ->orderByDesc('tanggal_tindakan');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get()
                    ->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'type' => 'procedure',
                            'title' => $item->jenisTindakan?->nama_tindakan ?? 'Tindakan Medis',
                            'description' => $item->keterangan ?: 'Tindakan oleh ' . ($item->dokter?->nama_dokter ?? 'Dokter'),
                            'date' => $item->tanggal_tindakan,
                            'status' => $item->status_validasi,
                            'tarif' => $item->tarif,
                            'created_at' => $item->created_at,
                        ];
                    })
                    ->toArray();
    }
}
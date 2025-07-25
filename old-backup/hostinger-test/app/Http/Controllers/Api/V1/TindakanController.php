<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Tindakan;
use App\Models\Pasien;
use App\Models\JenisTindakan;
use App\Models\Dokter;
use App\Models\Pegawai;
use App\Http\Resources\V1\TindakanResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class TindakanController extends BaseApiController
{
    /**
     * Display a listing of medical procedures
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'dokter']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Get pagination parameters
            $pagination = $this->getPaginationParams($request);

            // Build query with relationships
            $query = Tindakan::with([
                'pasien:id,nama_pasien,nomor_pasien',
                'dokter:id,nama_dokter,jabatan',
                'jenisTindakan:id,nama_tindakan,kategori',
                'paramedis:id,nama_pegawai',
                'nonParamedis:id,nama_pegawai'
            ]);

            // Apply filters
            $query = $this->applyTindakanFilters($query, $request);

            // Apply sorting
            $allowedSorts = ['tanggal_tindakan', 'created_at', 'tarif', 'status_validasi'];
            $query = $this->applySorting($query, $request, $allowedSorts, 'tanggal_tindakan', 'desc');

            // Get paginated results
            $tindakans = $query->paginate($pagination['per_page']);

            // Transform data based on view type
            $viewType = $request->get('view', 'default'); // default, minimal, mobile
            $transformedData = $tindakans->through(function ($tindakan) use ($viewType, $request) {
                $resource = new TindakanResource($tindakan);
                return match ($viewType) {
                    'minimal' => $resource->toArrayMinimal($request),
                    'mobile' => $resource->toArrayMobile($request),
                    default => $resource->toArray($request),
                };
            });

            $this->logApiActivity('tindakans.index', ['count' => $tindakans->total()]);

            return $this->paginatedResponse($transformedData, 'Daftar tindakan berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching tindakans');
        }
    }

    /**
     * Store a newly created medical procedure
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
                'tanggal_tindakan' => 'required|date',
                'keluhan' => 'required|string|max:1000',
                'diagnosa' => 'required|string|max:1000',
                'tindakan_medis' => 'required|string|max:1000',
                'obat_diberikan' => 'nullable|string|max:1000',
                'catatan_tambahan' => 'nullable|string|max:1000',
                'tarif' => 'required|numeric|min:0',
                'pasien_id' => 'required|exists:pasiens,id',
                'jenis_tindakan_id' => 'required|exists:jenis_tindakans,id',
                'dokter_id' => 'nullable|exists:dokters,id',
                'paramedis_id' => 'nullable|exists:pegawais,id',
                'non_paramedis_id' => 'nullable|exists:pegawais,id',
            ]);

            // Validate team assignment (at least dokter or paramedis must be assigned)
            if (empty($validated['dokter_id']) && empty($validated['paramedis_id'])) {
                return $this->errorResponse(
                    'Minimal satu dokter atau paramedis harus ditugaskan',
                    422
                );
            }

            // Validate staff types
            if (!empty($validated['paramedis_id'])) {
                $paramedis = Pegawai::find($validated['paramedis_id']);
                if (!$paramedis || $paramedis->jenis_pegawai !== 'paramedis') {
                    return $this->errorResponse('Pegawai yang dipilih bukan paramedis', 422);
                }
            }

            if (!empty($validated['non_paramedis_id'])) {
                $nonParamedis = Pegawai::find($validated['non_paramedis_id']);
                if (!$nonParamedis || $nonParamedis->jenis_pegawai !== 'non_paramedis') {
                    return $this->errorResponse('Pegawai yang dipilih bukan non-paramedis', 422);
                }
            }

            DB::beginTransaction();

            // Set default status
            $validated['status_validasi'] = 'pending';
            $validated['input_by'] = $this->getAuthUser()->id;

            // Create tindakan
            $tindakan = Tindakan::create($validated);

            // Load relationships
            $tindakan->load([
                'pasien:id,nama_pasien,nomor_pasien',
                'dokter:id,nama_dokter,jabatan',
                'jenisTindakan:id,nama_tindakan,kategori',
                'paramedis:id,nama_pegawai',
                'nonParamedis:id,nama_pegawai'
            ]);

            DB::commit();

            $this->logApiActivity('tindakans.store', ['tindakan_id' => $tindakan->id]);

            return $this->successResponse(
                new TindakanResource($tindakan),
                'Tindakan berhasil ditambahkan',
                201
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error creating tindakan');
        }
    }

    /**
     * Display the specified medical procedure
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'dokter']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find tindakan with relationships
            $tindakan = Tindakan::with([
                'pasien:id,nama_pasien,nomor_pasien,tanggal_lahir,jenis_kelamin',
                'dokter:id,nama_dokter,jabatan,spesialisasi',
                'jenisTindakan:id,nama_tindakan,kategori,deskripsi',
                'paramedis:id,nama_pegawai',
                'nonParamedis:id,nama_pegawai',
                'pendapatan:id,jumlah,tanggal_pendapatan',
                'inputBy:id,name'
            ])->findOrFail($id);

            $this->logApiActivity('tindakans.show', ['tindakan_id' => $id]);

            return $this->successResponse(
                new TindakanResource($tindakan),
                'Detail tindakan berhasil dimuat'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Tindakan tidak ditemukan', 404);
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching tindakan');
        }
    }

    /**
     * Update the specified medical procedure
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find tindakan
            $tindakan = Tindakan::findOrFail($id);

            // Check if already approved (cannot edit approved tindakan)
            if ($tindakan->status_validasi === 'approved') {
                return $this->errorResponse(
                    'Tidak dapat mengubah tindakan yang sudah disetujui',
                    400
                );
            }

            // Validate request
            $validated = $request->validate([
                'tanggal_tindakan' => 'sometimes|required|date',
                'keluhan' => 'sometimes|required|string|max:1000',
                'diagnosa' => 'sometimes|required|string|max:1000',
                'tindakan_medis' => 'sometimes|required|string|max:1000',
                'obat_diberikan' => 'nullable|string|max:1000',
                'catatan_tambahan' => 'nullable|string|max:1000',
                'tarif' => 'sometimes|required|numeric|min:0',
                'dokter_id' => 'nullable|exists:dokters,id',
                'paramedis_id' => 'nullable|exists:pegawais,id',
                'non_paramedis_id' => 'nullable|exists:pegawais,id',
            ]);

            // Validate team assignment if provided
            $dokterId = $validated['dokter_id'] ?? $tindakan->dokter_id;
            $paramedisId = $validated['paramedis_id'] ?? $tindakan->paramedis_id;
            
            if (empty($dokterId) && empty($paramedisId)) {
                return $this->errorResponse(
                    'Minimal satu dokter atau paramedis harus ditugaskan',
                    422
                );
            }

            // Validate staff types if provided
            if (isset($validated['paramedis_id']) && !empty($validated['paramedis_id'])) {
                $paramedis = Pegawai::find($validated['paramedis_id']);
                if (!$paramedis || $paramedis->jenis_pegawai !== 'paramedis') {
                    return $this->errorResponse('Pegawai yang dipilih bukan paramedis', 422);
                }
            }

            if (isset($validated['non_paramedis_id']) && !empty($validated['non_paramedis_id'])) {
                $nonParamedis = Pegawai::find($validated['non_paramedis_id']);
                if (!$nonParamedis || $nonParamedis->jenis_pegawai !== 'non_paramedis') {
                    return $this->errorResponse('Pegawai yang dipilih bukan non-paramedis', 422);
                }
            }

            DB::beginTransaction();

            // Update tindakan
            $tindakan->update($validated);

            // Load relationships
            $tindakan->load([
                'pasien:id,nama_pasien,nomor_pasien',
                'dokter:id,nama_dokter,jabatan',
                'jenisTindakan:id,nama_tindakan,kategori',
                'paramedis:id,nama_pegawai',
                'nonParamedis:id,nama_pegawai'
            ]);

            DB::commit();

            $this->logApiActivity('tindakans.update', ['tindakan_id' => $id]);

            return $this->successResponse(
                new TindakanResource($tindakan->fresh()),
                'Tindakan berhasil diperbarui'
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Tindakan tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error updating tindakan');
        }
    }

    /**
     * Update tindakan status (approve/reject)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            // Validate permissions (only admin can approve/reject)
            $permissionCheck = $this->validateApiPermissions(['admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find tindakan
            $tindakan = Tindakan::findOrFail($id);

            // Validate request
            $validated = $request->validate([
                'status_validasi' => 'required|in:approved,rejected',
                'catatan_validasi' => 'nullable|string|max:500',
            ]);

            DB::beginTransaction();

            // Update status
            $tindakan->update([
                'status_validasi' => $validated['status_validasi'],
                'catatan_validasi' => $validated['catatan_validasi'],
                'validated_by' => $this->getAuthUser()->id,
                'validated_at' => now(),
            ]);

            DB::commit();

            $this->logApiActivity('tindakans.updateStatus', [
                'tindakan_id' => $id,
                'new_status' => $validated['status_validasi']
            ]);

            return $this->successResponse(
                new TindakanResource($tindakan->fresh()),
                'Status tindakan berhasil diperbarui'
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Tindakan tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error updating tindakan status');
        }
    }

    /**
     * Get patient timeline (medical history)
     */
    public function patientTimeline(Request $request, int $patientId): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin', 'dokter']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Verify patient exists
            $patient = Pasien::findOrFail($patientId);

            // Get patient's medical timeline
            $query = Tindakan::with([
                'dokter:id,nama_dokter,jabatan',
                'jenisTindakan:id,nama_tindakan,kategori',
                'paramedis:id,nama_pegawai',
                'nonParamedis:id,nama_pegawai'
            ])
            ->where('pasien_id', $patientId)
            ->orderBy('tanggal_tindakan', 'desc');

            // Apply date filters if provided
            if ($dateFrom = $request->get('date_from')) {
                $query->where('tanggal_tindakan', '>=', $dateFrom);
            }

            if ($dateTo = $request->get('date_to')) {
                $query->where('tanggal_tindakan', '<=', $dateTo);
            }

            // Apply status filter
            if ($status = $request->get('status')) {
                $query->where('status_validasi', $status);
            }

            $tindakans = $query->get();

            $transformedData = TindakanResource::collection($tindakans)
                ->map(function ($resource) use ($request) {
                    return $resource->toArray($request);
                });

            $this->logApiActivity('tindakans.patientTimeline', ['patient_id' => $patientId]);

            return $this->successResponse(
                [
                    'patient' => [
                        'id' => $patient->id,
                        'nama' => $patient->nama_pasien,
                        'nomor' => $patient->nomor_pasien,
                    ],
                    'timeline' => $transformedData,
                    'summary' => [
                        'total_procedures' => $tindakans->count(),
                        'approved' => $tindakans->where('status_validasi', 'approved')->count(),
                        'pending' => $tindakans->where('status_validasi', 'pending')->count(),
                        'rejected' => $tindakans->where('status_validasi', 'rejected')->count(),
                        'total_cost' => $tindakans->where('status_validasi', 'approved')->sum('tarif'),
                    ]
                ],
                'Timeline pasien berhasil dimuat'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching patient timeline');
        }
    }

    /**
     * Get tindakan statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $period = $request->get('period', 'month'); // day, week, month, year

            $stats = $this->calculateTindakanStatistics($period);

            return $this->successResponse($stats, 'Statistik tindakan berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching tindakan statistics');
        }
    }

    /**
     * Apply tindakan-specific filters
     */
    private function applyTindakanFilters($query, Request $request)
    {
        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('keluhan', 'like', "%{$search}%")
                  ->orWhere('diagnosa', 'like', "%{$search}%")
                  ->orWhere('tindakan_medis', 'like', "%{$search}%")
                  ->orWhereHas('pasien', function ($q) use ($search) {
                      $q->where('nama_pasien', 'like', "%{$search}%")
                        ->orWhere('nomor_pasien', 'like', "%{$search}%");
                  })
                  ->orWhereHas('dokter', function ($q) use ($search) {
                      $q->where('nama_dokter', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status_validasi', $status);
        }

        // Date range filters
        if ($dateFrom = $request->get('date_from')) {
            $query->where('tanggal_tindakan', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->where('tanggal_tindakan', '<=', $dateTo);
        }

        // Patient filter
        if ($patientId = $request->get('patient_id')) {
            $query->where('pasien_id', $patientId);
        }

        // Doctor filter
        if ($dokterId = $request->get('dokter_id')) {
            $query->where('dokter_id', $dokterId);
        }

        // Procedure type filter
        if ($jenisTindakanId = $request->get('jenis_tindakan_id')) {
            $query->where('jenis_tindakan_id', $jenisTindakanId);
        }

        // Tarif range filters
        if ($minTarif = $request->get('min_tarif')) {
            $query->where('tarif', '>=', $minTarif);
        }

        if ($maxTarif = $request->get('max_tarif')) {
            $query->where('tarif', '<=', $maxTarif);
        }

        return $query;
    }

    /**
     * Calculate tindakan statistics
     */
    private function calculateTindakanStatistics(string $period): array
    {
        $now = now();
        
        switch ($period) {
            case 'day':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
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

        $total = Tindakan::count();
        $inPeriod = Tindakan::whereBetween('tanggal_tindakan', [$startDate, $endDate])->count();
        
        $statusStats = Tindakan::selectRaw('status_validasi, COUNT(*) as count')
                             ->groupBy('status_validasi')
                             ->pluck('count', 'status_validasi')
                             ->toArray();

        $topProcedures = Tindakan::with('jenisTindakan:id,nama_tindakan')
                               ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
                               ->get()
                               ->groupBy('jenis_tindakan_id')
                               ->map(function ($group) {
                                   return [
                                       'name' => $group->first()->jenisTindakan?->nama_tindakan ?? 'Unknown',
                                       'count' => $group->count(),
                                       'total_revenue' => $group->where('status_validasi', 'approved')->sum('tarif')
                                   ];
                               })
                               ->sortByDesc('count')
                               ->take(5)
                               ->values()
                               ->toArray();

        $totalRevenue = Tindakan::where('status_validasi', 'approved')
                              ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
                              ->sum('tarif');

        return [
            'period' => $period,
            'total_procedures' => $total,
            'procedures_in_period' => $inPeriod,
            'status_distribution' => [
                'pending' => $statusStats['pending'] ?? 0,
                'approved' => $statusStats['approved'] ?? 0,
                'rejected' => $statusStats['rejected'] ?? 0,
            ],
            'top_procedures' => $topProcedures,
            'total_revenue' => $totalRevenue,
            'average_tarif' => $inPeriod > 0 ? $totalRevenue / $inPeriod : 0,
            'period_dates' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
        ];
    }
}
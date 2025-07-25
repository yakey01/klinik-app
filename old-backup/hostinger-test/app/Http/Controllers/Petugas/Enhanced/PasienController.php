<?php

namespace App\Http\Controllers\Petugas\Enhanced;

use App\Http\Controllers\Controller;
use App\Services\PetugasDataService;
use App\Services\PetugasStatsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PasienController extends Controller
{
    protected PetugasDataService $dataService;
    protected PetugasStatsService $statsService;
    
    public function __construct(PetugasDataService $dataService, PetugasStatsService $statsService)
    {
        $this->dataService = $dataService;
        $this->statsService = $statsService;
    }
    
    /**
     * Display enhanced patient list view
     */
    public function index()
    {
        return view('petugas.enhanced.pasien.index');
    }
    
    /**
     * Get patient data for DataTable via AJAX
     */
    public function getData(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'jenis_kelamin', 'date_from', 'date_to', 
                'age_min', 'age_max', 'sort_by', 'sort_order'
            ]);
            
            $perPage = $request->get('per_page', 25);
            $patients = $this->dataService->getPasienList($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $patients->items(),
                'pagination' => [
                    'current_page' => $patients->currentPage(),
                    'last_page' => $patients->lastPage(),
                    'per_page' => $patients->perPage(),
                    'total' => $patients->total(),
                    'from' => $patients->firstItem(),
                    'to' => $patients->lastItem(),
                ],
                'filters_applied' => $filters
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data pasien: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show create patient form
     */
    public function create()
    {
        $formData = $this->dataService->getFormData();
        return view('petugas.enhanced.pasien.create', compact('formData'));
    }
    
    /**
     * Store new patient
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_pasien' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date|before:today',
            'alamat' => 'required|string|max:500',
            'nomor_telepon' => 'nullable|string|max:20',
            'nomor_pasien' => 'nullable|string|max:50|unique:pasien,nomor_pasien',
        ]);
        
        $result = $this->dataService->createPasien($validated);
        
        return response()->json($result, $result['success'] ? 201 : 400);
    }
    
    /**
     * Show patient details
     */
    public function show(int $id)
    {
        $result = $this->dataService->getPasienDetail($id);
        
        if (!$result['success']) {
            abort(404);
        }
        
        return view('petugas.enhanced.pasien.show', [
            'pasien' => $result['data'],
            'stats' => $result['stats']
        ]);
    }
    
    /**
     * Show edit patient form
     */
    public function edit(int $id)
    {
        $result = $this->dataService->getPasienDetail($id);
        
        if (!$result['success']) {
            abort(404);
        }
        
        $formData = $this->dataService->getFormData();
        
        return view('petugas.enhanced.pasien.edit', [
            'pasien' => $result['data'],
            'formData' => $formData
        ]);
    }
    
    /**
     * Update patient data
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'nama_pasien' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date|before:today',
            'alamat' => 'required|string|max:500',
            'nomor_telepon' => 'nullable|string|max:20',
        ]);
        
        $result = $this->dataService->updatePasien($id, $validated);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
    
    /**
     * Delete patient (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->dataService->getPasienDetail($id);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pasien tidak ditemukan'
                ], 404);
            }
            
            $result['data']->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Pasien berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pasien: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk delete patients
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:pasien,id'
        ]);
        
        $result = $this->dataService->bulkDeletePasien($validated['ids']);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
    
    /**
     * Export patient data
     */
    public function export(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'jenis_kelamin', 'date_from', 'date_to', 'age_min', 'age_max'
        ]);
        
        $format = $request->get('format', 'excel');
        
        $result = $this->dataService->exportData('pasien', $filters, $format);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    }
    
    /**
     * Get patient quick stats for dashboard cards
     */
    public function getQuickStats(): JsonResponse
    {
        try {
            $stats = $this->dataService->getDashboardSummary();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'today_patients' => $stats['today_stats']['pasien_count'] ?? 0,
                    'month_patients' => $stats['month_stats']['pasien_count'] ?? 0,
                    'pending_validations' => $stats['pending_validations']['tindakan'] ?? 0,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Search patients for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $limit = $request->get('limit', 10);
            
            if (strlen($query) < 2) {
                return response()->json(['success' => true, 'data' => []]);
            }
            
            $patients = \App\Models\Pasien::where('input_by', auth()->id())
                ->where(function($q) use ($query) {
                    $q->where('nama_pasien', 'like', "%{$query}%")
                      ->orWhere('nomor_pasien', 'like', "%{$query}%");
                })
                ->select('id', 'nama_pasien', 'nomor_pasien', 'jenis_kelamin')
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $patients
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencari pasien: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get patient timeline for detailed view
     */
    public function getTimeline(int $id): JsonResponse
    {
        try {
            $result = $this->dataService->getPasienDetail($id);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pasien tidak ditemukan'
                ], 404);
            }
            
            $pasien = $result['data'];
            $timeline = [];
            
            // Add registration event
            $timeline[] = [
                'date' => $pasien->created_at->format('Y-m-d H:i:s'),
                'type' => 'registration',
                'title' => 'Pendaftaran Pasien',
                'description' => 'Pasien terdaftar dalam sistem',
                'icon' => 'user-plus',
                'color' => 'blue'
            ];
            
            // Add procedure events
            foreach ($pasien->tindakan as $tindakan) {
                $timeline[] = [
                    'date' => $tindakan->tanggal_tindakan,
                    'type' => 'procedure',
                    'title' => $tindakan->jenisTindakan->nama_tindakan ?? 'Tindakan',
                    'description' => 'Biaya: Rp ' . number_format($tindakan->tarif, 0, ',', '.'),
                    'icon' => 'medical-bag',
                    'color' => 'green',
                    'status' => $tindakan->status_validasi
                ];
            }
            
            // Sort by date descending
            usort($timeline, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            return response()->json([
                'success' => true,
                'data' => $timeline
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat timeline: ' . $e->getMessage()
            ], 500);
        }
    }
}
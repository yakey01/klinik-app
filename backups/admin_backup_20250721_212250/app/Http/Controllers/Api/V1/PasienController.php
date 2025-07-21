<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Pasien;
use App\Http\Resources\V1\PasienResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PasienController extends BaseApiController
{
    /**
     * Display a listing of patients
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Get pagination parameters
            $pagination = $this->getPaginationParams($request);

            // Build query
            $query = Pasien::query();

            // Apply filters
            $allowedFilters = [
                'search' => ['type' => 'search'],
                'jenis_kelamin' => 'jenis_kelamin',
                'age_min' => ['type' => 'age_min'],
                'age_max' => ['type' => 'age_max'],
                'created_from' => ['type' => 'date_range', 'column' => 'created_at'],
                'created_to' => ['type' => 'date_range', 'column' => 'created_at'],
            ];

            $query = $this->applyPasienFilters($query, $request);

            // Apply sorting
            $allowedSorts = ['nama_pasien', 'nomor_pasien', 'tanggal_lahir', 'created_at'];
            $query = $this->applySorting($query, $request, $allowedSorts, 'created_at', 'desc');

            // Include relationships if requested
            if ($request->has('include')) {
                $includes = explode(',', $request->get('include'));
                $allowedIncludes = ['tindakans', 'tindakans.jenisTindakan'];
                $validIncludes = array_intersect($includes, $allowedIncludes);
                if (!empty($validIncludes)) {
                    $query->with($validIncludes);
                }
            }

            // Get paginated results
            $patients = $query->paginate($pagination['per_page']);

            // Transform data based on view type
            $viewType = $request->get('view', 'default'); // default, minimal, mobile
            $transformedData = $patients->through(function ($patient) use ($viewType, $request) {
                $resource = new PasienResource($patient);
                return match ($viewType) {
                    'minimal' => $resource->toArrayMinimal($request),
                    'mobile' => $resource->toArrayMobile($request),
                    default => $resource->toArray($request),
                };
            });

            $this->logApiActivity('patients.index', ['count' => $patients->total()]);

            return $this->paginatedResponse($transformedData, 'Daftar pasien berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching patients');
        }
    }

    /**
     * Store a newly created patient
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
                'nama_pasien' => 'required|string|max:255',
                'tanggal_lahir' => 'required|date|before:today',
                'jenis_kelamin' => 'required|in:L,P',
                'alamat' => 'required|string|max:500',
                'nomor_telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'pekerjaan' => 'nullable|string|max:100',
                'status_pernikahan' => 'nullable|in:belum_menikah,menikah,cerai,janda,duda',
                'golongan_darah' => 'nullable|in:A,B,AB,O',
                'alergi' => 'nullable|string|max:500',
                'kontak_darurat_nama' => 'nullable|string|max:255',
                'kontak_darurat_hubungan' => 'nullable|string|max:100',
                'kontak_darurat_telepon' => 'nullable|string|max:20',
                'catatan_medis' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            // Generate nomor pasien
            $validated['nomor_pasien'] = $this->generateNomorPasien();

            // Create patient
            $patient = Pasien::create($validated);

            DB::commit();

            $this->logApiActivity('patients.store', ['patient_id' => $patient->id]);

            return $this->successResponse(
                new PasienResource($patient),
                'Pasien berhasil ditambahkan',
                201
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error creating patient');
        }
    }

    /**
     * Display the specified patient
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find patient
            $query = Pasien::where('id', $id);

            // Include relationships if requested
            if ($request->has('include')) {
                $includes = explode(',', $request->get('include'));
                $allowedIncludes = ['tindakans', 'tindakans.jenisTindakan', 'tindakans.dokter'];
                $validIncludes = array_intersect($includes, $allowedIncludes);
                if (!empty($validIncludes)) {
                    $query->with($validIncludes);
                }
            }

            $patient = $query->firstOrFail();

            $this->logApiActivity('patients.show', ['patient_id' => $id]);

            return $this->successResponse(
                new PasienResource($patient),
                'Detail pasien berhasil dimuat'
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching patient');
        }
    }

    /**
     * Update the specified patient
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find patient
            $patient = Pasien::findOrFail($id);

            // Validate request
            $validated = $request->validate([
                'nama_pasien' => 'sometimes|required|string|max:255',
                'tanggal_lahir' => 'sometimes|required|date|before:today',
                'jenis_kelamin' => 'sometimes|required|in:L,P',
                'alamat' => 'sometimes|required|string|max:500',
                'nomor_telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'pekerjaan' => 'nullable|string|max:100',
                'status_pernikahan' => 'nullable|in:belum_menikah,menikah,cerai,janda,duda',
                'golongan_darah' => 'nullable|in:A,B,AB,O',
                'alergi' => 'nullable|string|max:500',
                'kontak_darurat_nama' => 'nullable|string|max:255',
                'kontak_darurat_hubungan' => 'nullable|string|max:100',
                'kontak_darurat_telepon' => 'nullable|string|max:20',
                'catatan_medis' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            // Update patient
            $patient->update($validated);

            DB::commit();

            $this->logApiActivity('patients.update', ['patient_id' => $id]);

            return $this->successResponse(
                new PasienResource($patient->fresh()),
                'Pasien berhasil diperbarui'
            );

        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->errorResponse('Data tidak valid', 422, $e->errors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error updating patient');
        }
    }

    /**
     * Remove the specified patient
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['admin']); // Only admin can delete
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            // Find patient
            $patient = Pasien::findOrFail($id);

            // Check if patient has any tindakan
            if ($patient->tindakans()->count() > 0) {
                return $this->errorResponse(
                    'Tidak dapat menghapus pasien yang memiliki riwayat tindakan',
                    400
                );
            }

            DB::beginTransaction();

            $patient->delete();

            DB::commit();

            $this->logApiActivity('patients.destroy', ['patient_id' => $id]);

            return $this->successResponse(null, 'Pasien berhasil dihapus');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Pasien tidak ditemukan', 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleApiException($e, 'Error deleting patient');
        }
    }

    /**
     * Search patients
     */
    public function search(Request $request): JsonResponse
    {
        try {
            // Validate permissions
            $permissionCheck = $this->validateApiPermissions(['petugas', 'admin']);
            if ($permissionCheck !== true) {
                return $permissionCheck;
            }

            $query = $request->get('q', '');
            if (strlen($query) < 2) {
                return $this->errorResponse('Query pencarian minimal 2 karakter', 400);
            }

            $patients = Pasien::where(function ($q) use ($query) {
                $q->where('nama_pasien', 'like', "%{$query}%")
                  ->orWhere('nomor_pasien', 'like', "%{$query}%")
                  ->orWhere('nomor_telepon', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

            $transformedData = PasienResource::collection($patients)
                ->map(function ($resource) use ($request) {
                    return $resource->toArrayMinimal($request);
                });

            return $this->successResponse(
                $transformedData,
                'Hasil pencarian pasien'
            );

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error searching patients');
        }
    }

    /**
     * Get patient statistics
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

            $stats = $this->calculatePatientStatistics($period);

            return $this->successResponse($stats, 'Statistik pasien berhasil dimuat');

        } catch (\Exception $e) {
            return $this->handleApiException($e, 'Error fetching patient statistics');
        }
    }

    /**
     * Apply patient-specific filters
     */
    private function applyPasienFilters($query, Request $request)
    {
        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pasien', 'like', "%{$search}%")
                  ->orWhere('nomor_pasien', 'like', "%{$search}%")
                  ->orWhere('nomor_telepon', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        // Gender filter
        if ($gender = $request->get('jenis_kelamin')) {
            $query->where('jenis_kelamin', $gender);
        }

        // Age filters
        if ($ageMin = $request->get('age_min')) {
            $maxBirthDate = now()->subYears($ageMin)->endOfYear();
            $query->where('tanggal_lahir', '<=', $maxBirthDate);
        }

        if ($ageMax = $request->get('age_max')) {
            $minBirthDate = now()->subYears($ageMax + 1)->startOfYear();
            $query->where('tanggal_lahir', '>=', $minBirthDate);
        }

        // Date range filters
        if ($dateFrom = $request->get('created_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('created_to')) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query;
    }

    /**
     * Generate unique patient number
     */
    private function generateNomorPasien(): string
    {
        $prefix = 'P';
        $year = date('Y');
        $month = date('m');
        
        // Get the latest patient number for this month
        $latestPatient = Pasien::where('nomor_pasien', 'like', "{$prefix}{$year}{$month}%")
                             ->orderBy('nomor_pasien', 'desc')
                             ->first();
        
        if ($latestPatient) {
            $lastNumber = (int) substr($latestPatient->nomor_pasien, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate patient statistics
     */
    private function calculatePatientStatistics(string $period): array
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

        $total = Pasien::count();
        $newInPeriod = Pasien::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $genderStats = Pasien::selectRaw('jenis_kelamin, COUNT(*) as count')
                           ->groupBy('jenis_kelamin')
                           ->pluck('count', 'jenis_kelamin')
                           ->toArray();

        $ageGroups = $this->calculateAgeGroups();

        return [
            'period' => $period,
            'total_patients' => $total,
            'new_in_period' => $newInPeriod,
            'gender_distribution' => [
                'male' => $genderStats['L'] ?? 0,
                'female' => $genderStats['P'] ?? 0,
            ],
            'age_groups' => $ageGroups,
            'period_dates' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
        ];
    }

    /**
     * Calculate age group distribution
     */
    private function calculateAgeGroups(): array
    {
        $ageGroups = [
            '0-10' => 0,
            '11-20' => 0,
            '21-30' => 0,
            '31-40' => 0,
            '41-50' => 0,
            '51-60' => 0,
            '60+' => 0,
        ];

        $patients = Pasien::whereNotNull('tanggal_lahir')->get(['tanggal_lahir']);

        foreach ($patients as $patient) {
            $age = \Carbon\Carbon::parse($patient->tanggal_lahir)->age;
            
            if ($age <= 10) {
                $ageGroups['0-10']++;
            } elseif ($age <= 20) {
                $ageGroups['11-20']++;
            } elseif ($age <= 30) {
                $ageGroups['21-30']++;
            } elseif ($age <= 40) {
                $ageGroups['31-40']++;
            } elseif ($age <= 50) {
                $ageGroups['41-50']++;
            } elseif ($age <= 60) {
                $ageGroups['51-60']++;
            } else {
                $ageGroups['60+']++;
            }
        }

        return $ageGroups;
    }
}
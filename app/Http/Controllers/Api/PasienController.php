<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Helpers\ValidationHelper;
use App\Exceptions\BusinessLogicException;
use App\Traits\HandlesErrors;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PasienController extends Controller
{
    use HandlesErrors;

    public function index(Request $request): JsonResponse
    {
        return $this->wrapOperation(function() use ($request) {
            $query = Pasien::query();
            
            // Apply search filters
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('no_rekam_medis', 'like', "%{$search}%");
                });
            }
            
            // Apply filters
            if ($request->has('jenis_kelamin')) {
                $query->where('jenis_kelamin', $request->get('jenis_kelamin'));
            }
            
            $pasiens = $query->paginate($request->get('per_page', 15));
            
            return response()->json([
                'success' => true,
                'data' => $pasiens,
                'message' => 'Data pasien berhasil diambil',
            ]);
        }, 'get_patients');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->validateAndExecute(
            $request->all(),
            ValidationHelper::getCommonRules()['pasien'],
            function($validated) {
                $pasien = Pasien::create($validated);
                
                return response()->json([
                    'success' => true,
                    'data' => $pasien,
                    'message' => 'Pasien berhasil ditambahkan',
                ], 201);
            },
            'Pasien berhasil ditambahkan'
        );
    }

    public function show(string $id): JsonResponse
    {
        return $this->wrapOperation(function() use ($id) {
            $pasien = Pasien::find($id);
            
            if (!$pasien) {
                throw BusinessLogicException::pasienNotFound($id);
            }
            
            return response()->json([
                'success' => true,
                'data' => $pasien,
                'message' => 'Data pasien berhasil diambil',
            ]);
        }, 'get_patient');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return $this->wrapOperation(function() use ($request, $id) {
            $pasien = Pasien::find($id);
            
            if (!$pasien) {
                throw BusinessLogicException::pasienNotFound($id);
            }
            
            $rules = ValidationHelper::getCommonRules()['pasien'];
            $rules['no_rekam_medis'] = 'required|string|max:20|unique:pasien,no_rekam_medis,' . $id;
            
            $validated = ValidationHelper::validate(
                $request->all(),
                $rules,
                ValidationHelper::getMessages(),
                ValidationHelper::getAttributes()
            );
            
            $pasien->update($validated);
            
            return response()->json([
                'success' => true,
                'data' => $pasien,
                'message' => 'Pasien berhasil diperbarui',
            ]);
        }, 'update_patient');
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->wrapOperation(function() use ($id) {
            $pasien = Pasien::find($id);
            
            if (!$pasien) {
                throw BusinessLogicException::pasienNotFound($id);
            }
            
            // Check if patient has associated records
            if ($pasien->tindakan()->exists()) {
                throw BusinessLogicException::recordInUse('pasien', $id);
            }
            
            $pasien->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Pasien berhasil dihapus',
            ]);
        }, 'delete_patient');
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        return $this->wrapOperation(function() use ($request) {
            $validated = ValidationHelper::validate($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer|exists:pasien,id',
            ]);
            
            $deleted = 0;
            $errors = [];
            
            foreach ($validated['ids'] as $id) {
                try {
                    $pasien = Pasien::find($id);
                    if ($pasien && !$pasien->tindakan()->exists()) {
                        $pasien->delete();
                        $deleted++;
                    } else {
                        $errors[] = "Pasien ID {$id} tidak dapat dihapus karena masih memiliki tindakan terkait";
                    }
                } catch (Exception $e) {
                    $errors[] = "Error menghapus pasien ID {$id}: " . $e->getMessage();
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'deleted' => $deleted,
                    'errors' => $errors,
                ],
                'message' => "Berhasil menghapus {$deleted} pasien",
            ]);
        }, 'bulk_delete_patients');
    }

    public function search(Request $request): JsonResponse
    {
        return $this->wrapOperation(function() use ($request) {
            $validated = ValidationHelper::validate($request->all(), [
                'query' => 'required|string|min:2',
                'field' => 'nullable|string|in:nama,no_rekam_medis,alamat',
                'limit' => 'nullable|integer|min:1|max:50',
            ]);
            
            $query = Pasien::query();
            $searchTerm = $validated['query'];
            $field = $validated['field'] ?? null;
            $limit = $validated['limit'] ?? 10;
            
            if ($field) {
                $query->where($field, 'like', "%{$searchTerm}%");
            } else {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('nama', 'like', "%{$searchTerm}%")
                      ->orWhere('no_rekam_medis', 'like', "%{$searchTerm}%")
                      ->orWhere('alamat', 'like', "%{$searchTerm}%");
                });
            }
            
            $results = $query->limit($limit)->get();
            
            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Hasil pencarian berhasil diambil',
            ]);
        }, 'search_patients');
    }
}
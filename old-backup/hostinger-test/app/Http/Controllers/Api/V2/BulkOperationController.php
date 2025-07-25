<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\BulkOperationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BulkOperationController extends Controller
{
    protected BulkOperationService $bulkService;

    public function __construct(BulkOperationService $bulkService)
    {
        $this->bulkService = $bulkService;
    }

    /**
     * Bulk create records
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'model' => 'required|string',
                'data' => 'required|array|min:1',
                'data.*' => 'required|array',
                'options' => 'sometimes|array',
                'options.batch_size' => 'sometimes|integer|min:1|max:1000',
                'options.validate' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $modelClass = $this->getModelClass($request->input('model'));
            $data = $request->input('data');
            $options = $request->input('options', []);

            $result = $this->bulkService->bulkCreate($modelClass, $data, $options);

            return response()->json([
                'success' => true,
                'message' => 'Bulk create operation completed',
                'data' => $result,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk create operation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk update records
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'model' => 'required|string',
                'updates' => 'required|array|min:1',
                'updates.*' => 'required|array',
                'key_field' => 'sometimes|string',
                'options' => 'sometimes|array',
                'options.batch_size' => 'sometimes|integer|min:1|max:1000',
                'options.validate' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $modelClass = $this->getModelClass($request->input('model'));
            $updates = $request->input('updates');
            $keyField = $request->input('key_field', 'id');
            $options = $request->input('options', []);

            $result = $this->bulkService->bulkUpdate($modelClass, $updates, $keyField, $options);

            return response()->json([
                'success' => true,
                'message' => 'Bulk update operation completed',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk update operation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete records
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'model' => 'required|string',
                'ids' => 'required|array|min:1',
                'ids.*' => 'required|integer',
                'options' => 'sometimes|array',
                'options.soft_delete' => 'sometimes|boolean',
                'options.batch_size' => 'sometimes|integer|min:1|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $modelClass = $this->getModelClass($request->input('model'));
            $ids = $request->input('ids');
            $options = $request->input('options', []);

            $result = $this->bulkService->bulkDelete($modelClass, $ids, $options);

            return response()->json([
                'success' => true,
                'message' => 'Bulk delete operation completed',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk delete operation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate bulk data without saving
     */
    public function bulkValidate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'model' => 'required|string',
                'data' => 'required|array|min:1',
                'data.*' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $modelClass = $this->getModelClass($request->input('model'));
            $data = $request->input('data');

            $result = $this->bulkService->bulkValidate($modelClass, $data);

            return response()->json([
                'success' => true,
                'message' => 'Bulk validation completed',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk validation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get bulk operation statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'model' => 'sometimes|string',
                'days' => 'sometimes|integer|min:1|max:365',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $modelClass = $request->has('model') ? $this->getModelClass($request->input('model')) : null;
            $days = $request->input('days', 30);

            $stats = $this->bulkService->getBulkOperationStats($modelClass, $days);

            return response()->json([
                'success' => true,
                'message' => 'Bulk operation statistics retrieved',
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get supported models for bulk operations
     */
    public function getSupportedModels(): JsonResponse
    {
        $supportedModels = [
            'PendapatanHarian' => 'Daily Revenue',
            'PengeluaranHarian' => 'Daily Expenses',
            'JumlahPasienHarian' => 'Daily Patient Count',
            'Tindakan' => 'Medical Procedures',
            'Pasien' => 'Patients',
            'Pegawai' => 'Employees',
            'Dokter' => 'Doctors',
            'Attendance' => 'Attendance Records',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Supported models retrieved',
            'data' => $supportedModels,
        ]);
    }

    /**
     * Bulk import from CSV/JSON file
     */
    public function bulkImport(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'model' => 'required|string',
                'file' => 'required|file|mimes:json,csv|max:10240', // 10MB max
                'options' => 'sometimes|array',
                'options.validate' => 'sometimes|boolean',
                'options.skip_duplicates' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $modelClass = $this->getModelClass($request->input('model'));
            $file = $request->file('file');
            $options = $request->input('options', []);

            // Parse file based on extension
            $extension = $file->getClientOriginalExtension();
            $content = file_get_contents($file->getRealPath());

            if ($extension === 'json') {
                $data = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
                }
            } elseif ($extension === 'csv') {
                $lines = explode("\n", $content);
                $headers = str_getcsv(array_shift($lines));
                $data = array_map(function ($line) use ($headers) {
                    $values = str_getcsv($line);
                    return array_combine($headers, $values);
                }, array_filter($lines));
            } else {
                throw new \Exception('Unsupported file format');
            }

            if (empty($data)) {
                throw new \Exception('No data found in file');
            }

            $result = $this->bulkService->bulkCreate($modelClass, $data, $options);

            return response()->json([
                'success' => true,
                'message' => 'Bulk import completed',
                'data' => $result,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get model class from string
     */
    protected function getModelClass(string $model): string
    {
        $modelClass = "App\\Models\\{$model}";
        
        if (!class_exists($modelClass)) {
            throw new \Exception("Model {$model} not found");
        }
        
        return $modelClass;
    }
}
<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\AuditLog;
use Exception;
use App\Traits\HandlesErrors;
use App\Exceptions\ValidationException;
use App\Exceptions\SystemException;

class BulkOperationService
{
    use HandlesErrors;
    protected array $supportedModels = [
        'PendapatanHarian',
        'PengeluaranHarian', 
        'JumlahPasienHarian',
        'Tindakan',
        'Pasien',
        'Pegawai',
        'Dokter',
        'Attendance',
    ];

    protected array $batchSizes = [
        'default' => 100,
        'large' => 50,
        'xlarge' => 25,
    ];

    public function bulkCreate(string $modelClass, array $data, array $options = []): array
    {
        $this->validateModel($modelClass);
        
        DB::beginTransaction();
        
        try {
            $results = [];
            $errors = [];
            $batchSize = $options['batch_size'] ?? $this->getBatchSize($data);
            $validateData = $options['validate'] ?? true;
            
            $batches = array_chunk($data, $batchSize);
            
            foreach ($batches as $batchIndex => $batch) {
                $batchResults = $this->processBatch(
                    'create',
                    $modelClass,
                    $batch,
                    $validateData,
                    $batchIndex
                );
                
                $results = array_merge($results, $batchResults['success']);
                $errors = array_merge($errors, $batchResults['errors']);
            }
            
            $this->logBulkOperation('create', $modelClass, count($results), count($errors));
            
            DB::commit();
            
            return [
                'success' => true,
                'created' => count($results),
                'errors' => count($errors),
                'data' => $results,
                'error_details' => $errors,
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Bulk create operation failed', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    public function bulkUpdate(string $modelClass, array $updates, string $keyField = 'id', array $options = []): array
    {
        $this->validateModel($modelClass);
        
        DB::beginTransaction();
        
        try {
            $results = [];
            $errors = [];
            $batchSize = $options['batch_size'] ?? $this->getBatchSize($updates);
            $validateData = $options['validate'] ?? true;
            
            $batches = array_chunk($updates, $batchSize);
            
            foreach ($batches as $batchIndex => $batch) {
                $batchResults = $this->processBatchUpdate(
                    $modelClass,
                    $batch,
                    $keyField,
                    $validateData,
                    $batchIndex
                );
                
                $results = array_merge($results, $batchResults['success']);
                $errors = array_merge($errors, $batchResults['errors']);
            }
            
            $this->logBulkOperation('update', $modelClass, count($results), count($errors));
            
            DB::commit();
            
            return [
                'success' => true,
                'updated' => count($results),
                'errors' => count($errors),
                'data' => $results,
                'error_details' => $errors,
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Bulk update operation failed', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    public function bulkDelete(string $modelClass, array $ids, array $options = []): array
    {
        $this->validateModel($modelClass);
        
        DB::beginTransaction();
        
        try {
            $softDelete = $options['soft_delete'] ?? true;
            $batchSize = $options['batch_size'] ?? $this->getBatchSize($ids);
            
            $deleted = 0;
            $errors = [];
            
            $batches = array_chunk($ids, $batchSize);
            
            foreach ($batches as $batchIndex => $batch) {
                try {
                    if ($softDelete && method_exists($modelClass, 'trashed')) {
                        $batchDeleted = $modelClass::whereIn('id', $batch)->delete();
                    } else {
                        $batchDeleted = $modelClass::whereIn('id', $batch)->forceDelete();
                    }
                    
                    $deleted += $batchDeleted;
                    
                } catch (Exception $e) {
                    $errors[] = [
                        'batch' => $batchIndex,
                        'ids' => $batch,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $this->logBulkOperation('delete', $modelClass, $deleted, count($errors));
            
            DB::commit();
            
            return [
                'success' => true,
                'deleted' => $deleted,
                'errors' => count($errors),
                'error_details' => $errors,
            ];
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete operation failed', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    protected function processBatch(
        string $operation,
        string $modelClass,
        array $batch,
        bool $validate,
        int $batchIndex
    ): array {
        $results = [];
        $errors = [];
        
        foreach ($batch as $index => $item) {
            try {
                $model = new $modelClass();
                
                if ($validate) {
                    $validator = $this->validateModelData($model, $item);
                    if ($validator->fails()) {
                        $errors[] = [
                            'batch' => $batchIndex,
                            'index' => $index,
                            'data' => $item,
                            'errors' => $validator->errors()->toArray()
                        ];
                        continue;
                    }
                }
                
                $model->fill($item);
                $model->save();
                
                $results[] = $model->toArray();
                
            } catch (Exception $e) {
                $errors[] = [
                    'batch' => $batchIndex,
                    'index' => $index,
                    'data' => $item,
                    'errors' => ['general' => [$e->getMessage()]]
                ];
            }
        }
        
        return [
            'success' => $results,
            'errors' => $errors
        ];
    }

    protected function processBatchUpdate(
        string $modelClass,
        array $batch,
        string $keyField,
        bool $validate,
        int $batchIndex
    ): array {
        $results = [];
        $errors = [];
        
        foreach ($batch as $index => $item) {
            try {
                if (!isset($item[$keyField])) {
                    $errors[] = [
                        'batch' => $batchIndex,
                        'index' => $index,
                        'data' => $item,
                        'errors' => ['general' => ["Missing key field: {$keyField}"]]
                    ];
                    continue;
                }
                
                $model = $modelClass::find($item[$keyField]);
                if (!$model) {
                    $errors[] = [
                        'batch' => $batchIndex,
                        'index' => $index,
                        'data' => $item,
                        'errors' => ['general' => ["Record not found with {$keyField}: {$item[$keyField]}"]]
                    ];
                    continue;
                }
                
                if ($validate) {
                    $validator = $this->validateModelData($model, $item);
                    if ($validator->fails()) {
                        $errors[] = [
                            'batch' => $batchIndex,
                            'index' => $index,
                            'data' => $item,
                            'errors' => $validator->errors()->toArray()
                        ];
                        continue;
                    }
                }
                
                $model->fill($item);
                $model->save();
                
                $results[] = $model->toArray();
                
            } catch (Exception $e) {
                $errors[] = [
                    'batch' => $batchIndex,
                    'index' => $index,
                    'data' => $item,
                    'errors' => ['general' => [$e->getMessage()]]
                ];
            }
        }
        
        return [
            'success' => $results,
            'errors' => $errors
        ];
    }

    protected function validateModel(string $modelClass): void
    {
        if (!class_exists($modelClass)) {
            throw new Exception("Model class {$modelClass} does not exist");
        }
        
        $modelName = class_basename($modelClass);
        if (!in_array($modelName, $this->supportedModels)) {
            throw new Exception("Model {$modelName} is not supported for bulk operations");
        }
    }

    protected function validateModelData(Model $model, array $data)
    {
        $fillable = $model->getFillable();
        $rules = [];
        
        foreach ($fillable as $field) {
            if (isset($data[$field])) {
                $rules[$field] = 'required';
            }
        }
        
        if (method_exists($model, 'getBulkValidationRules')) {
            $rules = array_merge($rules, $model->getBulkValidationRules());
        }
        
        return Validator::make($data, $rules);
    }

    protected function getBatchSize(array $data): int
    {
        $count = count($data);
        
        if ($count > 1000) {
            return $this->batchSizes['xlarge'];
        } elseif ($count > 500) {
            return $this->batchSizes['large'];
        }
        
        return $this->batchSizes['default'];
    }

    protected function logBulkOperation(string $operation, string $modelClass, int $success, int $errors): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => "bulk_{$operation}",
                'model_type' => $modelClass,
                'model_id' => null,
                'changes' => json_encode([
                    'operation' => $operation,
                    'model' => class_basename($modelClass),
                    'success_count' => $success,
                    'error_count' => $errors,
                    'total_processed' => $success + $errors,
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'risk_level' => $errors > 0 ? 'medium' : 'low',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log bulk operation', [
                'error' => $e->getMessage(),
                'operation' => $operation,
                'model' => $modelClass
            ]);
        }
    }
}
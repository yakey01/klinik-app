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
use App\Traits\SafeTransaction;
use App\Exceptions\ValidationException;
use App\Exceptions\SystemException;

class BulkOperationService
{
    use HandlesErrors, SafeTransaction;
    protected array $supportedModels = [
        'App\Models\PendapatanHarian',
        'App\Models\PengeluaranHarian', 
        'App\Models\JumlahPasienHarian',
        'App\Models\Tindakan',
        'App\Models\Pasien',
        'App\Models\Pegawai',
        'App\Models\Dokter',
        'App\Models\Attendance',
        'App\Models\User',
    ];

    protected array $batchSizes = [
        'default' => 100,
        'large' => 50,
        'xlarge' => 25,
    ];

    public function bulkCreate(string $modelClass, array $data, array $options = []): array
    {
        $this->validateModel($modelClass);
        
        return $this->safeTransaction(function() use ($modelClass, $data, $options) {
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
            
            return [
                'success' => true,
                'created' => count($results),
                'failed' => count($errors),
                'errors' => $errors,
                'data' => $results,
                'error_details' => $errors,
            ];
        });
    }

    public function bulkUpdate(string $modelClass, array $updates, string $keyField = 'id', array $options = []): array
    {
        $this->validateModel($modelClass);
        
        return $this->safeTransaction(function() use ($modelClass, $updates, $keyField, $options) {
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
            
            return [
                'success' => true,
                'updated' => count($results),
                'failed' => count($errors),
                'errors' => $errors,
                'data' => $results,
                'error_details' => $errors,
            ];
        });
    }

    public function bulkDelete(string $modelClass, array $ids, array $options = []): array
    {
        $this->validateModel($modelClass);
        
        return $this->safeTransaction(function() use ($modelClass, $ids, $options) {
            $softDelete = $options['soft_delete'] ?? true;
            $batchSize = $options['batch_size'] ?? $this->getBatchSize($ids);
            
            $deleted = 0;
            $errors = [];
            
            $batches = array_chunk($ids, $batchSize);
            
            foreach ($batches as $batchIndex => $batch) {
                try {
                    // Check which records exist before deletion
                    $existingIds = $modelClass::whereIn('id', $batch)->pluck('id')->toArray();
                    $nonExistingIds = array_diff($batch, $existingIds);
                    
                    if ($softDelete && in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($modelClass))) {
                        $batchDeleted = $modelClass::whereIn('id', $existingIds)->delete();
                    } else {
                        $batchDeleted = $modelClass::whereIn('id', $existingIds)->delete();
                    }
                    
                    $deleted += $batchDeleted;
                    
                    // Track non-existing IDs as errors
                    foreach ($nonExistingIds as $id) {
                        $errors[] = [
                            'batch' => $batchIndex,
                            'id' => $id,
                            'error' => "Record with ID {$id} not found"
                        ];
                    }
                    
                } catch (Exception $e) {
                    $errors[] = [
                        'batch' => $batchIndex,
                        'ids' => $batch,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $this->logBulkOperation('delete', $modelClass, $deleted, count($errors));
            
            return [
                'success' => true,
                'deleted' => $deleted,
                'failed' => count($errors),
                'errors' => $errors,
                'error_details' => $errors,
            ];
        });
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
                            'error' => $validator->errors()->toArray()
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
                    'error' => $e->getMessage()
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
                        'error' => "Missing key field: {$keyField}"
                    ];
                    continue;
                }
                
                $model = $modelClass::find($item[$keyField]);
                if (!$model) {
                    $errors[] = [
                        'batch' => $batchIndex,
                        'index' => $index,
                        'data' => $item,
                        'error' => "Record not found with {$keyField}: {$item[$keyField]}"
                    ];
                    continue;
                }
                
                if ($validate) {
                    $validator = $this->validateModelDataForUpdate($model, $item);
                    if ($validator->fails()) {
                        $errors[] = [
                            'batch' => $batchIndex,
                            'index' => $index,
                            'data' => $item,
                            'error' => $validator->errors()->toArray()
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
                    'error' => $e->getMessage()
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
        
        if (!in_array($modelClass, $this->supportedModels)) {
            throw new Exception("Model {$modelClass} is not supported for bulk operations");
        }
    }

    protected function validateModelData(Model $model, array $data)
    {
        $rules = [];
        
        // Only use custom validation rules if they exist
        if (method_exists($model, 'getBulkValidationRules')) {
            $rules = $model->getBulkValidationRules();
        }
        
        // If no custom rules, use minimal validation
        if (empty($rules)) {
            // Only validate that required database fields are present
            // Let the database handle the actual constraints
            $rules = [];
        }
        
        return Validator::make($data, $rules);
    }

    protected function validateModelDataForUpdate(Model $model, array $data)
    {
        $rules = [];
        
        // Only use custom validation rules if they exist
        if (method_exists($model, 'getBulkValidationRules')) {
            $rules = $model->getBulkValidationRules();
            
            // Modify unique rules to exclude current record
            foreach ($rules as $field => $rule) {
                if (strpos($rule, 'unique:') !== false) {
                    $rules[$field] = $rule . ',' . $model->getKey();
                }
            }
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
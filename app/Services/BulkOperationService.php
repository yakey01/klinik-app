<?php

namespace App\Services;

use App\Models\BulkOperation;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BulkOperationService
{
    /**
     * Create a new bulk operation
     */
    public function createOperation(
        User $user,
        string $type,
        string $modelType,
        array $operationData,
        array $filters = [],
        int $totalRecords = 0
    ): BulkOperation {
        $operation = BulkOperation::createOperation(
            $user,
            $type,
            $modelType,
            $operationData,
            $filters,
            $totalRecords
        );

        // Log the operation creation
        AuditLog::logSystem(
            'bulk_operation_created',
            $user,
            "Bulk operation created: {$type} on {$modelType}",
            [
                'operation_id' => $operation->id,
                'operation_type' => $type,
                'model_type' => $modelType,
                'total_records' => $totalRecords,
            ]
        );

        return $operation;
    }

    /**
     * Execute bulk update operation
     */
    public function executeBulkUpdate(BulkOperation $operation): void
    {
        $operation->start();

        try {
            $modelClass = $operation->model_type;
            $updateData = $operation->operation_data['update_data'] ?? [];
            $filters = $operation->filters;

            if (empty($updateData)) {
                throw new \Exception('No update data provided');
            }

            // Build query with filters
            $query = $this->buildQuery($modelClass, $filters);
            
            // Get total count if not set
            if ($operation->total_records === 0) {
                $totalCount = $query->count();
                $operation->update(['total_records' => $totalCount]);
            }

            // Process in chunks to avoid memory issues
            $chunkSize = 100;
            $processed = 0;
            $successful = 0;
            $failed = 0;

            $query->chunk($chunkSize, function ($records) use ($operation, $updateData, &$processed, &$successful, &$failed) {
                foreach ($records as $record) {
                    try {
                        $oldValues = $record->toArray();
                        $record->update($updateData);
                        
                        // Log individual update
                        AuditLog::logSystem(
                            'bulk_update_record',
                            $operation->user,
                            "Record updated via bulk operation",
                            [
                                'operation_id' => $operation->id,
                                'record_id' => $record->id,
                                'model_type' => $operation->model_type,
                                'old_values' => $oldValues,
                                'new_values' => $updateData,
                            ]
                        );

                        $successful++;
                    } catch (\Exception $e) {
                        $operation->addError(
                            "ID: {$record->id}",
                            $e->getMessage()
                        );
                        $failed++;
                    }
                    
                    $processed++;
                }

                $operation->updateProgress($processed, $successful, $failed);
            });

            $operation->complete();

            // Log completion
            AuditLog::logSystem(
                'bulk_operation_completed',
                $operation->user,
                "Bulk operation completed successfully",
                [
                    'operation_id' => $operation->id,
                    'processed' => $processed,
                    'successful' => $successful,
                    'failed' => $failed,
                ]
            );

        } catch (\Exception $e) {
            $operation->fail(['error' => $e->getMessage()]);
            
            AuditLog::logSystem(
                'bulk_operation_failed',
                $operation->user,
                "Bulk operation failed: " . $e->getMessage(),
                [
                    'operation_id' => $operation->id,
                    'error' => $e->getMessage(),
                ]
            );
            
            throw $e;
        }
    }

    /**
     * Execute bulk delete operation
     */
    public function executeBulkDelete(BulkOperation $operation): void
    {
        $operation->start();

        try {
            $modelClass = $operation->model_type;
            $filters = $operation->filters;

            // Build query with filters
            $query = $this->buildQuery($modelClass, $filters);
            
            // Get total count if not set
            if ($operation->total_records === 0) {
                $totalCount = $query->count();
                $operation->update(['total_records' => $totalCount]);
            }

            // Process in chunks
            $chunkSize = 100;
            $processed = 0;
            $successful = 0;
            $failed = 0;

            $query->chunk($chunkSize, function ($records) use ($operation, &$processed, &$successful, &$failed) {
                foreach ($records as $record) {
                    try {
                        $recordData = $record->toArray();
                        $record->delete();
                        
                        // Log individual deletion
                        AuditLog::logSystem(
                            'bulk_delete_record',
                            $operation->user,
                            "Record deleted via bulk operation",
                            [
                                'operation_id' => $operation->id,
                                'record_id' => $record->id,
                                'model_type' => $operation->model_type,
                                'record_data' => $recordData,
                            ]
                        );

                        $successful++;
                    } catch (\Exception $e) {
                        $operation->addError(
                            "ID: {$record->id}",
                            $e->getMessage()
                        );
                        $failed++;
                    }
                    
                    $processed++;
                }

                $operation->updateProgress($processed, $successful, $failed);
            });

            $operation->complete();

            // Log completion
            AuditLog::logSystem(
                'bulk_operation_completed',
                $operation->user,
                "Bulk delete operation completed",
                [
                    'operation_id' => $operation->id,
                    'processed' => $processed,
                    'successful' => $successful,
                    'failed' => $failed,
                ]
            );

        } catch (\Exception $e) {
            $operation->fail(['error' => $e->getMessage()]);
            
            AuditLog::logSystem(
                'bulk_operation_failed',
                $operation->user,
                "Bulk delete operation failed: " . $e->getMessage(),
                [
                    'operation_id' => $operation->id,
                    'error' => $e->getMessage(),
                ]
            );
            
            throw $e;
        }
    }

    /**
     * Execute bulk export operation
     */
    public function executeBulkExport(BulkOperation $operation): void
    {
        $operation->start();

        try {
            $modelClass = $operation->model_type;
            $filters = $operation->filters;
            $format = $operation->operation_data['format'] ?? 'csv';
            $columns = $operation->operation_data['columns'] ?? ['*'];

            // Build query with filters
            $query = $this->buildQuery($modelClass, $filters);
            
            // Get total count if not set
            if ($operation->total_records === 0) {
                $totalCount = $query->count();
                $operation->update(['total_records' => $totalCount]);
            }

            // Generate filename
            $filename = $this->generateExportFilename($modelClass, $format);
            $filePath = "exports/{$filename}";

            // Create export file
            $this->createExportFile($query, $filePath, $format, $columns, $operation);

            // Update operation with file path
            $operationData = $operation->operation_data;
            $operationData['export_file'] = $filePath;
            $operation->update(['operation_data' => $operationData]);

            $operation->complete();

            // Log completion
            AuditLog::logSystem(
                'bulk_export_completed',
                $operation->user,
                "Bulk export operation completed",
                [
                    'operation_id' => $operation->id,
                    'file_path' => $filePath,
                    'format' => $format,
                    'total_records' => $operation->total_records,
                ]
            );

        } catch (\Exception $e) {
            $operation->fail(['error' => $e->getMessage()]);
            
            AuditLog::logSystem(
                'bulk_export_failed',
                $operation->user,
                "Bulk export operation failed: " . $e->getMessage(),
                [
                    'operation_id' => $operation->id,
                    'error' => $e->getMessage(),
                ]
            );
            
            throw $e;
        }
    }

    /**
     * Build query with filters
     */
    private function buildQuery(string $modelClass, array $filters): Builder
    {
        $query = $modelClass::query();

        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            if (!$field || $value === null) {
                continue;
            }

            switch ($operator) {
                case '=':
                case '!=':
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $query->where($field, $operator, $value);
                    break;
                case 'like':
                    $query->where($field, 'LIKE', "%{$value}%");
                    break;
                case 'in':
                    $query->whereIn($field, is_array($value) ? $value : [$value]);
                    break;
                case 'not_in':
                    $query->whereNotIn($field, is_array($value) ? $value : [$value]);
                    break;
                case 'between':
                    if (is_array($value) && count($value) === 2) {
                        $query->whereBetween($field, $value);
                    }
                    break;
                case 'null':
                    $query->whereNull($field);
                    break;
                case 'not_null':
                    $query->whereNotNull($field);
                    break;
                default:
                    $query->where($field, $value);
            }
        }

        return $query;
    }

    /**
     * Generate export filename
     */
    private function generateExportFilename(string $modelClass, string $format): string
    {
        $modelName = class_basename($modelClass);
        $timestamp = now()->format('Y-m-d_H-i-s');
        return strtolower("{$modelName}_export_{$timestamp}.{$format}");
    }

    /**
     * Create export file
     */
    private function createExportFile(Builder $query, string $filePath, string $format, array $columns, BulkOperation $operation): void
    {
        if ($format === 'csv') {
            $this->createCsvExport($query, $filePath, $columns, $operation);
        } elseif ($format === 'json') {
            $this->createJsonExport($query, $filePath, $columns, $operation);
        } else {
            throw new \Exception("Unsupported export format: {$format}");
        }
    }

    /**
     * Create CSV export
     */
    private function createCsvExport(Builder $query, string $filePath, array $columns, BulkOperation $operation): void
    {
        $handle = fopen('php://temp', 'w');
        $chunkSize = 1000;
        $processed = 0;
        $isFirstChunk = true;

        $query->chunk($chunkSize, function ($records) use ($handle, $columns, &$processed, &$isFirstChunk, $operation) {
            foreach ($records as $record) {
                $data = $record->toArray();
                
                // Write header for first record
                if ($isFirstChunk && $processed === 0) {
                    $headers = $columns === ['*'] ? array_keys($data) : $columns;
                    fputcsv($handle, $headers);
                }
                
                // Write data
                $rowData = $columns === ['*'] ? array_values($data) : array_map(fn($col) => $data[$col] ?? '', $columns);
                fputcsv($handle, $rowData);
                
                $processed++;
                $operation->updateProgress($processed, $processed, 0);
            }
            
            $isFirstChunk = false;
        });

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        Storage::put($filePath, $content);
    }

    /**
     * Create JSON export
     */
    private function createJsonExport(Builder $query, string $filePath, array $columns, BulkOperation $operation): void
    {
        $data = [];
        $chunkSize = 1000;
        $processed = 0;

        $query->chunk($chunkSize, function ($records) use (&$data, $columns, &$processed, $operation) {
            foreach ($records as $record) {
                $recordData = $record->toArray();
                
                if ($columns !== ['*']) {
                    $recordData = array_intersect_key($recordData, array_flip($columns));
                }
                
                $data[] = $recordData;
                $processed++;
                $operation->updateProgress($processed, $processed, 0);
            }
        });

        Storage::put($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Cancel operation
     */
    public function cancelOperation(BulkOperation $operation): void
    {
        if (!$operation->canCancel()) {
            throw new \Exception('Operation cannot be cancelled');
        }

        $operation->cancel();

        AuditLog::logSystem(
            'bulk_operation_cancelled',
            $operation->user,
            "Bulk operation cancelled",
            [
                'operation_id' => $operation->id,
                'operation_type' => $operation->operation_type,
            ]
        );
    }

    /**
     * Get operation statistics
     */
    public function getOperationStats(User $user = null): array
    {
        $query = BulkOperation::query();
        
        if ($user) {
            $query->where('user_id', $user->id);
        }

        $stats = [
            'total_operations' => $query->count(),
            'active_operations' => $query->active()->count(),
            'completed_operations' => $query->completed()->count(),
            'failed_operations' => $query->failed()->count(),
            'recent_operations' => $query->recent(24)->count(),
        ];

        // Get operation type breakdown
        $typeStats = $query->select('operation_type')
            ->selectRaw('count(*) as count')
            ->groupBy('operation_type')
            ->pluck('count', 'operation_type')
            ->toArray();

        $stats['operations_by_type'] = $typeStats;

        return $stats;
    }

    /**
     * Get available models for bulk operations
     */
    public function getAvailableModels(): array
    {
        return [
            'App\\Models\\User' => [
                'name' => 'Users',
                'fields' => ['name', 'email', 'is_active', 'role_id'],
                'operations' => ['update', 'delete', 'export'],
            ],
            'App\\Models\\Pasien' => [
                'name' => 'Patients',
                'fields' => ['nama', 'alamat', 'no_telepon', 'tanggal_lahir'],
                'operations' => ['update', 'delete', 'export'],
            ],
            'App\\Models\\Tindakan' => [
                'name' => 'Treatments',
                'fields' => ['nama_tindakan', 'harga', 'deskripsi'],
                'operations' => ['update', 'delete', 'export'],
            ],
            'App\\Models\\Pendapatan' => [
                'name' => 'Revenue',
                'fields' => ['jumlah', 'tanggal', 'deskripsi'],
                'operations' => ['update', 'delete', 'export'],
            ],
            'App\\Models\\Pengeluaran' => [
                'name' => 'Expenses',
                'fields' => ['jumlah', 'tanggal', 'deskripsi'],
                'operations' => ['update', 'delete', 'export'],
            ],
        ];
    }
}
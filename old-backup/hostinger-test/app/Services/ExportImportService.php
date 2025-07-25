<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Exception;
use App\Traits\HandlesErrors;
use App\Exceptions\ValidationException;
use App\Exceptions\SystemException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExportImportService
{
    use HandlesErrors;
    protected array $supportedFormats = ['csv', 'xlsx', 'json'];
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

    /**
     * Export data to specified format
     */
    public function exportData(string $modelClass, array $options = []): array
    {
        $this->validateModel($modelClass);
        
        try {
            $format = $options['format'] ?? 'xlsx';
            $filename = $options['filename'] ?? $this->generateFilename($modelClass, $format);
            $includeRelations = $options['include_relations'] ?? false;
            $filters = $options['filters'] ?? [];
            
            // Get data with optional filters
            $query = $modelClass::query();
            
            if (!empty($filters)) {
                $query = $this->applyFilters($query, $filters);
            }
            
            $data = $query->get();
            
            // Include relations if requested
            if ($includeRelations && method_exists($modelClass, 'getExportableRelations')) {
                $relations = (new $modelClass)->getExportableRelations();
                $data->load($relations);
            }
            
            // Convert to array
            $exportData = $data->map(function ($item) use ($includeRelations) {
                $array = $item->toArray();
                
                // Format dates for export
                if (method_exists($item, 'getExportableDates')) {
                    foreach ($item->getExportableDates() as $dateField) {
                        if (isset($array[$dateField])) {
                            $array[$dateField] = $item->$dateField?->format('Y-m-d H:i:s');
                        }
                    }
                }
                
                return $array;
            })->toArray();
            
            // Generate file based on format
            $filePath = $this->generateFile($exportData, $format, $filename);
            
            // Log export operation
            $this->logExportImportOperation('export', $modelClass, count($exportData), $format);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $filename,
                'format' => $format,
                'record_count' => count($exportData),
                'file_size' => Storage::size($filePath),
            ];
            
        } catch (Exception $e) {
            Log::error('Export operation failed', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Import data from file with validation
     */
    public function importData(string $modelClass, string $filePath, array $options = []): array
    {
        $this->validateModel($modelClass);
        
        try {
            $validateData = $options['validate'] ?? true;
            $skipDuplicates = $options['skip_duplicates'] ?? false;
            $batchSize = $options['batch_size'] ?? 100;
            
            // Parse file based on extension
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $data = $this->parseFile($filePath, $extension);
            
            if (empty($data)) {
                throw new Exception('No data found in file');
            }
            
            $results = [];
            $errors = [];
            $skipped = 0;
            
            // Process data in batches
            $batches = array_chunk($data, $batchSize);
            
            foreach ($batches as $batchIndex => $batch) {
                $batchResults = $this->processBatch(
                    $modelClass,
                    $batch,
                    $validateData,
                    $skipDuplicates,
                    $batchIndex
                );
                
                $results = array_merge($results, $batchResults['success']);
                $errors = array_merge($errors, $batchResults['errors']);
                $skipped += $batchResults['skipped'];
            }
            
            // Log import operation
            $this->logExportImportOperation('import', $modelClass, count($results), $extension);
            
            return [
                'success' => true,
                'imported' => count($results),
                'errors' => count($errors),
                'skipped' => $skipped,
                'total_processed' => count($data),
                'data' => $results,
                'error_details' => $errors,
            ];
            
        } catch (Exception $e) {
            Log::error('Import operation failed', [
                'model' => $modelClass,
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Validate import data structure
     */
    public function validateImportData(string $modelClass, string $filePath): array
    {
        $this->validateModel($modelClass);
        
        try {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $data = $this->parseFile($filePath, $extension);
            
            if (empty($data)) {
                throw new Exception('No data found in file');
            }
            
            $model = new $modelClass;
            $fillable = $model->getFillable();
            $required = method_exists($model, 'getImportRequiredFields') 
                ? $model->getImportRequiredFields() 
                : [];
            
            $results = [];
            $errors = [];
            
            foreach ($data as $index => $item) {
                $validationResult = [
                    'index' => $index,
                    'data' => $item,
                    'issues' => []
                ];
                
                // Check required fields
                foreach ($required as $field) {
                    if (!isset($item[$field]) || empty($item[$field])) {
                        $validationResult['issues'][] = "Missing required field: {$field}";
                    }
                }
                
                // Check unknown fields
                foreach ($item as $field => $value) {
                    if (!in_array($field, $fillable)) {
                        $validationResult['issues'][] = "Unknown field: {$field}";
                    }
                }
                
                // Validate data types
                if (method_exists($model, 'getImportValidationRules')) {
                    $rules = $model->getImportValidationRules();
                    $validator = Validator::make($item, $rules);
                    
                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $error) {
                            $validationResult['issues'][] = $error;
                        }
                    }
                }
                
                if (empty($validationResult['issues'])) {
                    $results[] = $validationResult;
                } else {
                    $errors[] = $validationResult;
                }
            }
            
            return [
                'total' => count($data),
                'valid' => count($results),
                'invalid' => count($errors),
                'validation_results' => $results,
                'validation_errors' => $errors,
            ];
            
        } catch (Exception $e) {
            Log::error('Import validation failed', [
                'model' => $modelClass,
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Generate export template for model
     */
    public function generateTemplate(string $modelClass, string $format = 'xlsx'): array
    {
        $this->validateModel($modelClass);
        
        try {
            $model = new $modelClass;
            $fillable = $model->getFillable();
            $required = method_exists($model, 'getImportRequiredFields') 
                ? $model->getImportRequiredFields() 
                : [];
            
            // Create sample data
            $sampleData = [];
            foreach ($fillable as $field) {
                $isRequired = in_array($field, $required);
                $sampleData[$field] = $isRequired ? "REQUIRED" : "optional";
            }
            
            // Add field descriptions if available
            if (method_exists($model, 'getImportFieldDescriptions')) {
                $descriptions = $model->getImportFieldDescriptions();
                $sampleData = array_merge($sampleData, $descriptions);
            }
            
            $filename = $this->generateFilename($modelClass, $format, 'template');
            
            // Generate template file
            $templateData = [$sampleData];
            $filePath = $this->generateFile($templateData, $format, $filename);
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'file_name' => $filename,
                'format' => $format,
                'fields' => $fillable,
                'required_fields' => $required,
            ];
            
        } catch (Exception $e) {
            Log::error('Template generation failed', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Parse file based on extension
     */
    protected function parseFile(string $filePath, string $extension): array
    {
        switch ($extension) {
            case 'json':
                $content = Storage::get($filePath);
                $data = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON format: ' . json_last_error_msg());
                }
                return $data;
                
            case 'csv':
                $content = Storage::get($filePath);
                $lines = explode("\n", trim($content));
                $headers = str_getcsv(array_shift($lines));
                return array_map(function ($line) use ($headers) {
                    if (empty(trim($line))) return null;
                    $values = str_getcsv($line);
                    return array_combine($headers, $values);
                }, array_filter($lines));
                
            case 'xlsx':
                $fullPath = Storage::path($filePath);
                $spreadsheet = IOFactory::load($fullPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $data = $worksheet->toArray();
                
                if (empty($data)) {
                    return [];
                }
                
                $headers = array_shift($data);
                return array_map(function ($row) use ($headers) {
                    return array_combine($headers, $row);
                }, $data);
                
            default:
                throw new Exception("Unsupported file format: {$extension}");
        }
    }

    /**
     * Generate file in specified format
     */
    protected function generateFile(array $data, string $format, string $filename): string
    {
        $filePath = "exports/{$filename}";
        
        switch ($format) {
            case 'json':
                $content = json_encode($data, JSON_PRETTY_PRINT);
                Storage::put($filePath, $content);
                break;
                
            case 'csv':
                if (empty($data)) {
                    Storage::put($filePath, '');
                    break;
                }
                
                $headers = array_keys($data[0]);
                $csv = implode(',', $headers) . "\n";
                
                foreach ($data as $row) {
                    $csv .= implode(',', array_map(function ($value) {
                        return '"' . str_replace('"', '""', $value) . '"';
                    }, $row)) . "\n";
                }
                
                Storage::put($filePath, $csv);
                break;
                
            case 'xlsx':
                $spreadsheet = new Spreadsheet();
                $worksheet = $spreadsheet->getActiveSheet();
                
                if (!empty($data)) {
                    $headers = array_keys($data[0]);
                    $worksheet->fromArray($headers, null, 'A1');
                    
                    $row = 2;
                    foreach ($data as $item) {
                        $worksheet->fromArray(array_values($item), null, 'A' . $row);
                        $row++;
                    }
                }
                
                $writer = new Xlsx($spreadsheet);
                $tempFile = tempnam(sys_get_temp_dir(), 'export_');
                $writer->save($tempFile);
                
                Storage::put($filePath, file_get_contents($tempFile));
                unlink($tempFile);
                break;
                
            default:
                throw new Exception("Unsupported export format: {$format}");
        }
        
        return $filePath;
    }

    /**
     * Process batch of import data
     */
    protected function processBatch(
        string $modelClass,
        array $batch,
        bool $validate,
        bool $skipDuplicates,
        int $batchIndex
    ): array {
        $results = [];
        $errors = [];
        $skipped = 0;
        
        foreach ($batch as $index => $item) {
            try {
                // Skip empty rows
                if (empty(array_filter($item))) {
                    continue;
                }
                
                // Check for duplicates
                if ($skipDuplicates && $this->isDuplicate($modelClass, $item)) {
                    $skipped++;
                    continue;
                }
                
                $model = new $modelClass;
                
                // Validate data
                if ($validate) {
                    if (method_exists($model, 'getImportValidationRules')) {
                        $rules = $model->getImportValidationRules();
                        $validator = Validator::make($item, $rules);
                        
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
                }
                
                // Transform data if needed
                if (method_exists($model, 'transformImportData')) {
                    $item = $model->transformImportData($item);
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
            'errors' => $errors,
            'skipped' => $skipped
        ];
    }

    /**
     * Check if record is duplicate
     */
    protected function isDuplicate(string $modelClass, array $data): bool
    {
        $model = new $modelClass;
        
        if (method_exists($model, 'getImportUniqueFields')) {
            $uniqueFields = $model->getImportUniqueFields();
            $query = $modelClass::query();
            
            foreach ($uniqueFields as $field) {
                if (isset($data[$field])) {
                    $query->where($field, $data[$field]);
                }
            }
            
            return $query->exists();
        }
        
        return false;
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query;
    }

    /**
     * Generate filename
     */
    protected function generateFilename(string $modelClass, string $format, string $prefix = 'export'): string
    {
        $modelName = strtolower(class_basename($modelClass));
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "{$prefix}_{$modelName}_{$timestamp}.{$format}";
    }

    /**
     * Validate model
     */
    protected function validateModel(string $modelClass): void
    {
        if (!class_exists($modelClass)) {
            throw new Exception("Model class {$modelClass} does not exist");
        }
        
        $modelName = class_basename($modelClass);
        if (!in_array($modelName, $this->supportedModels)) {
            throw new Exception("Model {$modelName} is not supported for export/import");
        }
    }

    /**
     * Log export/import operation
     */
    protected function logExportImportOperation(string $operation, string $modelClass, int $count, string $format): void
    {
        try {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $operation,
                'model_type' => $modelClass,
                'model_id' => null,
                'changes' => json_encode([
                    'operation' => $operation,
                    'model' => class_basename($modelClass),
                    'record_count' => $count,
                    'format' => $format,
                ]),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'risk_level' => 'low',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log export/import operation', [
                'error' => $e->getMessage(),
                'operation' => $operation,
                'model' => $modelClass
            ]);
        }
    }
}
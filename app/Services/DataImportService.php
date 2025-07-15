<?php

namespace App\Services;

use App\Models\DataImport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class DataImportService
{
    /**
     * Create a new data import
     */
    public function createImport(User $user, array $data): DataImport
    {
        $import = DataImport::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'user_id' => $user->id,
            'source_type' => $data['source_type'],
            'target_model' => $data['target_model'],
            'mapping_config' => $data['mapping_config'] ?? [],
            'validation_rules' => $data['validation_rules'] ?? [],
            'source_config' => $data['source_config'] ?? [],
            'backup_before_import' => $data['backup_before_import'] ?? true,
            'is_scheduled' => $data['is_scheduled'] ?? false,
            'schedule_frequency' => $data['schedule_frequency'] ?? null,
            'notification_settings' => $data['notification_settings'] ?? [],
        ]);

        if ($import->is_scheduled && $import->schedule_frequency) {
            $import->scheduleNextRun();
        }

        return $import;
    }

    /**
     * Handle file upload for import
     */
    public function handleFileUpload(DataImport $import, UploadedFile $file): void
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('imports', $fileName, 'local');

        $import->update([
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }

    /**
     * Preview import data
     */
    public function previewImport(DataImport $import, int $limit = 10): array
    {
        try {
            $data = $this->readSourceData($import, $limit);
            $preview = [
                'sample_data' => $data,
                'total_rows' => count($data),
                'columns' => !empty($data) ? array_keys($data[0]) : [],
            ];

            $import->update(['preview_data' => $preview]);
            return $preview;
        } catch (Exception $e) {
            Log::error('Import preview failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Execute data import
     */
    public function executeImport(DataImport $import): void
    {
        try {
            $import->start();

            // Create backup if enabled
            if ($import->backup_before_import) {
                $this->createBackup($import);
            }

            // Read and process data
            $data = $this->readSourceData($import);
            $import->update(['total_rows' => count($data)]);

            $this->processImportData($import, $data);

            $import->complete();
            $import->scheduleNextRun();
        } catch (Exception $e) {
            Log::error('Import execution failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);
            $import->fail($e->getMessage());
        }
    }

    /**
     * Read source data based on import type
     */
    private function readSourceData(DataImport $import, int $limit = null): array
    {
        return match($import->source_type) {
            DataImport::SOURCE_CSV => $this->readCsvData($import, $limit),
            DataImport::SOURCE_EXCEL => $this->readExcelData($import, $limit),
            DataImport::SOURCE_JSON => $this->readJsonData($import, $limit),
            DataImport::SOURCE_API => $this->readApiData($import, $limit),
            default => throw new Exception("Unsupported source type: {$import->source_type}"),
        };
    }

    /**
     * Read CSV data
     */
    private function readCsvData(DataImport $import, int $limit = null): array
    {
        $filePath = Storage::path($import->file_path);
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $records = [];
        $count = 0;

        foreach ($csv->getRecords() as $record) {
            if ($limit && $count >= $limit) {
                break;
            }
            $records[] = $record;
            $count++;
        }

        return $records;
    }

    /**
     * Read Excel data
     */
    private function readExcelData(DataImport $import, int $limit = null): array
    {
        $filePath = Storage::path($import->file_path);
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        $data = $worksheet->toArray();
        $headers = array_shift($data);
        
        $records = [];
        $count = 0;

        foreach ($data as $row) {
            if ($limit && $count >= $limit) {
                break;
            }
            $records[] = array_combine($headers, $row);
            $count++;
        }

        return $records;
    }

    /**
     * Read JSON data
     */
    private function readJsonData(DataImport $import, int $limit = null): array
    {
        $filePath = Storage::path($import->file_path);
        $jsonData = json_decode(file_get_contents($filePath), true);

        if ($limit) {
            return array_slice($jsonData, 0, $limit);
        }

        return $jsonData;
    }

    /**
     * Read API data
     */
    private function readApiData(DataImport $import, int $limit = null): array
    {
        $config = $import->source_config;
        $url = $config['url'] ?? null;
        $headers = $config['headers'] ?? [];
        $params = $config['params'] ?? [];

        if (!$url) {
            throw new Exception('API URL is required');
        }

        if ($limit) {
            $params['limit'] = $limit;
        }

        $response = $this->makeApiRequest($url, $headers, $params);
        return json_decode($response, true);
    }

    /**
     * Make API request
     */
    private function makeApiRequest(string $url, array $headers = [], array $params = []): string
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url . '?' . http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode !== 200) {
            throw new Exception("API request failed with status: {$httpCode}");
        }

        return $response;
    }

    /**
     * Process import data
     */
    private function processImportData(DataImport $import, array $data): void
    {
        $modelClass = $import->target_model;
        $mappingConfig = $import->mapping_config;
        $validationRules = $import->validation_rules;

        $processed = 0;
        $successful = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            try {
                // Map fields
                $mappedData = $this->mapFields($row, $mappingConfig);

                // Validate data
                if ($validationRules) {
                    $validator = Validator::make($mappedData, $validationRules);
                    if ($validator->fails()) {
                        $errors[] = [
                            'row' => $index + 1,
                            'errors' => $validator->errors()->toArray(),
                        ];
                        $failed++;
                        continue;
                    }
                }

                // Create record
                $modelClass::create($mappedData);
                $successful++;

            } catch (Exception $e) {
                $errors[] = [
                    'row' => $index + 1,
                    'error' => $e->getMessage(),
                ];
                $failed++;
            }

            $processed++;

            // Update progress every 100 records
            if ($processed % 100 === 0) {
                $import->updateProgress($processed, $successful, $failed, $skipped);
            }
        }

        // Final progress update
        $import->updateProgress($processed, $successful, $failed, $skipped);

        // Store validation errors
        if (!empty($errors)) {
            $import->update([
                'validation_errors' => json_encode($errors),
                'error_details' => ['validation_errors' => $errors],
            ]);
        }
    }

    /**
     * Map fields based on configuration
     */
    private function mapFields(array $sourceData, array $mappingConfig): array
    {
        $mappedData = [];

        foreach ($mappingConfig as $targetField => $sourceField) {
            if (isset($sourceData[$sourceField])) {
                $mappedData[$targetField] = $sourceData[$sourceField];
            }
        }

        return $mappedData;
    }

    /**
     * Create backup before import
     */
    private function createBackup(DataImport $import): void
    {
        $modelClass = $import->target_model;
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupFileName = "backup_{$timestamp}.json";
        $backupPath = "backups/{$backupFileName}";

        $data = $modelClass::all()->toArray();
        Storage::put($backupPath, json_encode($data, JSON_PRETTY_PRINT));

        $import->update(['backup_file_path' => $backupPath]);
    }

    /**
     * Get import statistics
     */
    public function getImportStats(User $user): array
    {
        $imports = DataImport::where('user_id', $user->id);

        return [
            'total_imports' => $imports->count(),
            'completed_imports' => $imports->completed()->count(),
            'failed_imports' => $imports->failed()->count(),
            'scheduled_imports' => $imports->scheduled()->count(),
            'processing_imports' => $imports->processing()->count(),
            'total_rows_processed' => $imports->sum('processed_rows'),
            'total_rows_successful' => $imports->sum('successful_rows'),
            'avg_success_rate' => $imports->avg('successful_rows') / ($imports->avg('processed_rows') ?: 1) * 100,
        ];
    }

    /**
     * Get available field mappings for a model
     */
    public function getAvailableFields(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            return [];
        }

        $model = new $modelClass;
        return $model->getFillable();
    }

    /**
     * Cancel import
     */
    public function cancelImport(DataImport $import): void
    {
        if ($import->isProcessing()) {
            $import->cancel();
        }
    }

    /**
     * Delete import and associated files
     */
    public function deleteImport(DataImport $import): void
    {
        // Delete associated files
        if ($import->file_path && Storage::exists($import->file_path)) {
            Storage::delete($import->file_path);
        }

        if ($import->backup_file_path && Storage::exists($import->backup_file_path)) {
            Storage::delete($import->backup_file_path);
        }

        $import->delete();
    }

    /**
     * Get scheduled imports ready for execution
     */
    public function getScheduledImports(): array
    {
        return DataImport::scheduled()
            ->where('next_run_at', '<=', now())
            ->where('status', DataImport::STATUS_PENDING)
            ->get()
            ->toArray();
    }

    /**
     * Validate import configuration
     */
    public function validateImportConfig(array $config): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'source_type' => 'required|in:' . implode(',', array_keys(DataImport::getSourceTypes())),
            'target_model' => 'required|in:' . implode(',', array_keys(DataImport::getTargetModels())),
            'mapping_config' => 'required|array',
        ];

        $validator = Validator::make($config, $rules);
        
        if ($validator->fails()) {
            throw new Exception('Invalid import configuration: ' . $validator->errors()->first());
        }

        return $config;
    }
}
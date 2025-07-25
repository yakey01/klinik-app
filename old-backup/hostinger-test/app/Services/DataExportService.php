<?php

namespace App\Services;

use App\Models\DataExport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Exception;

class DataExportService
{
    /**
     * Create a new data export
     */
    public function createExport(User $user, array $data): DataExport
    {
        $export = DataExport::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'user_id' => $user->id,
            'source_model' => $data['source_model'],
            'export_format' => $data['export_format'],
            'query_config' => $data['query_config'] ?? [],
            'column_config' => $data['column_config'] ?? [],
            'format_config' => $data['format_config'] ?? [],
            'compress_output' => $data['compress_output'] ?? false,
            'compression_format' => $data['compression_format'] ?? null,
            'encrypt_output' => $data['encrypt_output'] ?? false,
            'expires_at' => $data['expires_at'] ?? null,
            'is_scheduled' => $data['is_scheduled'] ?? false,
            'schedule_frequency' => $data['schedule_frequency'] ?? null,
            'notification_settings' => $data['notification_settings'] ?? [],
            'access_permissions' => $data['access_permissions'] ?? [],
        ]);

        if ($export->is_scheduled && $export->schedule_frequency) {
            $export->scheduleNextRun();
        }

        return $export;
    }

    /**
     * Execute data export
     */
    public function executeExport(DataExport $export): void
    {
        try {
            $export->start();

            // Build query
            $query = $this->buildQuery($export);
            $data = $query->get()->toArray();

            $export->update(['total_rows' => count($data)]);

            // Generate export file
            $filePath = $this->generateExportFile($export, $data);

            // Apply compression if enabled
            if ($export->compress_output) {
                $filePath = $this->compressFile($filePath, $export->compression_format);
            }

            // Apply encryption if enabled
            if ($export->encrypt_output) {
                $filePath = $this->encryptFile($filePath, $export->encryption_key);
            }

            $export->update([
                'file_path' => $filePath,
                'file_name' => basename($filePath),
                'file_size' => Storage::size($filePath),
                'exported_rows' => count($data),
            ]);

            $export->complete();
            $export->scheduleNextRun();

        } catch (Exception $e) {
            Log::error('Export execution failed', [
                'export_id' => $export->id,
                'error' => $e->getMessage(),
            ]);
            $export->fail($e->getMessage());
        }
    }

    /**
     * Build query based on export configuration
     */
    private function buildQuery(DataExport $export): Builder
    {
        $modelClass = $export->source_model;
        $queryConfig = $export->query_config;
        $columnConfig = $export->column_config;

        $query = $modelClass::query();

        // Apply filters
        if (isset($queryConfig['filters']) && is_array($queryConfig['filters'])) {
            foreach ($queryConfig['filters'] as $filter) {
                $field = $filter['field'];
                $operator = $filter['operator'];
                $value = $filter['value'];

                match($operator) {
                    '=' => $query->where($field, $value),
                    '!=' => $query->where($field, '!=', $value),
                    '>' => $query->where($field, '>', $value),
                    '<' => $query->where($field, '<', $value),
                    '>=' => $query->where($field, '>=', $value),
                    '<=' => $query->where($field, '<=', $value),
                    'like' => $query->where($field, 'like', "%{$value}%"),
                    'in' => $query->whereIn($field, explode(',', $value)),
                    'date_range' => $query->whereBetween($field, explode(',', $value)),
                    default => $query->where($field, $value),
                };
            }
        }

        // Apply sorting
        if (isset($queryConfig['sort'])) {
            $sort = $queryConfig['sort'];
            $query->orderBy($sort['field'], $sort['direction'] ?? 'asc');
        }

        // Apply limit
        if (isset($queryConfig['limit'])) {
            $query->limit($queryConfig['limit']);
        }

        // Select specific columns
        if (!empty($columnConfig)) {
            $query->select(array_keys($columnConfig));
        }

        return $query;
    }

    /**
     * Generate export file based on format
     */
    private function generateExportFile(DataExport $export, array $data): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "export_{$export->id}_{$timestamp}";

        return match($export->export_format) {
            DataExport::FORMAT_CSV => $this->generateCsvFile($fileName, $data, $export->column_config),
            DataExport::FORMAT_EXCEL => $this->generateExcelFile($fileName, $data, $export->column_config),
            DataExport::FORMAT_JSON => $this->generateJsonFile($fileName, $data),
            DataExport::FORMAT_XML => $this->generateXmlFile($fileName, $data),
            DataExport::FORMAT_PDF => $this->generatePdfFile($fileName, $data, $export->column_config),
            default => throw new Exception("Unsupported export format: {$export->export_format}"),
        };
    }

    /**
     * Generate CSV file
     */
    private function generateCsvFile(string $fileName, array $data, array $columnConfig): string
    {
        $filePath = "exports/{$fileName}.csv";
        $writer = Writer::createFromString();

        if (!empty($data)) {
            // Write headers
            $headers = !empty($columnConfig) ? array_values($columnConfig) : array_keys($data[0]);
            $writer->insertOne($headers);

            // Write data
            foreach ($data as $row) {
                $exportRow = [];
                foreach ($headers as $key => $header) {
                    $field = !empty($columnConfig) ? array_keys($columnConfig)[$key] : $header;
                    $exportRow[] = $row[$field] ?? '';
                }
                $writer->insertOne($exportRow);
            }
        }

        Storage::put($filePath, $writer->toString());
        return $filePath;
    }

    /**
     * Generate Excel file
     */
    private function generateExcelFile(string $fileName, array $data, array $columnConfig): string
    {
        $filePath = "exports/{$fileName}.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if (!empty($data)) {
            // Write headers
            $headers = !empty($columnConfig) ? array_values($columnConfig) : array_keys($data[0]);
            $sheet->fromArray($headers, null, 'A1');

            // Write data
            $row = 2;
            foreach ($data as $dataRow) {
                $exportRow = [];
                foreach ($headers as $key => $header) {
                    $field = !empty($columnConfig) ? array_keys($columnConfig)[$key] : $header;
                    $exportRow[] = $dataRow[$field] ?? '';
                }
                $sheet->fromArray($exportRow, null, "A{$row}");
                $row++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        $tempPath = storage_path("app/{$filePath}");
        $writer->save($tempPath);

        return $filePath;
    }

    /**
     * Generate JSON file
     */
    private function generateJsonFile(string $fileName, array $data): string
    {
        $filePath = "exports/{$fileName}.json";
        Storage::put($filePath, json_encode($data, JSON_PRETTY_PRINT));
        return $filePath;
    }

    /**
     * Generate XML file
     */
    private function generateXmlFile(string $fileName, array $data): string
    {
        $filePath = "exports/{$fileName}.xml";
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data></data>');

        foreach ($data as $index => $row) {
            $record = $xml->addChild('record');
            $record->addAttribute('id', $index + 1);
            
            foreach ($row as $key => $value) {
                $record->addChild($key, htmlspecialchars($value));
            }
        }

        Storage::put($filePath, $xml->asXML());
        return $filePath;
    }

    /**
     * Generate PDF file
     */
    private function generatePdfFile(string $fileName, array $data, array $columnConfig): string
    {
        $filePath = "exports/{$fileName}.pdf";
        $headers = !empty($columnConfig) ? array_values($columnConfig) : (!empty($data) ? array_keys($data[0]) : []);

        $html = '<table border="1" cellpadding="5" cellspacing="0">';
        
        // Headers
        $html .= '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead>';

        // Data
        $html .= '<tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($headers as $key => $header) {
                $field = !empty($columnConfig) ? array_keys($columnConfig)[$key] : $header;
                $html .= '<td>' . htmlspecialchars($row[$field] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();

        Storage::put($filePath, $dompdf->output());
        return $filePath;
    }

    /**
     * Compress file
     */
    private function compressFile(string $filePath, string $format): string
    {
        $fullPath = Storage::path($filePath);
        $compressedPath = $filePath . '.' . $format;

        match($format) {
            DataExport::COMPRESSION_ZIP => $this->createZipFile($fullPath, Storage::path($compressedPath)),
            DataExport::COMPRESSION_GZIP => $this->createGzipFile($fullPath, Storage::path($compressedPath)),
            default => throw new Exception("Unsupported compression format: {$format}"),
        };

        // Delete original file
        Storage::delete($filePath);

        return $compressedPath;
    }

    /**
     * Create ZIP file
     */
    private function createZipFile(string $source, string $destination): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($destination, \ZipArchive::CREATE) === TRUE) {
            $zip->addFile($source, basename($source));
            $zip->close();
        } else {
            throw new Exception('Failed to create ZIP file');
        }
    }

    /**
     * Create GZIP file
     */
    private function createGzipFile(string $source, string $destination): void
    {
        $sourceFile = fopen($source, 'rb');
        $destFile = gzopen($destination, 'wb9');

        while (!feof($sourceFile)) {
            gzwrite($destFile, fread($sourceFile, 8192));
        }

        fclose($sourceFile);
        gzclose($destFile);
    }

    /**
     * Encrypt file
     */
    private function encryptFile(string $filePath, string $key): string
    {
        $data = Storage::get($filePath);
        $encryptedData = encrypt($data);
        
        $encryptedPath = $filePath . '.encrypted';
        Storage::put($encryptedPath, $encryptedData);
        
        // Delete original file
        Storage::delete($filePath);
        
        return $encryptedPath;
    }

    /**
     * Download export file
     */
    public function downloadExport(DataExport $export): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (!$export->isDownloadable()) {
            throw new Exception('Export file is not available for download');
        }

        $export->recordDownload();
        
        return response()->download(
            Storage::path($export->file_path),
            $export->file_name
        );
    }

    /**
     * Get export statistics
     */
    public function getExportStats(User $user): array
    {
        $exports = DataExport::where('user_id', $user->id);

        return [
            'total_exports' => $exports->count(),
            'completed_exports' => $exports->completed()->count(),
            'failed_exports' => $exports->failed()->count(),
            'scheduled_exports' => $exports->scheduled()->count(),
            'processing_exports' => $exports->processing()->count(),
            'total_rows_exported' => $exports->sum('exported_rows'),
            'total_downloads' => $exports->sum('download_count'),
            'avg_file_size' => $exports->avg('file_size'),
        ];
    }

    /**
     * Cancel export
     */
    public function cancelExport(DataExport $export): void
    {
        if ($export->isProcessing()) {
            $export->cancel();
        }
    }

    /**
     * Delete export and associated files
     */
    public function deleteExport(DataExport $export): void
    {
        // Delete associated files
        if ($export->file_path && Storage::exists($export->file_path)) {
            Storage::delete($export->file_path);
        }

        $export->delete();
    }

    /**
     * Get scheduled exports ready for execution
     */
    public function getScheduledExports(): array
    {
        return DataExport::scheduled()
            ->where('next_run_at', '<=', now())
            ->where('status', DataExport::STATUS_PENDING)
            ->get()
            ->toArray();
    }

    /**
     * Clean up expired exports
     */
    public function cleanupExpiredExports(): int
    {
        $expiredExports = DataExport::where('expires_at', '<', now())
            ->where('status', DataExport::STATUS_COMPLETED)
            ->get();

        $count = 0;
        foreach ($expiredExports as $export) {
            $this->deleteExport($export);
            $count++;
        }

        return $count;
    }

    /**
     * Get available columns for a model
     */
    public function getAvailableColumns(string $modelClass): array
    {
        if (!class_exists($modelClass)) {
            return [];
        }

        $model = new $modelClass;
        
        // Get fillable attributes
        $fillable = $model->getFillable();
        
        // Get additional attributes from database schema
        $table = $model->getTable();
        $columns = \Schema::getColumnListing($table);
        
        return array_unique(array_merge($fillable, $columns));
    }

    /**
     * Validate export configuration
     */
    public function validateExportConfig(array $config): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'source_model' => 'required|in:' . implode(',', array_keys(DataExport::getSourceModels())),
            'export_format' => 'required|in:' . implode(',', array_keys(DataExport::getExportFormats())),
            'column_config' => 'required|array',
        ];

        $validator = \Validator::make($config, $rules);
        
        if ($validator->fails()) {
            throw new Exception('Invalid export configuration: ' . $validator->errors()->first());
        }

        return $config;
    }
}
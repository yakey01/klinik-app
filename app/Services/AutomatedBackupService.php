<?php

namespace App\Services;

use App\Services\RealTimeNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use Exception;

class AutomatedBackupService
{
    protected RealTimeNotificationService $notificationService;
    protected array $criticalTables = [
        'users', 'pendapatan', 'pengeluaran', 'tindakan', 'jaspel', 
        'audit_logs', 'jumlah_pasien_harian', 'dokter', 'pegawai'
    ];

    public function __construct(RealTimeNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function performFullBackup(): array
    {
        $startTime = microtime(true);
        
        try {
            $backupPath = $this->createBackupPath();
            
            // Database backup
            $dbBackup = $this->createDatabaseBackup($backupPath);
            
            // File system backup
            $fileBackup = $this->createFileSystemBackup($backupPath);
            
            // Verify backup integrity
            $verification = $this->verifyBackupIntegrity($backupPath);
            
            $result = [
                'success' => true,
                'backup_path' => $backupPath,
                'database_backup' => $dbBackup,
                'file_backup' => $fileBackup,
                'verification' => $verification,
                'duration' => round(microtime(true) - $startTime, 2),
                'created_at' => now(),
            ];

            $this->notifyBackupCompletion($result);
            return $result;

        } catch (Exception $e) {
            Log::error('Backup failed', ['error' => $e->getMessage()]);
            $this->notifyBackupFailure($e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 2),
            ];
        }
    }

    protected function createBackupPath(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        return "backups/automated_backup_{$timestamp}";
    }

    protected function createDatabaseBackup(string $backupPath): array
    {
        $dbPath = "{$backupPath}/database";
        Storage::makeDirectory($dbPath);

        $result = ['tables' => [], 'total_records' => 0, 'size_mb' => 0];

        foreach ($this->criticalTables as $table) {
            $records = DB::table($table)->get();
            $filename = "{$dbPath}/{$table}.json";
            
            Storage::put($filename, json_encode($records, JSON_PRETTY_PRINT));
            
            $result['tables'][$table] = [
                'records' => count($records),
                'file' => $filename,
                'size_kb' => Storage::size($filename) / 1024,
            ];
            
            $result['total_records'] += count($records);
        }

        $result['size_mb'] = array_sum(array_column($result['tables'], 'size_kb')) / 1024;
        return $result;
    }

    protected function createFileSystemBackup(string $backupPath): array
    {
        $filePath = "{$backupPath}/files";
        Storage::makeDirectory($filePath);
        
        // Backup critical application files
        $criticalFiles = [
            'app/Models',
            'app/Services', 
            'config',
            'database/migrations',
            'storage/app/exports',
        ];

        $result = ['files' => [], 'total_files' => 0, 'size_mb' => 0];

        foreach ($criticalFiles as $path) {
            if (is_dir(base_path($path))) {
                $this->copyDirectory(base_path($path), storage_path("app/{$filePath}/" . basename($path)));
                $result['files'][] = $path;
                $result['total_files']++;
            }
        }

        return $result;
    }

    protected function verifyBackupIntegrity(string $backupPath): array
    {
        $verification = ['status' => 'valid', 'checks' => []];

        // Verify database backup
        foreach ($this->criticalTables as $table) {
            $filename = "{$backupPath}/database/{$table}.json";
            
            if (!Storage::exists($filename)) {
                $verification['status'] = 'invalid';
                $verification['checks'][$table] = 'missing_file';
                continue;
            }

            $content = Storage::get($filename);
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $verification['status'] = 'invalid';
                $verification['checks'][$table] = 'corrupted_json';
                continue;
            }

            $originalCount = DB::table($table)->count();
            $backupCount = count($data);
            
            if ($originalCount !== $backupCount) {
                $verification['status'] = 'invalid';
                $verification['checks'][$table] = "record_mismatch_{$originalCount}_vs_{$backupCount}";
            } else {
                $verification['checks'][$table] = 'valid';
            }
        }

        return $verification;
    }

    public function restoreFromBackup(string $backupPath): array
    {
        try {
            if (!Storage::exists($backupPath)) {
                throw new Exception("Backup path not found: {$backupPath}");
            }

            // Verify backup before restore
            $verification = $this->verifyBackupIntegrity($backupPath);
            if ($verification['status'] !== 'valid') {
                throw new Exception('Backup verification failed');
            }

            DB::beginTransaction();

            $restored = ['tables' => [], 'total_records' => 0];

            foreach ($this->criticalTables as $table) {
                $filename = "{$backupPath}/database/{$table}.json";
                $content = Storage::get($filename);
                $data = json_decode($content, true);

                // Backup current data
                $currentData = DB::table($table)->get();
                Storage::put("backups/pre_restore_{$table}_" . time() . ".json", 
                    json_encode($currentData, JSON_PRETTY_PRINT));

                // Clear and restore
                DB::table($table)->truncate();
                
                if (!empty($data)) {
                    DB::table($table)->insert($data);
                }

                $restored['tables'][$table] = count($data);
                $restored['total_records'] += count($data);
            }

            DB::commit();

            $this->notifyRestoreCompletion($restored);
            
            return [
                'success' => true,
                'restored' => $restored,
                'backup_path' => $backupPath,
                'restored_at' => now(),
            ];

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Restore failed', ['error' => $e->getMessage()]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function copyDirectory($src, $dst): void
    {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        
        closedir($dir);
    }

    protected function notifyBackupCompletion(array $result): void
    {
        $this->notificationService->sendSystemNotification('backup_completed', [
            'recipient' => 'admin',
            'total_records' => $result['database_backup']['total_records'],
            'size_mb' => round($result['database_backup']['size_mb'], 2),
            'duration' => $result['duration'],
        ]);
    }

    protected function notifyBackupFailure(string $error): void
    {
        $this->notificationService->sendSystemNotification('backup_failed', [
            'recipient' => 'admin',
            'error' => $error,
        ]);
    }

    protected function notifyRestoreCompletion(array $restored): void
    {
        $this->notificationService->sendSystemNotification('restore_completed', [
            'recipient' => 'admin',
            'total_records' => $restored['total_records'],
            'tables_count' => count($restored['tables']),
        ]);
    }
}
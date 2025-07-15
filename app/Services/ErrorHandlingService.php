<?php

namespace App\Services;

use App\Exceptions\DokterkunException;
use App\Exceptions\BusinessLogicException;
use App\Exceptions\SystemException;
use App\Exceptions\ValidationException;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException as LaravelValidationException;

class ErrorHandlingService
{
    protected $errorMappings = [
        'SQLSTATE[23000]' => 'DUPLICATE_ENTRY',
        'SQLSTATE[42S02]' => 'TABLE_NOT_FOUND',
        'SQLSTATE[42000]' => 'SYNTAX_ERROR',
        'SQLSTATE[HY000]' => 'GENERAL_ERROR',
        'Connection refused' => 'CONNECTION_ERROR',
        'Access denied' => 'ACCESS_DENIED',
        'Disk full' => 'DISK_FULL',
        'Memory limit' => 'MEMORY_LIMIT',
        'Maximum execution time' => 'TIMEOUT',
    ];

    public function handleException(Exception $exception): DokterkunException
    {
        // Log the original exception
        $this->logException($exception);

        // Convert to appropriate DokterkunException
        return $this->convertException($exception);
    }

    protected function convertException(Exception $exception): DokterkunException
    {
        // Already a DokterkunException
        if ($exception instanceof DokterkunException) {
            return $exception;
        }

        // Laravel validation exception
        if ($exception instanceof LaravelValidationException) {
            return new ValidationException(
                $exception->errors(),
                $exception->getMessage(),
                'Data yang diberikan tidak valid. Mohon periksa kembali.'
            );
        }

        // Database query exception
        if ($exception instanceof QueryException) {
            return $this->handleQueryException($exception);
        }

        // File system exceptions
        if ($this->isFileSystemException($exception)) {
            return SystemException::fileSystemError(
                'file_operation',
                $exception->getMessage()
            );
        }

        // Memory limit exceptions
        if ($this->isMemoryLimitException($exception)) {
            return SystemException::memoryLimitExceeded('operation');
        }

        // Timeout exceptions
        if ($this->isTimeoutException($exception)) {
            return SystemException::timeoutError('operation', 30);
        }

        // Generic system exception
        return new SystemException(
            $exception->getMessage(),
            'Terjadi kesalahan sistem yang tidak terduga.',
            500,
            'UNEXPECTED_ERROR',
            [
                'exception_type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]
        );
    }

    protected function handleQueryException(QueryException $exception): DokterkunException
    {
        $errorCode = $exception->errorInfo[0] ?? '';
        $message = $exception->getMessage();

        // Duplicate entry
        if (str_contains($message, 'Duplicate entry')) {
            preg_match('/Duplicate entry \'([^\']+)\' for key \'([^\']+)\'/', $message, $matches);
            $value = $matches[1] ?? 'unknown';
            $field = $matches[2] ?? 'unknown';
            
            return BusinessLogicException::duplicateEntry($field, $value);
        }

        // Foreign key constraint
        if (str_contains($message, 'foreign key constraint')) {
            return BusinessLogicException::recordInUse('record', 'unknown');
        }

        // Table not found
        if (str_contains($message, "Table") && str_contains($message, "doesn't exist")) {
            return SystemException::databaseError('table_access', 'Table not found');
        }

        // Column not found
        if (str_contains($message, "Unknown column")) {
            return SystemException::databaseError('column_access', 'Column not found');
        }

        // Generic database error
        return SystemException::databaseError('query_execution', $message);
    }

    protected function isFileSystemException(Exception $exception): bool
    {
        $message = $exception->getMessage();
        $fileSystemErrors = [
            'file_get_contents',
            'file_put_contents',
            'fopen',
            'mkdir',
            'rmdir',
            'unlink',
            'Permission denied',
            'No such file or directory',
            'Disk full',
        ];

        foreach ($fileSystemErrors as $error) {
            if (str_contains($message, $error)) {
                return true;
            }
        }

        return false;
    }

    protected function isMemoryLimitException(Exception $exception): bool
    {
        return str_contains($exception->getMessage(), 'memory limit') ||
               str_contains($exception->getMessage(), 'Allowed memory size');
    }

    protected function isTimeoutException(Exception $exception): bool
    {
        return str_contains($exception->getMessage(), 'Maximum execution time') ||
               str_contains($exception->getMessage(), 'timeout');
    }

    protected function logException(Exception $exception): void
    {
        $context = [
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ];

        // Add database context if available
        if (DB::logging()) {
            $context['last_query'] = DB::getQueryLog();
        }

        Log::error('Exception occurred: ' . $exception->getMessage(), $context);
    }

    public function wrapOperation(callable $operation, string $operationName = 'operation')
    {
        try {
            return $operation();
        } catch (Exception $e) {
            throw $this->handleException($e);
        }
    }

    public function formatErrorForUser(DokterkunException $exception): array
    {
        return [
            'title' => $this->getErrorTitle($exception),
            'message' => $exception->getUserMessage(),
            'type' => $this->getErrorType($exception),
            'code' => $exception->getErrorCode(),
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'suggestion' => $this->getErrorSuggestion($exception),
        ];
    }

    protected function getErrorTitle(DokterkunException $exception): string
    {
        $titles = [
            'VALIDATION_ERROR' => 'Data Tidak Valid',
            'BUSINESS_LOGIC_ERROR' => 'Aturan Bisnis Dilanggar',
            'PATIENT_NOT_FOUND' => 'Pasien Tidak Ditemukan',
            'DOCTOR_NOT_AVAILABLE' => 'Dokter Tidak Tersedia',
            'DUPLICATE_ENTRY' => 'Data Sudah Ada',
            'INSUFFICIENT_PERMISSION' => 'Akses Ditolak',
            'DATABASE_ERROR' => 'Kesalahan Database',
            'FILESYSTEM_ERROR' => 'Kesalahan File System',
            'EXTERNAL_SERVICE_ERROR' => 'Layanan Eksternal Bermasalah',
            'SYSTEM_ERROR' => 'Kesalahan Sistem',
        ];

        return $titles[$exception->getErrorCode()] ?? 'Kesalahan Tidak Diketahui';
    }

    protected function getErrorType(DokterkunException $exception): string
    {
        $code = $exception->getErrorCode();
        
        if (str_contains($code, 'VALIDATION')) return 'warning';
        if (str_contains($code, 'BUSINESS_LOGIC')) return 'info';
        if (str_contains($code, 'PERMISSION')) return 'danger';
        if (str_contains($code, 'SYSTEM')) return 'danger';
        if (str_contains($code, 'DATABASE')) return 'danger';
        
        return 'warning';
    }

    protected function getErrorSuggestion(DokterkunException $exception): string
    {
        $suggestions = [
            'VALIDATION_ERROR' => 'Periksa kembali data yang Anda masukkan dan pastikan semua field yang wajib telah diisi.',
            'PATIENT_NOT_FOUND' => 'Pastikan nomor rekam medis yang Anda masukkan benar atau buat data pasien baru.',
            'DOCTOR_NOT_AVAILABLE' => 'Pilih dokter lain yang tersedia atau coba lagi nanti.',
            'DUPLICATE_ENTRY' => 'Gunakan data yang berbeda atau perbarui data yang sudah ada.',
            'INSUFFICIENT_PERMISSION' => 'Hubungi administrator untuk mendapatkan akses yang diperlukan.',
            'DATABASE_ERROR' => 'Jika masalah berlanjut, hubungi tim teknis.',
            'SYSTEM_ERROR' => 'Coba lagi dalam beberapa menit. Jika masalah berlanjut, hubungi administrator.',
        ];

        return $suggestions[$exception->getErrorCode()] ?? 'Hubungi administrator jika masalah berlanjut.';
    }
}
<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SystemException extends DokterkunException
{
    public function __construct(
        string $message = 'Terjadi kesalahan sistem.',
        string $userMessage = 'Sistem sedang mengalami gangguan. Silakan coba lagi nanti.',
        int $code = 500,
        string $errorCode = 'SYSTEM_ERROR',
        array $errorContext = []
    ) {
        parent::__construct(
            $message,
            $userMessage,
            $code,
            $errorCode,
            $errorContext,
            'critical'
        );
    }

    public static function databaseError(string $operation, string $details = ''): self
    {
        return new self(
            "Database error during {$operation}: {$details}",
            "Terjadi kesalahan pada database. Silakan coba lagi nanti.",
            500,
            'DATABASE_ERROR',
            ['operation' => $operation, 'details' => $details]
        );
    }

    public static function fileSystemError(string $operation, string $path = ''): self
    {
        return new self(
            "File system error during {$operation}: {$path}",
            "Terjadi kesalahan pada sistem file. Silakan coba lagi nanti.",
            500,
            'FILESYSTEM_ERROR',
            ['operation' => $operation, 'path' => $path]
        );
    }

    public static function externalServiceError(string $service, string $details = ''): self
    {
        return new self(
            "External service error: {$service} - {$details}",
            "Layanan eksternal sedang tidak tersedia. Silakan coba lagi nanti.",
            503,
            'EXTERNAL_SERVICE_ERROR',
            ['service' => $service, 'details' => $details]
        );
    }

    public static function configurationError(string $config, string $details = ''): self
    {
        return new self(
            "Configuration error: {$config} - {$details}",
            "Terjadi kesalahan konfigurasi sistem. Silakan hubungi administrator.",
            500,
            'CONFIGURATION_ERROR',
            ['config' => $config, 'details' => $details]
        );
    }

    public static function memoryLimitExceeded(string $operation): self
    {
        return new self(
            "Memory limit exceeded during {$operation}",
            "Operasi membutuhkan memori yang terlalu besar. Silakan coba dengan data yang lebih kecil.",
            500,
            'MEMORY_LIMIT_EXCEEDED',
            ['operation' => $operation]
        );
    }

    public static function timeoutError(string $operation, int $timeout): self
    {
        return new self(
            "Timeout error during {$operation} after {$timeout} seconds",
            "Operasi membutuhkan waktu terlalu lama. Silakan coba lagi nanti.",
            408,
            'TIMEOUT_ERROR',
            ['operation' => $operation, 'timeout' => $timeout]
        );
    }
}
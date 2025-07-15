<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BusinessLogicException extends DokterkunException
{
    public function __construct(
        string $message = 'Terjadi kesalahan dalam proses bisnis.',
        string $userMessage = 'Operasi tidak dapat dilakukan karena tidak memenuhi aturan bisnis.',
        int $code = 400,
        string $errorCode = 'BUSINESS_LOGIC_ERROR',
        array $errorContext = []
    ) {
        parent::__construct(
            $message,
            $userMessage,
            $code,
            $errorCode,
            $errorContext,
            'warning'
        );
    }

    public static function pasienNotFound(string $rekamMedis): self
    {
        return new self(
            "Pasien dengan rekam medis {$rekamMedis} tidak ditemukan",
            "Pasien dengan nomor rekam medis {$rekamMedis} tidak ditemukan dalam sistem.",
            404,
            'PATIENT_NOT_FOUND',
            ['rekam_medis' => $rekamMedis]
        );
    }

    public static function dokterNotAvailable(string $dokterName): self
    {
        return new self(
            "Dokter {$dokterName} tidak tersedia",
            "Dokter {$dokterName} sedang tidak tersedia untuk melayani pasien.",
            400,
            'DOCTOR_NOT_AVAILABLE',
            ['dokter_name' => $dokterName]
        );
    }

    public static function invalidTindakan(string $tindakanName): self
    {
        return new self(
            "Tindakan {$tindakanName} tidak valid",
            "Tindakan {$tindakanName} tidak tersedia atau tidak aktif.",
            400,
            'INVALID_TINDAKAN',
            ['tindakan_name' => $tindakanName]
        );
    }

    public static function insufficientPermission(string $action): self
    {
        return new self(
            "Akses ditolak untuk aksi: {$action}",
            "Anda tidak memiliki izin untuk melakukan aksi ini.",
            403,
            'INSUFFICIENT_PERMISSION',
            ['action' => $action]
        );
    }

    public static function duplicateEntry(string $field, string $value): self
    {
        return new self(
            "Duplicate entry untuk field {$field}: {$value}",
            "Data dengan {$field} '{$value}' sudah ada dalam sistem.",
            409,
            'DUPLICATE_ENTRY',
            ['field' => $field, 'value' => $value]
        );
    }

    public static function invalidDateRange(string $startDate, string $endDate): self
    {
        return new self(
            "Invalid date range: {$startDate} to {$endDate}",
            "Rentang tanggal tidak valid. Tanggal mulai harus lebih kecil dari tanggal akhir.",
            400,
            'INVALID_DATE_RANGE',
            ['start_date' => $startDate, 'end_date' => $endDate]
        );
    }

    public static function recordInUse(string $recordType, string $recordId): self
    {
        return new self(
            "Record {$recordType} dengan ID {$recordId} sedang digunakan",
            "Data tidak dapat dihapus karena masih digunakan oleh data lain.",
            409,
            'RECORD_IN_USE',
            ['record_type' => $recordType, 'record_id' => $recordId]
        );
    }
}
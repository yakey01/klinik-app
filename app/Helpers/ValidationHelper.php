<?php

namespace App\Helpers;

use App\Exceptions\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException as LaravelValidationException;

class ValidationHelper
{
    /**
     * Validate data and throw DokterkunException on failure
     */
    public static function validate(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        try {
            return Validator::make($data, $rules, $messages, $attributes)->validate();
        } catch (LaravelValidationException $e) {
            throw new ValidationException(
                $e->errors(),
                'Data validasi gagal',
                'Mohon periksa kembali data yang Anda masukkan.'
            );
        }
    }

    /**
     * Validate data and return validator instance
     */
    public static function make(array $data, array $rules, array $messages = [], array $attributes = []): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $rules, $messages, $attributes);
    }

    /**
     * Get common validation rules for different data types
     */
    public static function getCommonRules(): array
    {
        return [
            'pasien' => [
                'nama' => 'required|string|max:255',
                'tanggal_lahir' => 'required|date|before:today',
                'jenis_kelamin' => 'required|in:L,P',
                'alamat' => 'nullable|string|max:500',
                'no_telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'no_rekam_medis' => 'required|string|max:20|unique:pasien,no_rekam_medis',
            ],
            'dokter' => [
                'nama_lengkap' => 'required|string|max:255',
                'nik' => 'required|string|max:20|unique:dokter,nik',
                'tanggal_lahir' => 'required|date|before:today',
                'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
                'jabatan' => 'required|in:dokter_umum,dokter_spesialis,dokter_gigi',
                'nomor_sip' => 'required|string|max:50|unique:dokter,nomor_sip',
                'email' => 'required|email|max:255|unique:dokter,email',
                'aktif' => 'required|boolean',
            ],
            'tindakan' => [
                'pasien_id' => 'required|exists:pasien,id',
                'dokter_id' => 'required|exists:dokter,id',
                'jenis_tindakan_id' => 'required|exists:jenis_tindakan,id',
                'tanggal_tindakan' => 'required|date',
                'deskripsi' => 'nullable|string|max:500',
                'biaya' => 'required|numeric|min:0',
                'status' => 'required|in:pending,selesai,batal',
            ],
            'pendapatan' => [
                'tanggal' => 'required|date',
                'keterangan' => 'required|string|max:255',
                'nominal' => 'required|numeric|min:0',
                'kategori' => 'required|in:tindakan,konsultasi,obat,administrasi,lainnya',
                'tindakan_id' => 'nullable|exists:tindakan,id',
            ],
            'pengeluaran' => [
                'tanggal' => 'required|date',
                'keterangan' => 'required|string|max:255',
                'nominal' => 'required|numeric|min:0',
                'kategori' => 'required|in:operasional,alat_kesehatan,obat,administrasi,lainnya',
            ],
            'pendapatan_harian' => [
                'tanggal_input' => 'required|date',
                'shift' => 'required|in:Pagi,Sore',
                'total_pendapatan_tunai' => 'required|numeric|min:0',
                'total_pendapatan_transfer' => 'required|numeric|min:0',
                'total_pendapatan_bpjs' => 'required|numeric|min:0',
                'user_id' => 'required|exists:users,id',
            ],
            'pengeluaran_harian' => [
                'tanggal_input' => 'required|date',
                'shift' => 'required|in:Pagi,Sore',
                'total_pengeluaran' => 'required|numeric|min:0',
                'keterangan' => 'required|string|max:255',
                'user_id' => 'required|exists:users,id',
            ],
            'jumlah_pasien_harian' => [
                'tanggal' => 'required|date',
                'poli' => 'required|in:umum,gigi',
                'jumlah_pasien_umum' => 'required|integer|min:0',
                'jumlah_pasien_bpjs' => 'required|integer|min:0',
                'dokter_id' => 'required|exists:dokter,id',
            ],
        ];
    }

    /**
     * Get validation messages in Indonesian
     */
    public static function getMessages(): array
    {
        return [
            'required' => ':attribute harus diisi.',
            'string' => ':attribute harus berupa text.',
            'max' => ':attribute maksimal :max karakter.',
            'min' => ':attribute minimal :min.',
            'numeric' => ':attribute harus berupa angka.',
            'integer' => ':attribute harus berupa angka bulat.',
            'email' => ':attribute harus berupa email yang valid.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'before' => ':attribute harus sebelum :date.',
            'after' => ':attribute harus setelah :date.',
            'in' => ':attribute harus salah satu dari: :values.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'unique' => ':attribute sudah digunakan.',
            'boolean' => ':attribute harus berupa true atau false.',
        ];
    }

    /**
     * Get field attributes in Indonesian
     */
    public static function getAttributes(): array
    {
        return [
            'nama' => 'Nama',
            'nama_lengkap' => 'Nama Lengkap',
            'tanggal_lahir' => 'Tanggal Lahir',
            'jenis_kelamin' => 'Jenis Kelamin',
            'alamat' => 'Alamat',
            'no_telepon' => 'No. Telepon',
            'email' => 'Email',
            'no_rekam_medis' => 'No. Rekam Medis',
            'nik' => 'NIK',
            'jabatan' => 'Jabatan',
            'nomor_sip' => 'Nomor SIP',
            'aktif' => 'Status Aktif',
            'pasien_id' => 'Pasien',
            'dokter_id' => 'Dokter',
            'jenis_tindakan_id' => 'Jenis Tindakan',
            'tanggal_tindakan' => 'Tanggal Tindakan',
            'deskripsi' => 'Deskripsi',
            'biaya' => 'Biaya',
            'status' => 'Status',
            'tanggal' => 'Tanggal',
            'keterangan' => 'Keterangan',
            'nominal' => 'Nominal',
            'kategori' => 'Kategori',
            'tindakan_id' => 'Tindakan',
            'tanggal_input' => 'Tanggal Input',
            'shift' => 'Shift',
            'total_pendapatan_tunai' => 'Total Pendapatan Tunai',
            'total_pendapatan_transfer' => 'Total Pendapatan Transfer',
            'total_pendapatan_bpjs' => 'Total Pendapatan BPJS',
            'user_id' => 'User',
            'total_pengeluaran' => 'Total Pengeluaran',
            'poli' => 'Poliklinik',
            'jumlah_pasien_umum' => 'Jumlah Pasien Umum',
            'jumlah_pasien_bpjs' => 'Jumlah Pasien BPJS',
        ];
    }

    /**
     * Validate specific data type with predefined rules
     */
    public static function validateDataType(string $type, array $data): array
    {
        $rules = self::getCommonRules()[$type] ?? [];
        $messages = self::getMessages();
        $attributes = self::getAttributes();

        return self::validate($data, $rules, $messages, $attributes);
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $data, array $allowedTypes = ['xlsx', 'csv', 'json'], int $maxSize = 10240): array
    {
        $rules = [
            'file' => [
                'required',
                'file',
                'max:' . $maxSize,
                'mimes:' . implode(',', $allowedTypes),
            ],
        ];

        $messages = array_merge(self::getMessages(), [
            'file.mimes' => 'File harus berupa: ' . implode(', ', $allowedTypes),
            'file.max' => 'Ukuran file maksimal ' . ($maxSize / 1024) . 'MB',
        ]);

        return self::validate($data, $rules, $messages);
    }
}
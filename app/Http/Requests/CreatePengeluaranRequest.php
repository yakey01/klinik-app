<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePengeluaranRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kode_pengeluaran' => [
                'required',
                'string',
                'max:50',
                'regex:/^PNG-\d{4}$/',
                Rule::unique('pengeluaran', 'kode_pengeluaran')->ignore($this->route('record')),
            ],
            'nama_pengeluaran' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pengeluaran', 'nama_pengeluaran')->ignore($this->route('record')),
            ],
            'kategori' => [
                'required',
                'string',
                'in:operasional,medis,maintenance,administrasi,lainnya',
            ],
            'keterangan' => [
                'nullable',
                'string',
                'max:500',
            ],
            'status_validasi' => [
                'required',
                'string',
                'in:pending,disetujui,ditolak',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_pengeluaran.required' => 'Kode pengeluaran wajib diisi.',
            'kode_pengeluaran.unique' => 'Kode pengeluaran sudah digunakan.',
            'kode_pengeluaran.regex' => 'Format kode pengeluaran harus PNG-0001.',
            'nama_pengeluaran.required' => 'Nama pengeluaran wajib diisi.',
            'nama_pengeluaran.unique' => 'Nama pengeluaran sudah digunakan.',
            'nama_pengeluaran.max' => 'Nama pengeluaran maksimal 255 karakter.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'kategori.in' => 'Kategori harus salah satu dari: operasional, medis, maintenance, administrasi, lainnya.',
            'keterangan.max' => 'Keterangan maksimal 500 karakter.',
            'status_validasi.required' => 'Status validasi wajib dipilih.',
            'status_validasi.in' => 'Status validasi harus: pending, disetujui, atau ditolak.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'kode_pengeluaran' => 'kode pengeluaran',
            'nama_pengeluaran' => 'nama pengeluaran',
            'kategori' => 'kategori',
            'keterangan' => 'keterangan',
            'status_validasi' => 'status validasi',
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePendapatanRequest extends FormRequest
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
            'kode_pendapatan' => [
                'required',
                'string',
                'max:20',
                'regex:/^PND-\d{4}$/',
                Rule::unique('pendapatan', 'kode_pendapatan')->ignore($this->route('record')),
            ],
            'nama_pendapatan' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pendapatan', 'nama_pendapatan')->ignore($this->route('record')),
            ],
            'sumber_pendapatan' => [
                'required',
                'string',
                'in:Umum,Gigi',
            ],
            'is_aktif' => [
                'boolean',
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
            'kode_pendapatan.required' => 'Kode pendapatan wajib diisi.',
            'kode_pendapatan.unique' => 'Kode pendapatan sudah digunakan.',
            'kode_pendapatan.regex' => 'Format kode pendapatan harus PND-0001.',
            'nama_pendapatan.required' => 'Nama pendapatan wajib diisi.',
            'nama_pendapatan.unique' => 'Nama pendapatan sudah digunakan.',
            'nama_pendapatan.max' => 'Nama pendapatan maksimal 100 karakter.',
            'sumber_pendapatan.required' => 'Sumber pendapatan wajib dipilih.',
            'sumber_pendapatan.in' => 'Sumber pendapatan harus Umum atau Gigi.',
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
            'kode_pendapatan' => 'kode pendapatan',
            'nama_pendapatan' => 'nama pendapatan',
            'sumber_pendapatan' => 'sumber pendapatan',
            'is_aktif' => 'status aktif',
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ConsistentRoleAssignmentRule;
use App\Models\Role;

class CreateUserWithRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user() && auth()->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $source = $this->input('source');
        $employeeType = $this->input('employee_type');
        $relatedRecordId = $this->input('related_record_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username'],
            'nip' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'source' => ['required', 'string', 'in:dokter,pegawai'],
            'employee_type' => ['nullable', 'string', 'in:paramedis,non_paramedis'],
            'role_id' => [
                'required',
                'exists:roles,id',
                new ConsistentRoleAssignmentRule($source, $employeeType, $relatedRecordId)
            ],
            'is_active' => ['boolean'],
            'tanggal_bergabung' => ['nullable', 'date'],
            'no_telepon' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh user lain.',
            'username.unique' => 'Username sudah digunakan oleh user lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'source.required' => 'Sumber pembuatan user wajib ditentukan.',
            'source.in' => 'Sumber harus berupa dokter atau pegawai.',
            'employee_type.in' => 'Jenis pegawai harus paramedis atau non_paramedis.',
            'role_id.required' => 'Role wajib dipilih.',
            'role_id.exists' => 'Role yang dipilih tidak valid.',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'email' => 'email',
            'username' => 'username',
            'nip' => 'NIP',
            'password' => 'password',
            'source' => 'sumber',
            'employee_type' => 'jenis pegawai',
            'role_id' => 'role',
            'is_active' => 'status aktif',
            'tanggal_bergabung' => 'tanggal bergabung',
            'no_telepon' => 'nomor telepon',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Log validation errors for debugging
        \Illuminate\Support\Facades\Log::error('User creation validation failed', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all(),
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Get validated data with computed role assignment
     */
    public function getValidatedDataWithRole(): array
    {
        $validated = $this->validated();
        
        // Auto-assign role if not explicitly set
        if (empty($validated['role_id'])) {
            $validated['role_id'] = $this->getAutoAssignedRoleId();
        }

        return $validated;
    }

    /**
     * Auto-assign role based on source and employee type
     */
    private function getAutoAssignedRoleId(): ?int
    {
        $source = $this->input('source');
        $employeeType = $this->input('employee_type');

        if ($source === 'dokter') {
            return Role::where('name', 'dokter')->first()?->id;
        }

        if ($source === 'pegawai' && $employeeType === 'paramedis') {
            return Role::where('name', 'paramedis')->first()?->id;
        }

        if ($source === 'pegawai' && $employeeType === 'non_paramedis') {
            return Role::where('name', 'petugas')->first()?->id;
        }

        return null;
    }
}
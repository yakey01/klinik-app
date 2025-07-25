<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Role;
use App\Models\Dokter;
use App\Models\Pegawai;

class ConsistentRoleAssignmentRule implements ValidationRule
{
    private string $source;
    private ?string $employeeType;
    private ?int $relatedRecordId;

    public function __construct(string $source, ?string $employeeType = null, ?int $relatedRecordId = null)
    {
        $this->source = $source;
        $this->employeeType = $employeeType;
        $this->relatedRecordId = $relatedRecordId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $role = Role::find($value);
        
        if (!$role) {
            $fail('Role tidak ditemukan dalam sistem.');
            return;
        }

        // Validate role consistency based on source
        if ($this->source === 'dokter') {
            $this->validateDokterRole($role, $fail);
        } elseif ($this->source === 'pegawai') {
            $this->validatePegawaiRole($role, $fail);
        }

        // Additional validation for existing record linkage
        if ($this->relatedRecordId) {
            $this->validateExistingRecordConsistency($role, $fail);
        }
    }

    /**
     * Validate that dokter role is assigned for dokter source
     */
    private function validateDokterRole(Role $role, Closure $fail): void
    {
        if ($role->name !== 'dokter') {
            $fail('User yang dibuat dari manajemen dokter harus memiliki role "dokter".');
        }
    }

    /**
     * Validate that appropriate role is assigned for pegawai source
     */
    private function validatePegawaiRole(Role $role, Closure $fail): void
    {
        $allowedRoles = ['paramedis', 'petugas', 'manajer', 'bendahara'];
        
        if (!in_array($role->name, $allowedRoles)) {
            $fail('User yang dibuat dari manajemen pegawai harus memiliki role: ' . implode(', ', $allowedRoles));
            return;
        }

        // Validate role consistency with employee type
        if ($this->employeeType) {
            if ($this->employeeType === 'paramedis' && $role->name !== 'paramedis') {
                $fail('Pegawai dengan jenis "Paramedis" harus memiliki role "paramedis".');
            }
            
            if ($this->employeeType === 'non_paramedis' && $role->name === 'paramedis') {
                $fail('Pegawai dengan jenis "Non-Paramedis" tidak dapat memiliki role "paramedis".');
            }
        }
    }

    /**
     * Validate consistency with existing linked records
     */
    private function validateExistingRecordConsistency(Role $role, Closure $fail): void
    {
        if ($this->source === 'dokter') {
            $dokter = Dokter::find($this->relatedRecordId);
            if ($dokter && $role->name !== 'dokter') {
                $fail("Role harus 'dokter' untuk user yang terhubung dengan data dokter '{$dokter->nama_lengkap}'.");
            }
        } elseif ($this->source === 'pegawai') {
            $pegawai = Pegawai::find($this->relatedRecordId);
            if ($pegawai) {
                if ($pegawai->jenis_pegawai === 'Paramedis' && $role->name !== 'paramedis') {
                    $fail("Role harus 'paramedis' untuk user yang terhubung dengan pegawai paramedis '{$pegawai->nama_lengkap}'.");
                }
            }
        }
    }
}
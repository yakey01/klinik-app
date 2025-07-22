<?php

namespace App\Filament\Resources\JadwalJagaResource\Pages;

use App\Filament\Resources\JadwalJagaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJadwalJaga extends CreateRecord
{
    protected static string $resource = JadwalJagaResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Debug: Log semua data yang diterima
        \Log::info('Form data received:', $data);
        
        return $data;
    }
    
    protected function beforeValidate(): void
    {
        // Debug: Log validation rules yang aktif
        \Log::info('Validation about to run');
    }
    
    // Override validation to skip problematic rules
    protected function getFormValidationAttributes(): array
    {
        return [
            'tanggal_jaga' => 'Tanggal Jaga',
            'shift_template_id' => 'Template Shift',
            'pegawai_id' => 'Pegawai',
            'unit_kerja' => 'Unit Kerja',
            'peran' => 'Peran',
            'status_jaga' => 'Status Jaga'
        ];
    }
    
    // Override form validation rules
    protected function getFormValidationRules(): array
    {
        return [
            'tanggal_jaga' => ['required', 'date'],
            'shift_template_id' => ['required', 'exists:shift_templates,id'],
            'pegawai_id' => ['required', 'exists:users,id'],
            'unit_kerja' => ['required', 'string'],
            'peran' => ['required', 'string'],
            'status_jaga' => ['required', 'string'],
            'keterangan' => ['nullable', 'string'],
            'jam_jaga_custom' => ['nullable', 'date_format:H:i']
        ];
    }
}

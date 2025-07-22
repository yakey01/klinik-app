<?php

namespace App\Filament\Resources\JadwalJagaResource\Pages;

use App\Filament\Resources\JadwalJagaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJadwalJaga extends EditRecord
{
    protected static string $resource = JadwalJagaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    // Debug logging for edit
    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::info('Edit form data received:', $data);
        return $data;
    }
    
    protected function beforeValidate(): void
    {
        \Log::info('Edit validation about to run');
    }
    
    // Override validation attributes
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
    
    // Override form validation rules - SAME as Create
    protected function getFormValidationRules(): array
    {
        return [
            'tanggal_jaga' => ['required', 'date'], // NO timestamp validation
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

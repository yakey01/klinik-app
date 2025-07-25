<?php

namespace App\Filament\Petugas\Resources\TindakanResource\Pages;

use App\Events\PatientCreated;
use App\Filament\Petugas\Resources\TindakanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTindakan extends CreateRecord
{
    protected static string $resource = TindakanResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Pre-fill pasien_id if passed via URL parameter
        if (request()->has('pasien_id')) {
            $data['pasien_id'] = request('pasien_id');
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['input_by'] = Auth::id();
        $data['status_validasi'] = 'pending';
        
        // Don't set default dokter_id - let user choose
        // Don't set default shift_id - make it required in form
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Dispatch PatientCreated event for Telegram notifications
        event(new PatientCreated([
            'patient_name' => $this->record->pasien?->nama ?? 'Unknown Patient',
            'procedure' => $this->record->jenisTindakan?->nama ?? 'Unknown Procedure',
            'user_name' => auth()->user()->name,
            'user_role' => auth()->user()->role?->name ?? 'petugas',
            'tindakan_id' => $this->record->id,
            'tarif' => $this->record->tarif ?? 0,
            'dokter' => $this->record->dokter?->nama ?? 'Unknown Doctor',
        ]));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
<?php

namespace App\Filament\Resources\PengeluaranResource\Pages;

use App\Filament\Resources\PengeluaranResource;
use App\Services\AutoCodeGeneratorService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreatePengeluaran extends CreateRecord
{
    protected static string $resource = PengeluaranResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    public function mount(): void
    {
        parent::mount();
        
        // Generate and set the auto-generated code when form is mounted
        $this->form->fill([
            'kode_pengeluaran' => AutoCodeGeneratorService::generatePengeluaranCode(),
        ]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Double-check and regenerate code if empty or conflicting
        if (empty($data['kode_pengeluaran'])) {
            $data['kode_pengeluaran'] = AutoCodeGeneratorService::generatePengeluaranCode();
        }
        
        // Set default values for system fields if not already set
        if (!isset($data['input_by'])) {
            $data['input_by'] = Auth::id();
        }
        
        if (!isset($data['tanggal'])) {
            $data['tanggal'] = now();
        }
        
        if (!isset($data['nominal'])) {
            $data['nominal'] = 0;
        }
        
        return $data;
    }
    
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            // Final safety check - regenerate code if it exists
            $existingRecord = $this->getModel()::where('kode_pengeluaran', $data['kode_pengeluaran'])->first();
            if ($existingRecord) {
                $data['kode_pengeluaran'] = AutoCodeGeneratorService::generatePengeluaranCode();
            }
            
            return parent::handleRecordCreation($data);
        });
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Data pengeluaran berhasil disimpan.';
    }
}

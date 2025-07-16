<?php

namespace App\Filament\Resources\PendapatanResource\Pages;

use App\Filament\Resources\PendapatanResource;
use App\Services\AutoCodeGeneratorService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreatePendapatan extends CreateRecord
{
    protected static string $resource = PendapatanResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    public function mount(): void
    {
        parent::mount();
        
        // Generate and set the auto-generated code when form is mounted
        $this->form->fill([
            'kode_pendapatan' => AutoCodeGeneratorService::generatePendapatanCode(),
        ]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Double-check and regenerate code if empty or conflicting
        if (empty($data['kode_pendapatan'])) {
            $data['kode_pendapatan'] = AutoCodeGeneratorService::generatePendapatanCode();
        }
        
        // Set default values for system fields
        $data['input_by'] = Auth::id();
        $data['status_validasi'] = 'pending';
        $data['tanggal'] = now();
        
        // Set default values for nullable fields to prevent constraint errors
        $data['keterangan'] = $data['nama_pendapatan'] ?? 'Pendapatan dari ' . ($data['sumber_pendapatan'] ?? 'sistem');
        $data['nominal'] = 0;
        $data['kategori'] = 'lain_lain';
        
        return $data;
    }
    
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            // Final safety check - regenerate code if it exists
            $existingRecord = $this->getModel()::where('kode_pendapatan', $data['kode_pendapatan'])->first();
            if ($existingRecord) {
                $data['kode_pendapatan'] = AutoCodeGeneratorService::generatePendapatanCode();
            }
            
            return parent::handleRecordCreation($data);
        });
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Data pendapatan berhasil disimpan.';
    }
    
    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan Data')
            ->icon('heroicon-o-check')
            ->color('success')
            ->size('lg');
    }
    
    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Simpan & Buat Baru')
            ->icon('heroicon-o-plus-circle')
            ->color('info');
    }
    
    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal')
            ->icon('heroicon-o-x-mark')
            ->color('gray');
    }
}

<?php

namespace App\Filament\Resources\PendapatanResource\Pages;

use App\Filament\Resources\PendapatanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePendapatan extends CreateRecord
{
    protected static string $resource = PendapatanResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
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

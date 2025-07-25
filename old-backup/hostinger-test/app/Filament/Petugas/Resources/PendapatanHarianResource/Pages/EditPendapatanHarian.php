<?php

namespace App\Filament\Petugas\Resources\PendapatanHarianResource\Pages;

use App\Filament\Petugas\Resources\PendapatanHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditPendapatanHarian extends EditRecord
{
    protected static string $resource = PendapatanHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record->status_validasi === 'pending'),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);
        
        // Redirect if already validated
        if ($this->record->status_validasi !== 'pending') {
            Notification::make()
                ->warning()
                ->title('🚫 Tidak Dapat Diedit')
                ->body('Data yang sudah divalidasi tidak dapat diubah lagi.')
                ->persistent()
                ->send();
                
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('✅ Pendapatan berhasil diperbarui')
            ->body('Data pendapatan harian telah berhasil diubah.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

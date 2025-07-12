<?php

namespace App\Filament\Petugas\Resources\PengeluaranHarianResource\Pages;

use App\Filament\Petugas\Resources\PengeluaranHarianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPengeluaranHarian extends EditRecord
{
    protected static string $resource = PengeluaranHarianResource::class;

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
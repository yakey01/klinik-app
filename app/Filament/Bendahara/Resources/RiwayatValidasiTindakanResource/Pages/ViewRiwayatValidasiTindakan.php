<?php

namespace App\Filament\Bendahara\Resources\RiwayatValidasiTindakanResource\Pages;

use App\Filament\Bendahara\Resources\RiwayatValidasiTindakanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewRiwayatValidasiTindakan extends ViewRecord
{
    protected static string $resource = RiwayatValidasiTindakanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('← Kembali')
                ->url($this->getResource()::getUrl('index'))
                ->color('gray'),
            
            Action::make('revert')
                ->label('🔄 Kembalikan ke Pending')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('🔄 Kembalikan ke Status Pending')
                ->modalDescription('Apakah Anda yakin ingin mengembalikan tindakan ini ke status pending untuk validasi ulang?')
                ->modalSubmitActionLabel('Kembalikan')
                ->visible(fn (): bool => auth()->user()->hasRole(['admin', 'bendahara']))
                ->action(function () {
                    try {
                        $this->record->update([
                            'status_validasi' => 'pending',
                            'status' => 'pending',
                            'validated_by' => null,
                            'validated_at' => null,
                            'komentar_validasi' => 'Dikembalikan untuk validasi ulang oleh ' . auth()->user()->name . ' pada ' . now()->format('d/m/Y H:i'),
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('✅ Berhasil')
                            ->body('Tindakan berhasil dikembalikan ke status pending')
                            ->success()
                            ->send();
                            
                        return redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('❌ Error')
                            ->body('Terjadi kesalahan: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Print functionality will be implemented later
            // Action::make('print')
            //     ->label('🖨️ Print')
            //     ->icon('heroicon-o-printer')
            //     ->color('info')
            //     ->action(function () {
            //         \Filament\Notifications\Notification::make()
            //             ->title('🖨️ Print')
            //             ->body('Print functionality coming soon')
            //             ->info()
            //             ->send();
            //     })
            //     ->visible(fn (): bool => $this->record->status_validasi === 'approved'),
        ];
    }

    public function getTitle(): string
    {
        return 'Detail Riwayat Validasi';
    }
}
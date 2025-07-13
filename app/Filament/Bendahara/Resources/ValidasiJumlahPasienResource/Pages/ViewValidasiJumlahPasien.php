<?php

namespace App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource\Pages;

use App\Filament\Bendahara\Resources\ValidasiJumlahPasienResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;

class ViewValidasiJumlahPasien extends ViewRecord
{
    protected static string $resource = ValidasiJumlahPasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('catatan_validasi')
                        ->label('Catatan Persetujuan')
                        ->placeholder('Tambahkan catatan jika diperlukan...')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->approve(auth()->user(), $data['catatan_validasi'] ?? null);
                    
                    // Trigger jaspel calculation
                    \App\Jobs\HitungJaspelPasienJob::dispatch($this->record->id);
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn (): bool => $this->record->isPending()),
                
            Actions\Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('catatan_validasi')
                        ->label('Alasan Penolakan')
                        ->placeholder('Jelaskan alasan penolakan...')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->reject(auth()->user(), $data['catatan_validasi']);
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn (): bool => $this->record->isPending()),
        ];
    }
}
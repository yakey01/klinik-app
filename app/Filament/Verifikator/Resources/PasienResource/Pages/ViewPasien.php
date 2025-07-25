<?php

namespace App\Filament\Verifikator\Resources\PasienResource\Pages;

use App\Filament\Verifikator\Resources\PasienResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPasien extends ViewRecord
{
    protected static string $resource = PasienResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('verify')
                ->label('✅ Verifikasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Verifikasi Data Pasien')
                ->modalDescription('Apakah Anda yakin data pasien ini sudah benar dan dapat diverifikasi?')
                ->modalSubmitActionLabel('Ya, Verifikasi')
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Catatan Verifikasi (Opsional)')
                        ->placeholder('Tambahkan catatan jika diperlukan')
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'verified',
                        'verified_at' => now(),
                        'verified_by' => auth()->id(),
                        'verification_notes' => $data['notes'] ?? null,
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('✅ Berhasil Diverifikasi')
                        ->body("Data pasien {$this->record->nama} telah diverifikasi.")
                        ->success()
                        ->send();
                        
                    $this->redirect(PasienResource::getUrl('index'));
                })
                ->visible(fn (): bool => $this->record->status === 'pending'),
            
            Actions\Action::make('reject')
                ->label('❌ Tolak')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Tolak Data Pasien')
                ->modalDescription('Data pasien akan ditolak. Pastikan Anda memberikan alasan penolakan.')
                ->modalSubmitActionLabel('Ya, Tolak')
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Alasan Penolakan')
                        ->placeholder('Jelaskan alasan penolakan data pasien')
                        ->required()
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'rejected',
                        'verified_at' => now(),
                        'verified_by' => auth()->id(),
                        'verification_notes' => $data['notes'],
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('❌ Data Ditolak')
                        ->body("Data pasien {$this->record->nama} telah ditolak.")
                        ->warning()
                        ->send();
                        
                    $this->redirect(PasienResource::getUrl('index'));
                })
                ->visible(fn (): bool => $this->record->status === 'pending'),
                
            Actions\Action::make('reset_status')
                ->label('🔄 Reset Status')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reset Status Verifikasi')
                ->modalDescription('Status verifikasi akan dikembalikan ke pending. Apakah Anda yakin?')
                ->modalSubmitActionLabel('Ya, Reset')
                ->action(function (): void {
                    $this->record->update([
                        'status' => 'pending',
                        'verified_at' => null,
                        'verified_by' => null,
                        'verification_notes' => null,
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('🔄 Status Direset')
                        ->body("Status verifikasi pasien {$this->record->nama} telah direset.")
                        ->info()
                        ->send();
                        
                    $this->redirect(PasienResource::getUrl('index'));
                })
                ->visible(fn (): bool => in_array($this->record->status, ['verified', 'rejected'])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Data Pasien')
                    ->schema([
                        Infolists\Components\TextEntry::make('no_rekam_medis')
                            ->label('No. Rekam Medis'),
                        Infolists\Components\TextEntry::make('nama')
                            ->label('Nama Lengkap')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold),
                        Infolists\Components\TextEntry::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->date('d F Y')
                            ->suffix(fn ($record) => $record->umur ? " ({$record->umur} tahun)" : ''),
                        Infolists\Components\TextEntry::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'L' => 'info',
                                'P' => 'success',
                            }),
                        Infolists\Components\TextEntry::make('alamat')
                            ->label('Alamat')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('no_telepon')
                            ->label('No. Telepon'),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Informasi Input')
                    ->schema([
                        Infolists\Components\TextEntry::make('inputBy.name')
                            ->label('Diinput Oleh'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal Input')
                            ->dateTime('d F Y H:i'),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Status Verifikasi')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending' => 'Menunggu Verifikasi',
                                'verified' => 'Terverifikasi',
                                'rejected' => 'Ditolak',
                            })
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'verified' => 'success',
                                'rejected' => 'danger',
                            }),
                        Infolists\Components\TextEntry::make('verifiedBy.name')
                            ->label('Diverifikasi Oleh')
                            ->placeholder('Belum diverifikasi'),
                        Infolists\Components\TextEntry::make('verified_at')
                            ->label('Tanggal Verifikasi')
                            ->dateTime('d F Y H:i')
                            ->placeholder('Belum diverifikasi'),
                        Infolists\Components\TextEntry::make('verification_notes')
                            ->label('Catatan Verifikasi')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
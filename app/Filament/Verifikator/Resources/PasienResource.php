<?php

namespace App\Filament\Verifikator\Resources;

use App\Filament\Verifikator\Resources\PasienResource\Pages;
use App\Models\Pasien;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;

class PasienResource extends Resource
{
    protected static ?string $model = Pasien::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationGroup = '📋 Verifikasi Pasien';
    
    protected static ?string $navigationLabel = 'Verifikasi Data Pasien';
    
    protected static ?string $modelLabel = 'Pasien';
    
    protected static ?string $pluralModelLabel = 'Verifikasi Data Pasien';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Pasien')
                    ->description('Data pasien yang diinput oleh petugas')
                    ->schema([
                        Forms\Components\TextInput::make('no_rekam_medis')
                            ->label('No. Rekam Medis')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->disabled(),
                        
                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->disabled(),
                        
                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->disabled()
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('no_telepon')
                            ->label('No. Telepon')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Forms\Components\Section::make('Informasi Input')
                    ->description('Informasi tentang yang menginput data')
                    ->schema([
                        Forms\Components\TextInput::make('inputBy.name')
                            ->label('Diinput Oleh')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Tanggal Input')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Forms\Components\Section::make('Verifikasi')
                    ->description('Proses verifikasi data pasien')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status Verifikasi')
                            ->options([
                                'pending' => 'Menunggu Verifikasi',
                                'verified' => 'Terverifikasi',
                                'rejected' => 'Ditolak',
                            ])
                            ->required()
                            ->live(),
                        
                        Forms\Components\Textarea::make('verification_notes')
                            ->label('Catatan Verifikasi')
                            ->placeholder('Masukkan catatan verifikasi (wajib jika ditolak)')
                            ->required(fn (Forms\Get $get): bool => $get('status') === 'rejected')
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->visible(fn (?Pasien $record): bool => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_rekam_medis')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),
                
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->limit(25)
                    ->sortable()
                    ->weight(FontWeight::SemiBold),
                
                Tables\Columns\TextColumn::make('tanggal_lahir')
                    ->label('Tgl. Lahir')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (Pasien $record): string => $record->umur ? $record->umur . ' tahun' : ''),
                
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'info',
                        'P' => 'success',
                    }),
                
                Tables\Columns\TextColumn::make('status')
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
                
                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Diinput Oleh')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl. Input')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Tgl. Verifikasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('verifiedBy.name')
                    ->label('Diverifikasi Oleh')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Verifikasi')
                    ->options([
                        'pending' => 'Menunggu Verifikasi',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('pending'),
                
                SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Input Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Input Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Action::make('verify')
                        ->label('✅ Verifikasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Verifikasi Data Pasien')
                        ->modalDescription('Apakah Anda yakin data pasien ini sudah benar dan dapat diverifikasi?')
                        ->modalSubmitActionLabel('Ya, Verifikasi')
                        ->form([
                            Forms\Components\Textarea::make('notes')
                                ->label('Catatan Verifikasi (Opsional)')
                                ->placeholder('Tambahkan catatan jika diperlukan')
                        ])
                        ->action(function (Pasien $record, array $data): void {
                            $record->update([
                                'status' => 'verified',
                                'verified_at' => now(),
                                'verified_by' => auth()->id(),
                                'verification_notes' => $data['notes'] ?? null,
                            ]);
                            
                            Notification::make()
                                ->title('✅ Berhasil Diverifikasi')
                                ->body("Data pasien {$record->nama} telah diverifikasi.")
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Pasien $record): bool => $record->status === 'pending'),
                    
                    Action::make('reject')
                        ->label('❌ Tolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Tolak Data Pasien')
                        ->modalDescription('Data pasien akan ditolak. Pastikan Anda memberikan alasan penolakan.')
                        ->modalSubmitActionLabel('Ya, Tolak')
                        ->form([
                            Forms\Components\Textarea::make('notes')
                                ->label('Alasan Penolakan')
                                ->placeholder('Jelaskan alasan penolakan data pasien')
                                ->required()
                        ])
                        ->action(function (Pasien $record, array $data): void {
                            $record->update([
                                'status' => 'rejected',
                                'verified_at' => now(),
                                'verified_by' => auth()->id(),
                                'verification_notes' => $data['notes'],
                            ]);
                            
                            Notification::make()
                                ->title('❌ Data Ditolak')
                                ->body("Data pasien {$record->nama} telah ditolak.")
                                ->warning()
                                ->send();
                        })
                        ->visible(fn (Pasien $record): bool => $record->status === 'pending'),
                    
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ Lihat Detail'),
                    
                    Action::make('reset_status')
                        ->label('🔄 Reset Status')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Status Verifikasi')
                        ->modalDescription('Status verifikasi akan dikembalikan ke pending. Apakah Anda yakin?')
                        ->modalSubmitActionLabel('Ya, Reset')
                        ->action(function (Pasien $record): void {
                            $record->update([
                                'status' => 'pending',
                                'verified_at' => null,
                                'verified_by' => null,
                                'verification_notes' => null,
                            ]);
                            
                            Notification::make()
                                ->title('🔄 Status Direset')
                                ->body("Status verifikasi pasien {$record->nama} telah direset.")
                                ->info()
                                ->send();
                        })
                        ->visible(fn (Pasien $record): bool => in_array($record->status, ['verified', 'rejected'])),
                ])
                ->label('⚙️ Aksi')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_verify')
                        ->label('✅ Verifikasi Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Verifikasi Data Pasien')
                        ->modalDescription('Verifikasi semua data pasien yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Verifikasi Semua')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (\Illuminate\Support\Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'verified',
                                        'verified_at' => now(),
                                        'verified_by' => auth()->id(),
                                    ]);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title('✅ Verifikasi Berhasil')
                                ->body("{$count} data pasien telah diverifikasi.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('Tidak ada data pasien')
            ->emptyStateDescription('Belum ada data pasien yang perlu diverifikasi.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['inputBy', 'verifiedBy'])
            ->orderBy('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPasiens::route('/'),
            'view' => Pages\ViewPasien::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
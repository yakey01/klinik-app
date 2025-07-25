<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class RiwayatValidasiTindakanResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    
    protected static ?string $navigationLabel = 'Riwayat Validasi';
    
    protected static ?string $navigationGroup = 'Validasi Transaksi';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Tindakan')
                    ->schema([
                        Forms\Components\TextInput::make('jenisTindakan.nama')
                            ->label('Jenis Tindakan')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('pasien.nama')
                            ->label('Nama Pasien')
                            ->disabled(),
                            
                        Forms\Components\DateTimePicker::make('tanggal_tindakan')
                            ->label('Tanggal Tindakan')
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('tarif')
                            ->label('Tarif')
                            ->prefix('Rp')
                            ->disabled(),
                            
                        Forms\Components\Select::make('status_validasi')
                            ->label('Status Validasi')
                            ->options([
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ])
                            ->disabled(),
                            
                        Forms\Components\TextInput::make('validatedBy.name')
                            ->label('Divalidasi Oleh')
                            ->disabled(),
                            
                        Forms\Components\DateTimePicker::make('validated_at')
                            ->label('Tanggal Validasi')
                            ->disabled(),
                            
                        Forms\Components\Textarea::make('komentar_validasi')
                            ->label('Komentar Validasi')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('pasien.nama')
                    ->label('Pasien')
                    ->searchable()
                    ->limit(25)
                    ->description(fn (Tindakan $record): string => $record->pasien->no_rekam_medis ?? ''),

                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_validasi')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('validatedBy.name')
                    ->label('Divalidasi Oleh')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validated_at')
                    ->label('Tgl Validasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Input Oleh')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),

                Tables\Filters\Filter::make('validated_at')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Validasi Dari'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Validasi Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('validated_at', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('validated_at', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('validated_by')
                    ->label('Validator')
                    ->options(function () {
                        return \App\Models\User::whereHas('roles', function ($query) {
                            $query->where('name', 'bendahara');
                        })->pluck('name', 'id');
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ Lihat Detail'),
                    
                    Action::make('revert')
                        ->label('🔄 Kembalikan ke Pending')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('🔄 Kembalikan ke Status Pending')
                        ->modalDescription('Apakah Anda yakin ingin mengembalikan tindakan ini ke status pending untuk validasi ulang?')
                        ->modalSubmitActionLabel('Kembalikan')
                        ->visible(fn (): bool => auth()->user()->hasRole(['admin', 'bendahara']))
                        ->action(function (Tindakan $record) {
                            try {
                                $record->update([
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
                    //     ->action(function (Tindakan $record) {
                    //         \Filament\Notifications\Notification::make()
                    //             ->title('🖨️ Print')
                    //             ->body('Print functionality coming soon')
                    //             ->info()
                    //             ->send();
                    //     })
                    //     ->visible(fn (Tindakan $record): bool => $record->status_validasi === 'approved'),
                ])
                ->label('⚙️ Aksi')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('📤 Export Terpilih')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('info')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            // Export functionality can be implemented here
                            \Filament\Notifications\Notification::make()
                                ->title('📤 Export')
                                ->body('Export functionality coming soon')
                                ->info()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('validated_at', 'desc')
            ->poll('60s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('status_validasi', ['approved', 'rejected'])
            ->whereNotNull('validated_by')
            ->with(['jenisTindakan', 'pasien', 'validatedBy', 'inputBy']);
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['admin', 'bendahara']);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Bendahara\Resources\RiwayatValidasiTindakanResource\Pages\ListRiwayatValidasiTindakan::route('/'),
            'view' => \App\Filament\Bendahara\Resources\RiwayatValidasiTindakanResource\Pages\ViewRiwayatValidasiTindakan::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status_validasi', ['approved', 'rejected'])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
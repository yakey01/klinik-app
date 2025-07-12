<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\PendapatanHarianResource\Pages;
use App\Filament\Petugas\Resources\PendapatanHarianResource\RelationManagers;
use App\Models\PendapatanHarian;
use App\Models\Pendapatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PendapatanHarianResource extends Resource
{
    protected static ?string $model = PendapatanHarian::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    protected static ?string $navigationLabel = 'Pendapatan Harian';
    
    protected static ?string $modelLabel = 'Pendapatan Harian';
    
    protected static ?string $navigationGroup = 'Input Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_input')
                            ->label('Tanggal Input')
                            ->default(now())
                            ->required()
                            ->columnSpan(1),
                            
                        Forms\Components\Select::make('shift')
                            ->label('Shift')
                            ->options([
                                'Pagi' => 'Pagi',
                                'Sore' => 'Sore',
                            ])
                            ->required()
                            ->columnSpan(1),
                    ]),
                
                Forms\Components\Select::make('pendapatan_id')
                    ->label('Nama Pendapatan')
                    ->relationship(
                        name: 'pendapatan',
                        titleAttribute: 'nama_pendapatan',
                        modifyQueryUsing: fn (Builder $query) =>
                            $query->where('is_aktif', true)
                    )
                    ->searchable()
                    ->required()
                    ->preload()
                    ->columnSpanFull()
                    ->helperText('Pilih jenis pendapatan dari data master yang tersedia'),
                
                Forms\Components\TextInput::make('nominal')
                    ->label('Nominal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->placeholder('0')
                    ->columnSpanFull(),
                
                Forms\Components\Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->placeholder('Keterangan tambahan (opsional)')
                    ->maxLength(255)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('💰 Pendapatan Harian Saya')
            ->description('Kelola pendapatan harian Anda dengan mudah dan efisien')
            ->headerActions([
                Tables\Actions\Action::make('summary')
                    ->label('📊 Ringkasan')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->button()
                    ->outlined()
                    ->modalHeading('📊 Ringkasan Pendapatan Harian')
                    ->modalContent(fn () => view('filament.widgets.pendapatan-summary'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\CreateAction::make()
                    ->label('➕ Tambah Pendapatan')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->button()
            ])
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_input')
                    ->label('📅 Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->weight('semibold')
                    ->tooltip('Tanggal input pendapatan'),
                Tables\Columns\TextColumn::make('shift')
                    ->label('⏰ Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'success',
                        'Sore' => 'warning',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Pagi' => 'heroicon-o-sun',
                        'Sore' => 'heroicon-o-moon',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Pagi' => '🌅 Pagi',
                        'Sore' => '🌅 Sore',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('pendapatan.nama_pendapatan')
                    ->label('💼 Jenis Pendapatan')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->weight('medium')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->pendapatan?->nama_pendapatan)
                    ->wrap(),
                Tables\Columns\TextColumn::make('nominal')
                    ->label('💰 Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->weight('bold')
                    ->size('lg')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('🎯 Total Pendapatan'),
                    ]),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('👤 Input Oleh')
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->color('gray')
                    ->weight('medium')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('📝 Deskripsi')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->deskripsi)
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('gray')
                    ->placeholder('Tidak ada deskripsi')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('🕒 Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shift')
                    ->label('⏰ Filter Shift')
                    ->options([
                        'Pagi' => '🌅 Shift Pagi',
                        'Sore' => '🌆 Shift Sore',
                    ])
                    ->placeholder('Semua Shift')
                    ->multiple(),
                Tables\Filters\Filter::make('tanggal_input')
                    ->label('📅 Rentang Tanggal')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('dari')
                                    ->label('Dari Tanggal')
                                    ->placeholder('Pilih tanggal mulai')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                                Forms\Components\DatePicker::make('sampai')
                                    ->label('Sampai Tanggal')
                                    ->placeholder('Pilih tanggal akhir')
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_input', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_input', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari'] ?? null) {
                            $indicators[] = '📅 Dari: ' . \Carbon\Carbon::parse($data['dari'])->format('d/m/Y');
                        }
                        if ($data['sampai'] ?? null) {
                            $indicators[] = '📅 Sampai: ' . \Carbon\Carbon::parse($data['sampai'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
                Tables\Filters\Filter::make('nominal')
                    ->label('💰 Rentang Nominal')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nominal_min')
                                    ->label('Nominal Minimum')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('nominal_max')
                                    ->label('Nominal Maximum')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('1,000,000'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['nominal_min'], fn (Builder $query, $amount): Builder => $query->where('nominal', '>=', $amount))
                            ->when($data['nominal_max'], fn (Builder $query, $amount): Builder => $query->where('nominal', '<=', $amount));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ Lihat')
                        ->color('info')
                        ->icon('heroicon-o-eye')
                        ->tooltip('Lihat detail pendapatan'),
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->color('warning')
                        ->icon('heroicon-o-pencil-square')
                        ->tooltip('Edit pendapatan')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('✅ Berhasil!')
                                ->body('Pendapatan harian berhasil diperbarui.')
                                ->duration(3000)
                        ),
                    Tables\Actions\Action::make('duplicate')
                        ->label('📋 Duplikat')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('success')
                        ->tooltip('Duplikat data pendapatan')
                        ->action(function ($record) {
                            $newRecord = $record->replicate();
                            $newRecord->tanggal_input = now()->toDateString();
                            $newRecord->user_id = auth()->id();
                            $newRecord->save();
                            
                            Notification::make()
                                ->success()
                                ->title('📋 Data Diduplikat!')
                                ->body('Pendapatan berhasil diduplikat dengan tanggal hari ini.')
                                ->duration(3000)
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('📋 Duplikat Pendapatan')
                        ->modalDescription('Apakah Anda yakin ingin menduplikat data pendapatan ini dengan tanggal hari ini?')
                        ->modalSubmitActionLabel('Ya, Duplikat')
                        ->modalCancelActionLabel('Batal'),
                    Tables\Actions\DeleteAction::make()
                        ->label('🗑️ Hapus')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->tooltip('Hapus pendapatan')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('🗑️ Terhapus!')
                                ->body('Pendapatan harian berhasil dihapus.')
                                ->duration(3000)
                        )
                        ->modalHeading('🗑️ Hapus Pendapatan')
                        ->modalDescription('Data yang dihapus tidak dapat dikembalikan. Apakah Anda yakin?')
                        ->modalSubmitActionLabel('Ya, Hapus')
                        ->modalCancelActionLabel('Batal'),
                ])
                ->label('⚙️ Aksi')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
                ->tooltip('Menu aksi')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('📊 Export Terpilih')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            $total = $records->sum('nominal');
                            Notification::make()
                                ->info()
                                ->title('📊 Export Berhasil!')
                                ->body("Mengexport {$records->count()} data dengan total Rp " . number_format($total, 0, ',', '.'))
                                ->duration(4000)
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('🗑️ Hapus Terpilih')
                        ->modalHeading('🗑️ Hapus Data Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus semua data yang dipilih? Tindakan ini tidak dapat dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Hapus Semua')
                        ->modalCancelActionLabel('Batal')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('🗑️ Data Terhapus!')
                                ->body('Semua data terpilih berhasil dihapus.')
                                ->duration(3000)
                        ),
                ])
                ->label('🔧 Aksi Massal')
                ->color('gray')
                ->button()
            ])
            ->defaultSort('tanggal_input', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->emptyStateHeading('📝 Belum Ada Data Pendapatan')
            ->emptyStateDescription('Mulai tambahkan pendapatan harian Anda dengan klik tombol "Tambah Pendapatan" di atas.')
            ->emptyStateIcon('heroicon-o-currency-dollar')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('➕ Tambah Pendapatan Pertama')
                    ->color('success')
                    ->button()
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', auth()->id()));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendapatanHarians::route('/'),
            'create' => Pages\CreatePendapatanHarian::route('/create'),
            'edit' => Pages\EditPendapatanHarian::route('/{record}/edit'),
        ];
    }
}

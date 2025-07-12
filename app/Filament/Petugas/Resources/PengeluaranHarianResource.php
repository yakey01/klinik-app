<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\PengeluaranHarianResource\Pages;
use App\Models\PengeluaranHarian;
use App\Models\Pengeluaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PengeluaranHarianResource extends Resource
{
    protected static ?string $model = PengeluaranHarian::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    
    protected static ?string $navigationLabel = 'Pengeluaran Harian';
    
    protected static ?string $modelLabel = 'Pengeluaran Harian';
    
    protected static ?string $navigationGroup = 'Input Data';

    protected static ?int $navigationSort = 2;

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
                
                Forms\Components\Select::make('pengeluaran_id')
                    ->label('Nama Pengeluaran')
                    ->relationship(
                        name: 'pengeluaran',
                        titleAttribute: 'nama_pengeluaran',
                        modifyQueryUsing: fn (Builder $query) =>
                            $query->whereNotNull('nama_pengeluaran')
                                  ->where('nama_pengeluaran', '!=', '')
                    )
                    ->searchable()
                    ->required()
                    ->preload()
                    ->columnSpanFull()
                    ->helperText('Pilih jenis pengeluaran dari data master yang tersedia'),
                
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
            ->heading('💸 Pengeluaran Harian Saya')
            ->description('Kelola pengeluaran harian Anda dengan mudah dan efisien')
            ->headerActions([
                Tables\Actions\Action::make('summary')
                    ->label('📊 Ringkasan')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->button()
                    ->outlined()
                    ->modalHeading('📊 Ringkasan Pengeluaran Harian')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\CreateAction::make()
                    ->label('➕ Tambah Pengeluaran')
                    ->icon('heroicon-o-plus-circle')
                    ->color('danger')
                    ->button()
            ])
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_input')
                    ->label('📅 Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->weight('semibold'),
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
                        'Sore' => '🌆 Sore',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('pengeluaran.nama_pengeluaran')
                    ->label('💼 Jenis Pengeluaran')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->weight('medium')
                    ->limit(30)
                    ->wrap(),
                Tables\Columns\TextColumn::make('nominal')
                    ->label('💸 Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->icon('heroicon-o-banknotes')
                    ->color('danger')
                    ->weight('bold')
                    ->size('lg')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('🎯 Total Pengeluaran'),
                    ]),
                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('📋 Status Validasi')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'disetujui' => 'heroicon-o-check-circle',
                        'ditolak' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ Menunggu',
                        'disetujui' => '✅ Disetujui',
                        'ditolak' => '❌ Ditolak',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('📝 Deskripsi')
                    ->limit(40)
                    ->placeholder('Tidak ada deskripsi')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('🕒 Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
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
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('📋 Status Validasi')
                    ->options([
                        'pending' => '⏳ Menunggu Validasi',
                        'disetujui' => '✅ Disetujui',
                        'ditolak' => '❌ Ditolak',
                    ])
                    ->placeholder('Semua Status'),
                Tables\Filters\Filter::make('tanggal_input')
                    ->label('📅 Rentang Tanggal')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('dari')
                                    ->label('Dari Tanggal'),
                                Forms\Components\DatePicker::make('sampai')
                                    ->label('Sampai Tanggal'),
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
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ Lihat')
                        ->color('info')
                        ->icon('heroicon-o-eye'),
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit')
                        ->color('warning')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn ($record): bool => $record->status_validasi === 'pending')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('✅ Berhasil!')
                                ->body('Pengeluaran harian berhasil diperbarui.')
                        ),
                    Tables\Actions\DeleteAction::make()
                        ->label('🗑️ Hapus')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->visible(fn ($record): bool => $record->status_validasi === 'pending')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('🗑️ Terhapus!')
                                ->body('Pengeluaran harian berhasil dihapus.')
                        ),
                ])
                ->label('⚙️ Aksi')
                ->icon('heroicon-o-ellipsis-vertical')
                ->button()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('🗑️ Hapus Terpilih')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('🗑️ Data Terhapus!')
                                ->body('Semua data terpilih berhasil dihapus.')
                        ),
                ])
            ])
            ->defaultSort('tanggal_input', 'desc')
            ->striped()
            ->poll('30s')
            ->emptyStateHeading('📝 Belum Ada Data Pengeluaran')
            ->emptyStateDescription('Mulai tambahkan pengeluaran harian Anda.')
            ->emptyStateIcon('heroicon-o-arrow-trending-down')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', auth()->id()));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengeluaranHarians::route('/'),
            'create' => Pages\CreatePengeluaranHarian::route('/create'),
            'edit' => Pages\EditPengeluaranHarian::route('/{record}/edit'),
        ];
    }
}
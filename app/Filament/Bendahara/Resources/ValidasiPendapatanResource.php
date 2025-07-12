<?php

namespace App\Filament\Bendahara\Resources;

use App\Models\Pendapatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ValidasiPendapatanResource extends Resource
{
    protected static ?string $model = Pendapatan::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = '💵 Validasi Transaksi';

    protected static ?string $navigationLabel = '🔹 Validasi Pendapatan';

    protected static ?string $modelLabel = 'Pendapatan';

    protected static ?string $pluralModelLabel = 'Validasi Pendapatan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Pendapatan')
                    ->schema([
                        Forms\Components\Select::make('input_by')
                            ->label('Petugas Input')
                            ->relationship('inputBy', 'name')
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->disabled(),

                        Forms\Components\TextInput::make('sumber_pendapatan')
                            ->label('Sumber Pendapatan')
                            ->disabled(),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori')
                            ->options([
                                'konsultasi' => 'Konsultasi',
                                'tindakan' => 'Tindakan',
                                'obat' => 'Obat',
                                'lain_lain' => 'Lain-lain',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal (Rp)')
                            ->prefix('Rp')
                            ->numeric()
                            ->required(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status_validasi')
                            ->label('Status Validasi')
                            ->options([
                                'pending' => 'Menunggu Validasi',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('catatan_validasi')
                            ->label('Catatan Validasi')
                            ->placeholder('Tambahkan catatan validasi...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sumber_pendapatan')
                    ->label('Sumber')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'konsultasi' => 'Konsultasi',
                        'tindakan' => 'Tindakan', 
                        'obat' => 'Obat',
                        'lain_lain' => 'Lain-lain',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('validasiBy.name')
                    ->label('Divalidasi Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal_input')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),

                Tables\Filters\SelectFilter::make('kategori')
                    ->options([
                        'konsultasi' => 'Konsultasi',
                        'tindakan' => 'Tindakan',
                        'obat' => 'Obat',
                        'lain_lain' => 'Lain-lain',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Pendapatan $record): bool => $record->status_validasi === 'pending'),
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->action(function (Pendapatan $record) {
                        $record->update([
                            'status_validasi' => 'disetujui',
                            'validasi_by' => Auth::id(),
                            'validasi_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Pendapatan disetujui')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Pendapatan $record): bool => $record->status_validasi === 'pending'),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->action(function (Pendapatan $record) {
                        $record->update([
                            'status_validasi' => 'ditolak',
                            'validasi_by' => Auth::id(),
                            'validasi_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Pendapatan ditolak')
                            ->warning()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Pendapatan $record): bool => $record->status_validasi === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_all')
                        ->label('Validasi Semua')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status_validasi === 'pending') {
                                    $record->update([
                                        'status_validasi' => 'disetujui',
                                        'validasi_by' => Auth::id(),
                                        'validasi_at' => now(),
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title('Semua pendapatan berhasil divalidasi')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['inputBy', 'validasiBy']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Bendahara\Resources\ValidasiPendapatanResource\Pages\ListValidasiPendapatan::route('/'),
            'view' => \App\Filament\Bendahara\Resources\ValidasiPendapatanResource\Pages\ViewValidasiPendapatan::route('/{record}'),
            'edit' => \App\Filament\Bendahara\Resources\ValidasiPendapatanResource\Pages\EditValidasiPendapatan::route('/{record}/edit'),
        ];
    }
}
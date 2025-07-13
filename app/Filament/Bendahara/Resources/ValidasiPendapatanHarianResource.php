<?php

namespace App\Filament\Bendahara\Resources;

use App\Enums\TelegramNotificationType;
use App\Models\PendapatanHarian;
use App\Services\TelegramService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ValidasiPendapatanHarianResource extends Resource
{
    protected static ?string $model = PendapatanHarian::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = '💵 Validasi Transaksi';

    protected static ?string $navigationLabel = '🔹 Validasi Pendapatan';

    protected static ?string $modelLabel = 'Pendapatan Harian';

    protected static ?string $pluralModelLabel = 'Validasi Pendapatan Harian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Pendapatan Harian')
                    ->schema([
                        Forms\Components\TextInput::make('petugas_name')
                            ->label('Petugas Input')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->inputBy?->name ?? 'Tidak diketahui'),

                        Forms\Components\DatePicker::make('tanggal_input')
                            ->label('Tanggal Input')
                            ->disabled(),

                        Forms\Components\Select::make('shift')
                            ->label('Shift')
                            ->options([
                                'Pagi' => 'Pagi',
                                'Sore' => 'Sore',
                            ])
                            ->disabled(),

                        Forms\Components\TextInput::make('jenis_pendapatan')
                            ->label('Jenis Pendapatan')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->pendapatan?->nama_pendapatan ?? 'Tidak diketahui'),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal (Rp)')
                            ->prefix('Rp')
                            ->numeric()
                            ->required(),

                        Forms\Components\Textarea::make('deskripsi')
                            ->label('Deskripsi')
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
                Tables\Columns\TextColumn::make('tanggal_input')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Petugas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift')
                    ->label('Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'success',
                        'Sore' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('pendapatan.nama_pendapatan')
                    ->label('Jenis Pendapatan')
                    ->searchable()
                    ->limit(30),

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
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_input', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_input', '<=', $date),
                            );
                    }),

                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),

                Tables\Filters\SelectFilter::make('shift')
                    ->options([
                        'Pagi' => 'Pagi',
                        'Sore' => 'Sore',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (PendapatanHarian $record): bool => $record->status_validasi === 'pending'),
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->action(function (PendapatanHarian $record) {
                        $record->update([
                            'status_validasi' => 'disetujui',
                            'validasi_by' => Auth::id(),
                            'validasi_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Pendapatan harian disetujui')
                            ->success()
                            ->send();

                        // Send Telegram notification to manajer
                        try {
                            $telegramService = app(TelegramService::class);
                            $message = $telegramService->formatNotificationMessage(
                                TelegramNotificationType::VALIDASI_DISETUJUI->value,
                                [
                                    'validator_name' => Auth::user()->name,
                                    'type' => 'Pendapatan',
                                    'amount' => $record->nominal,
                                    'description' => $record->deskripsi ?? 'Pendapatan dari '.($record->pendapatan->nama_pendapatan ?? '-'),
                                    'date' => $record->tanggal_input->format('d/m/Y'),
                                    'shift' => $record->shift,
                                    'petugas' => $record->inputBy->name ?? 'Unknown',
                                ]
                            );

                            $telegramService->sendNotificationToRole('manajer', TelegramNotificationType::VALIDASI_DISETUJUI->value, $message);
                        } catch (\Exception $e) {
                            \Log::error('Failed to send telegram notification for approval: '.$e->getMessage());
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (PendapatanHarian $record): bool => $record->status_validasi === 'pending'),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->action(function (PendapatanHarian $record) {
                        $record->update([
                            'status_validasi' => 'ditolak',
                            'validasi_by' => Auth::id(),
                            'validasi_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Pendapatan harian ditolak')
                            ->warning()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (PendapatanHarian $record): bool => $record->status_validasi === 'pending'),
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
                                ->title('Semua pendapatan harian berhasil divalidasi')
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
            ->with(['inputBy', 'validasiBy', 'pendapatan']);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource\Pages\ListValidasiPendapatanHarians::route('/'),
            'view' => \App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource\Pages\ViewValidasiPendapatanHarian::route('/{record}'),
            'edit' => \App\Filament\Bendahara\Resources\ValidasiPendapatanHarianResource\Pages\EditValidasiPendapatanHarian::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Bendahara\Resources;

use App\Enums\TelegramNotificationType;
use App\Filament\Bendahara\Resources\ValidasiTindakanResource\Pages;
use App\Models\Tindakan;
use App\Models\Jaspel;
use App\Models\User;
use App\Services\TelegramService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ValidasiTindakanResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = '💵 Validasi Transaksi';

    protected static ?string $navigationLabel = '🔹 Validasi Tindakan';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Tindakan';

    protected static ?string $pluralModelLabel = 'Validasi Tindakan';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Tindakan')
                    ->schema([
                        Forms\Components\TextInput::make('petugas_name')
                            ->label('Petugas Input')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->inputBy?->name ?? 'Tidak diketahui'),

                        Forms\Components\DateTimePicker::make('tanggal_tindakan')
                            ->label('Tanggal & Waktu Tindakan')
                            ->disabled()
                            ->native(false),

                        Forms\Components\TextInput::make('pasien_name')
                            ->label('Nama Pasien')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->pasien?->nama ?? 'Tidak diketahui'),

                        Forms\Components\TextInput::make('jenis_tindakan_name')
                            ->label('Jenis Tindakan')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->jenisTindakan?->nama ?? 'Tidak diketahui'),

                        Forms\Components\TextInput::make('dokter_name')
                            ->label('Dokter Pelaksana')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->dokter?->nama ?? '-'),

                        Forms\Components\TextInput::make('paramedis_name')
                            ->label('Paramedis Pelaksana')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->paramedis?->nama_lengkap ?? '-'),

                        Forms\Components\TextInput::make('non_paramedis_name')
                            ->label('Non-Paramedis Pelaksana')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->nonParamedis?->nama_lengkap ?? '-'),

                        Forms\Components\TextInput::make('shift_name')
                            ->label('Shift')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->shift?->nama ?? 'Tidak diketahui'),

                        Forms\Components\TextInput::make('tarif')
                            ->label('Tarif')
                            ->disabled()
                            ->prefix('Rp')
                            ->numeric()
                            ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                        Forms\Components\TextInput::make('jasa_dokter')
                            ->label('Jasa Dokter')
                            ->disabled()
                            ->prefix('Rp')
                            ->numeric()
                            ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                        Forms\Components\TextInput::make('jasa_paramedis')
                            ->label('Jasa Paramedis')
                            ->disabled()
                            ->prefix('Rp')
                            ->numeric()
                            ->formatStateUsing(fn ($state) => number_format($state, 0, ',', '.')),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan')
                            ->disabled()
                            ->rows(2),

                        Forms\Components\Select::make('status')
                            ->label('Status Tindakan')
                            ->options([
                                'pending' => 'Pending',
                                'selesai' => 'Selesai',
                                'batal' => 'Batal',
                            ])
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Validasi Bendahara')
                    ->schema([
                        Forms\Components\Select::make('status_validasi')
                            ->label('Status Validasi')
                            ->options([
                                'pending' => 'Menunggu',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('komentar_validasi')
                            ->label('Komentar Validasi')
                            ->placeholder('Berikan komentar atau catatan validasi...')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('validated_by')
                            ->default(fn () => Auth::id()),

                        Forms\Components\Hidden::make('validated_at')
                            ->default(fn () => now()),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Tindakan::with(['pasien', 'jenisTindakan', 'dokter', 'paramedis', 'nonParamedis', 'shift', 'inputBy', 'validatedBy'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('Tanggal')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('pasien.nama')
                    ->label('Pasien')
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('dokter.nama')
                    ->label('Dokter Pelaksana')
                    ->searchable()
                    ->limit(20)
                    ->default('-'),

                Tables\Columns\TextColumn::make('paramedis.nama_lengkap')
                    ->label('Paramedis Pelaksana')
                    ->searchable()
                    ->limit(20)
                    ->default('-'),

                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('shift.nama')
                    ->label('Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'info',
                        'Sore' => 'warning',
                        'Malam' => 'primary',
                        default => 'gray'
                    }),

                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status Validasi')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => ucfirst($state)
                    }),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Input Oleh')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validatedBy.name')
                    ->label('Validasi Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Tindakan')
                    ->options([
                        'pending' => 'Pending',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ]),

                Tables\Filters\Filter::make('tanggal_tindakan')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_tindakan', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('hari_ini')
                    ->label('Hari Ini')
                    ->query(fn (Builder $query): Builder => $query->whereDate('tanggal_tindakan', today())),

                Tables\Filters\Filter::make('minggu_ini')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('tanggal_tindakan', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->action(function (Tindakan $record, array $data) {
                        $record->update([
                            'status_validasi' => 'disetujui',
                            'validated_by' => Auth::id(),
                            'validated_at' => now(),
                            'komentar_validasi' => $data['komentar'] ?? null,
                        ]);

                        // Create jaspel records for each performer
                        self::createJaspelRecords($record);

                        Notification::make()
                            ->title('Tindakan berhasil disetujui dan jaspel dibuat')
                            ->success()
                            ->send();

                        // Send Telegram notification to manajer
                        try {
                            $telegramService = app(TelegramService::class);
                            $message = $telegramService->formatNotificationMessage(
                                TelegramNotificationType::VALIDASI_DISETUJUI->value,
                                [
                                    'validator_name' => Auth::user()->name,
                                    'type' => 'Tindakan',
                                    'amount' => $record->tarif,
                                    'description' => 'Tindakan '.($record->jenisTindakan->nama ?? 'Unknown').' untuk pasien '.($record->pasien->nama ?? 'Unknown'),
                                    'date' => $record->tanggal_tindakan->format('d/m/Y'),
                                    'shift' => $record->shift->nama ?? 'Unknown',
                                    'petugas' => $record->inputBy->name ?? 'Unknown',
                                    'dokter' => $record->dokter->nama ?? '-',
                                    'paramedis' => $record->paramedis->nama_lengkap ?? '-',
                                ]
                            );

                            $telegramService->sendNotificationToRole('manajer', TelegramNotificationType::VALIDASI_DISETUJUI->value, $message);
                        } catch (\Exception $e) {
                            \Log::error('Failed to send telegram notification for tindakan approval: '.$e->getMessage());
                        }
                    })
                    ->form([
                        Forms\Components\Textarea::make('komentar')
                            ->label('Komentar')
                            ->placeholder('Tambahkan komentar (opsional)'),
                    ])
                    ->visible(fn (Tindakan $record): bool => $record->status_validasi === 'pending'),

                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->action(function (Tindakan $record, array $data) {
                        $record->update([
                            'status_validasi' => 'ditolak',
                            'validated_by' => Auth::id(),
                            'validated_at' => now(),
                            'komentar_validasi' => $data['komentar'] ?? 'Tindakan ditolak',
                        ]);

                        Notification::make()
                            ->title('Tindakan ditolak')
                            ->warning()
                            ->send();
                    })
                    ->form([
                        Forms\Components\Textarea::make('komentar')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->placeholder('Jelaskan alasan penolakan...'),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (Tindakan $record): bool => $record->status_validasi === 'pending'),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_all')
                        ->label('Setujui Semua')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $approvedCount = 0;
                            $records->each(function ($record) use (&$approvedCount) {
                                if ($record->status_validasi === 'pending') {
                                    $record->update([
                                        'status_validasi' => 'disetujui',
                                        'validated_by' => Auth::id(),
                                        'validated_at' => now(),
                                    ]);
                                    
                                    // Create jaspel records for this tindakan
                                    self::createJaspelRecords($record);
                                    $approvedCount++;
                                }
                            });

                            Notification::make()
                                ->title("$approvedCount tindakan berhasil disetujui dan jaspel dibuat")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_tindakan', 'desc');
    }

    protected static function createJaspelRecords(Tindakan $tindakan): void
    {
        // Load relationships to ensure we have the latest data
        $tindakan->load(['dokter', 'paramedis.user', 'nonParamedis.user']);

        // Create jaspel for dokter if assigned and has fee
        if ($tindakan->dokter_id && $tindakan->jasa_dokter > 0) {
            // Find the user account for the dokter by email
            $dokterUser = User::where('email', $tindakan->dokter->email)->first();
            if ($dokterUser) {
                Jaspel::create([
                    'tindakan_id' => $tindakan->id,
                    'user_id' => $dokterUser->id,
                    'jenis_jaspel' => 'dokter',
                    'nominal' => $tindakan->jasa_dokter,
                    'tanggal' => $tindakan->tanggal_tindakan->format('Y-m-d'),
                    'shift_id' => $tindakan->shift_id,
                    'input_by' => Auth::id(),
                    'status_validasi' => 'disetujui', // Auto-approved when tindakan is approved
                    'validasi_by' => Auth::id(),
                    'validasi_at' => now(),
                    'catatan_validasi' => 'Auto-generated from approved tindakan for ' . $tindakan->dokter->nama,
                ]);
            }
        }

        // Create jaspel for paramedis if assigned and has fee
        if ($tindakan->paramedis_id && $tindakan->jasa_paramedis > 0) {
            $paramedisUser = null;
            
            // Priority 1: Use the specific user linked to this paramedis
            if ($tindakan->paramedis->user_id) {
                $paramedisUser = $tindakan->paramedis->user;
            }
            
            if ($paramedisUser) {
                Jaspel::create([
                    'tindakan_id' => $tindakan->id,
                    'user_id' => $paramedisUser->id,
                    'jenis_jaspel' => 'paramedis',
                    'nominal' => $tindakan->jasa_paramedis,
                    'tanggal' => $tindakan->tanggal_tindakan->format('Y-m-d'),
                    'shift_id' => $tindakan->shift_id,
                    'input_by' => Auth::id(),
                    'status_validasi' => 'disetujui', // Auto-approved when tindakan is approved
                    'validasi_by' => Auth::id(),
                    'validasi_at' => now(),
                    'catatan_validasi' => 'Auto-generated from approved tindakan for ' . $tindakan->paramedis->nama_lengkap,
                ]);
            }
        }

        // Create jaspel for non-paramedis if assigned and has fee
        if ($tindakan->non_paramedis_id && $tindakan->jasa_non_paramedis > 0) {
            $nonParamedisUser = null;
            
            // Priority 1: Use the specific user linked to this non-paramedis
            if ($tindakan->nonParamedis->user_id) {
                $nonParamedisUser = $tindakan->nonParamedis->user;
            }
            
            if ($nonParamedisUser) {
                Jaspel::create([
                    'tindakan_id' => $tindakan->id,
                    'user_id' => $nonParamedisUser->id,
                    'jenis_jaspel' => 'non_paramedis',
                    'nominal' => $tindakan->jasa_non_paramedis,
                    'tanggal' => $tindakan->tanggal_tindakan->format('Y-m-d'),
                    'shift_id' => $tindakan->shift_id,
                    'input_by' => Auth::id(),
                    'status_validasi' => 'disetujui', // Auto-approved when tindakan is approved
                    'validasi_by' => Auth::id(),
                    'validasi_at' => now(),
                    'catatan_validasi' => 'Auto-generated from approved tindakan for ' . $tindakan->nonParamedis->nama_lengkap,
                ]);
            }
        }
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListValidasiTindakans::route('/'),
            'create' => Pages\CreateValidasiTindakan::route('/create'),
            'view' => Pages\ViewValidasiTindakan::route('/{record}'),
            'edit' => Pages\EditValidasiTindakan::route('/{record}/edit'),
        ];
    }
}

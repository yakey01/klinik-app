<?php

namespace App\Filament\Dokter\Resources;

use App\Filament\Dokter\Resources\JaspelDokterResource\Pages;
use App\Models\Jaspel;
use App\Models\Dokter;
use App\Models\JenisTindakan;
use App\Models\Tindakan;
use App\Models\Pasien;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class JaspelDokterResource extends Resource
{
    protected static ?string $model = Jaspel::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Jaspel Saya';
    protected static ?string $modelLabel = 'Jaspel';
    protected static ?string $pluralModelLabel = 'Data Jaspel';
    protected static ?string $navigationGroup = 'Presensi & Jaspel';
    protected static ?int $navigationSort = 2;

    // Security: Only accessible by dokter role
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'dokter';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Jaspel')
                    ->schema([
                        Forms\Components\Select::make('tindakan_id')
                            ->label('Tindakan')
                            ->relationship('tindakan', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                $record->jenisTindakan->nama . ' - ' . $record->pasien->nama
                            )
                            ->searchable()
                            ->preload()
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('jenis_jaspel')
                            ->label('Jenis Jaspel')
                            ->disabled(),

                        Forms\Components\TextInput::make('nominal')
                            ->label('Nominal Jaspel')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),

                        Forms\Components\Select::make('status_validasi')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                            ])
                            ->disabled(),

                        Forms\Components\Textarea::make('catatan_validasi')
                            ->label('Catatan Validasi')
                            ->rows(3)
                            ->disabled()
                            ->placeholder('Tidak ada catatan'),
                    ]),

                Forms\Components\Section::make('Detail Tindakan')
                    ->schema([
                        Forms\Components\TextInput::make('tindakan.jenisTindakan.nama')
                            ->label('Jenis Tindakan')
                            ->disabled(),

                        Forms\Components\TextInput::make('tindakan.pasien.nama')
                            ->label('Nama Pasien')
                            ->disabled(),

                        Forms\Components\TextInput::make('tindakan.pasien.no_rm')
                            ->label('No. Rekam Medis')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('tindakan.tanggal_tindakan')
                            ->label('Tanggal Tindakan')
                            ->disabled(),

                        Forms\Components\TextInput::make('tindakan.tarif')
                            ->label('Tarif Tindakan')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tindakan.jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('tindakan.pasien.nama')
                    ->label('Pasien')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('tindakan.pasien.no_rm')
                    ->label('No. RM')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('jenis_jaspel')
                    ->label('Jenis Jaspel')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'jasa_dokter' => 'Jasa Dokter',
                        'jasa_paramedis' => 'Jasa Paramedis',
                        'jasa_non_paramedis' => 'Jasa Non-Paramedis',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    }),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui', 
                        'danger' => 'ditolak',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ Pending',
                        'disetujui' => '✅ Disetujui',
                        'ditolak' => '❌ Ditolak',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Shift')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('validasi_at')
                    ->label('Divalidasi')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Belum divalidasi'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereMonth('tanggal', Carbon::now()->month)
                              ->whereYear('tanggal', Carbon::now()->year)
                    )
                    ->default(),

                Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('tanggal', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek()
                        ])
                    ),

                SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),

                SelectFilter::make('jenis_jaspel')
                    ->label('Jenis Jaspel')
                    ->options([
                        'jasa_dokter' => 'Jasa Dokter',
                        'jasa_paramedis' => 'Jasa Paramedis',
                        'jasa_non_paramedis' => 'Jasa Non-Paramedis',
                    ]),

                Filter::make('approved_only')
                    ->label('Hanya Disetujui')
                    ->query(fn (Builder $query): Builder => $query->where('status_validasi', 'disetujui')),

                Filter::make('high_value')
                    ->label('Nominal Tinggi (>500k)')
                    ->query(fn (Builder $query): Builder => $query->where('nominal', '>', 500000)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('tanggal', 'desc')
            ->poll('60s') // Auto refresh every minute
            ->recordUrl(null); // Disable row click to view
    }

    public static function getEloquentQuery(): Builder
    {
        $currentDokter = static::getCurrentDokter();
        
        // Security: Only show current doctor's jaspel records
        return parent::getEloquentQuery()
            ->when($currentDokter, fn($query) => $query->where('user_id', $currentDokter->user_id))
            ->with(['tindakan.jenisTindakan', 'tindakan.pasien', 'shift', 'validasiBy'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function getCurrentDokter(): ?Dokter
    {
        if (!auth()->check()) {
            return null;
        }

        return Dokter::where('user_id', auth()->id())->first();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJaspelDokters::route('/'),
            'view' => Pages\ViewJaspelDokter::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Jaspel created automatically from tindakan
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // Cannot edit jaspel
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // Cannot delete jaspel
    }

    // Helper methods for stats and summaries
    public static function getTotalJaspelBulanIni(): float
    {
        $currentDokter = static::getCurrentDokter();
        if (!$currentDokter) return 0;

        return Jaspel::where('user_id', $currentDokter->user_id)
            ->where('status_validasi', 'disetujui')
            ->whereMonth('tanggal', Carbon::now()->month)
            ->whereYear('tanggal', Carbon::now()->year)
            ->sum('nominal');
    }

    public static function getJumlahJaspelPending(): int
    {
        $currentDokter = static::getCurrentDokter();
        if (!$currentDokter) return 0;

        return Jaspel::where('user_id', $currentDokter->user_id)
            ->where('status_validasi', 'pending')
            ->count();
    }

    public static function getJaspelHariIni(): float
    {
        $currentDokter = static::getCurrentDokter();
        if (!$currentDokter) return 0;

        return Jaspel::where('user_id', $currentDokter->user_id)
            ->where('status_validasi', 'disetujui')
            ->whereDate('tanggal', Carbon::today())
            ->sum('nominal');
    }
}
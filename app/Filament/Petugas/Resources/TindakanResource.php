<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\TindakanResource\Pages;
use App\Models\JenisTindakan;
use App\Models\Pasien;
use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TindakanResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Input Data';

    protected static ?string $modelLabel = 'Tindakan';

    protected static ?string $pluralModelLabel = 'Input Tindakan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Input Tindakan Medis')
                    ->schema([
                        Forms\Components\Select::make('jenis_tindakan_id')
                            ->label('Jenis Tindakan')
                            ->required()
                            ->options(JenisTindakan::where('is_active', true)->orderBy('nama')->pluck('nama', 'id'))
                            ->searchable()
                            ->placeholder('Pilih jenis tindakan')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $jenisTindakan = JenisTindakan::find($state);
                                    if ($jenisTindakan) {
                                        $tarif = $jenisTindakan->tarif;

                                        // Get JASPEL percentage from config or use default
                                        $persentaseJaspel = config('app.default_jaspel_percentage', 40);

                                        // Calculate JASPEL Petugas (same as admin calculation)
                                        $jasaPetugas = $tarif * ($persentaseJaspel / 100);

                                        $set('tarif', $tarif);
                                        $set('calculated_jaspel', $jasaPetugas); // Store calculated JASPEL

                                        // Reset all jasa fields initially
                                        $set('jasa_dokter', 0);
                                        $set('jasa_paramedis', 0);
                                        $set('jasa_non_paramedis', $jenisTindakan->jasa_non_paramedis);

                                        // Set hidden field to show the percentage used
                                        $set('persentase_jaspel_info', $persentaseJaspel);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('pasien_id')
                            ->label('Pasien')
                            ->required()
                            ->options(Pasien::orderBy('nama')->get()->mapWithKeys(fn (Pasien $pasien) => [$pasien->id => "{$pasien->no_rekam_medis} - {$pasien->nama}"]))
                            ->searchable()
                            ->placeholder('Pilih pasien'),

                        Forms\Components\DateTimePicker::make('tanggal_tindakan')
                            ->label('Tanggal Tindakan')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Forms\Components\Select::make('shift_id')
                            ->label('Shift')
                            ->options(\App\Models\Shift::where('is_active', true)
                                ->whereIn('name', ['Pagi', 'Sore'])
                                ->orderBy('start_time')
                                ->pluck('name', 'id'))
                            ->required()
                            ->native(false)
                            ->placeholder('Pilih shift (Pagi/Sore)'),

                        Forms\Components\Select::make('dokter_id')
                            ->label('Dokter Pelaksana')
                            ->options(\App\Models\Dokter::where('aktif', true)
                                ->orderBy('nama_lengkap')
                                ->get()
                                ->mapWithKeys(fn ($dokter) => [
                                    $dokter->id => $dokter->nama_lengkap.
                                    ($dokter->spesialisasi ? ' ('.$dokter->spesialisasi.')' : ''),
                                ]))
                            ->searchable()
                            ->placeholder('Pilih dokter (opsional)')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $calculatedJaspel = $get('calculated_jaspel') ?? 0;

                                if ($state) {
                                    // Doctor selected, give JASPEL to doctor
                                    $set('jasa_dokter', $calculatedJaspel);
                                    $set('jasa_paramedis', 0); // Remove from paramedic
                                } else {
                                    // No doctor selected, remove JASPEL from doctor
                                    $set('jasa_dokter', 0);

                                    // Check if paramedic is selected to give them JASPEL
                                    if ($get('paramedis_id')) {
                                        $set('jasa_paramedis', $calculatedJaspel);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('paramedis_id')
                            ->label('Paramedis Pelaksana')
                            ->options(\App\Models\Pegawai::where('jenis_pegawai', 'Paramedis')
                                ->where('aktif', true)
                                ->orderBy('nama_lengkap')
                                ->get()
                                ->mapWithKeys(fn ($pegawai) => [
                                    $pegawai->id => $pegawai->nama_lengkap.
                                    ($pegawai->jabatan ? ' ('.$pegawai->jabatan.')' : ''),
                                ]))
                            ->searchable()
                            ->placeholder('Pilih paramedis (opsional)')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $calculatedJaspel = $get('calculated_jaspel') ?? 0;

                                if ($state && ! $get('dokter_id')) {
                                    // Paramedic selected and no doctor selected, give JASPEL to paramedic
                                    $set('jasa_paramedis', $calculatedJaspel);
                                } elseif (! $state) {
                                    // No paramedic selected, remove JASPEL from paramedic
                                    $set('jasa_paramedis', 0);
                                } elseif ($get('dokter_id')) {
                                    // Doctor has priority, remove JASPEL from paramedic
                                    $set('jasa_paramedis', 0);
                                }
                            }),

                        Forms\Components\Select::make('non_paramedis_id')
                            ->label('Non-Paramedis Pelaksana')
                            ->options(\App\Models\Pegawai::where('jenis_pegawai', 'Non-Paramedis')
                                ->where('aktif', true)
                                ->orderBy('nama_lengkap')
                                ->get()
                                ->mapWithKeys(fn ($pegawai) => [
                                    $pegawai->id => $pegawai->nama_lengkap.
                                    ($pegawai->jabatan ? ' ('.$pegawai->jabatan.')' : ''),
                                ]))
                            ->searchable()
                            ->placeholder('Pilih non-paramedis (opsional)'),

                        Forms\Components\TextInput::make('tarif')
                            ->label('Tarif (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('100000')
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Hidden::make('persentase_jaspel_info')
                            ->default(config('app.default_jaspel_percentage', 40)),

                        Forms\Components\Hidden::make('calculated_jaspel')
                            ->default(0),

                        Forms\Components\TextInput::make('jasa_dokter')
                            ->label('Jasa Dokter (Rp)')
                            ->helperText('JASPEL diberikan kepada dokter pelaksana (jika dipilih)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('jasa_paramedis')
                            ->label('Jasa Paramedis (Rp)')
                            ->helperText('JASPEL diberikan kepada paramedis pelaksana (jika tidak ada dokter)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('jasa_non_paramedis')
                            ->label('Jasa Non-Paramedis (Rp)')
                            ->helperText('Jasa untuk non-paramedis yang terlibat')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan')
                            ->maxLength(500)
                            ->placeholder('Catatan tindakan (opsional)')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Status Tindakan')
                            ->options([
                                'pending' => 'Pending',
                                'selesai' => 'Selesai',
                                'batal' => 'Batal',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Hidden::make('input_by')
                            ->default(fn () => Auth::id()),

                        Forms\Components\Hidden::make('status_validasi')
                            ->default('pending'),
                    ])
                    ->columns(3),
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
                    ->limit(30)
                    ->description(fn (Tindakan $record): string => $record->pasien->no_rekam_medis ?? ''),

                Tables\Columns\TextColumn::make('dokter.nama_lengkap')
                    ->label('Dokter')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('paramedis.nama_lengkap')
                    ->label('Paramedis')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Shift')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pagi' => 'info',
                        'Sore' => 'warning',
                        'Malam' => 'primary',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'selesai' => 'success',
                        'batal' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ]),

                Tables\Filters\SelectFilter::make('jenis_tindakan_id')
                    ->label('Jenis Tindakan')
                    ->options(JenisTindakan::where('is_active', true)->orderBy('nama')->pluck('nama', 'id')),

                Tables\Filters\SelectFilter::make('dokter_id')
                    ->label('Dokter')
                    ->options(\App\Models\Dokter::where('aktif', true)->orderBy('nama_lengkap')->pluck('nama_lengkap', 'id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Tindakan $record): bool => $record->status === 'pending'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Tindakan $record): bool => $record->status === 'pending'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()->can('delete_any_tindakan')),
                ]),
            ])
            ->defaultSort('tanggal_tindakan', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('input_by', Auth::id())
            ->with(['jenisTindakan', 'pasien', 'dokter', 'paramedis', 'nonParamedis']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTindakans::route('/'),
            'create' => Pages\CreateTindakan::route('/create'),
            'view' => Pages\ViewTindakan::route('/{record}'),
            'edit' => Pages\EditTindakan::route('/{record}/edit'),
        ];
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        $panel = $panel ?? 'petugas';

        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }
}

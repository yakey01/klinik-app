<?php

namespace App\Filament\Paramedis\Resources;

use App\Filament\Paramedis\Resources\TindakanParamedisResource\Pages;
use App\Models\Tindakan;
use App\Models\User;
use App\Models\Pegawai;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TindakanParamedisResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Tindakan Saya';

    protected static ?string $modelLabel = 'Tindakan';

    protected static ?string $pluralModelLabel = 'Tindakan';

    protected static ?int $navigationSort = 2;
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('pasien.nama')
                    ->label('Nama Pasien')
                    ->disabled(),
                Forms\Components\TextInput::make('jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->disabled(),
                Forms\Components\TextInput::make('dokter.nama')
                    ->label('Dokter Pelaksana')
                    ->disabled()
                    ->visible(fn ($record) => !empty($record?->dokter_id)),
                Forms\Components\Select::make('shift_id')
                    ->relationship('shift', 'nama')
                    ->label('Shift')
                    ->disabled(),
                Forms\Components\DatePicker::make('tanggal_tindakan')
                    ->label('Tanggal Tindakan')
                    ->disabled(),
                Forms\Components\TextInput::make('tarif')
                    ->label('Tarif')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
                Forms\Components\TextInput::make('jasa_paramedis')
                    ->label('Jasa Paramedis')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),
                Forms\Components\Textarea::make('catatan')
                    ->label('Catatan')
                    ->disabled(),
                Forms\Components\TextInput::make('status_validasi')
                    ->label('Status Validasi')
                    ->disabled(),
                Forms\Components\Textarea::make('komentar_validasi')
                    ->label('Komentar Validasi')
                    ->disabled()
                    ->visible(fn ($record) => !empty($record?->komentar_validasi)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pasien.nama')
                    ->label('Pasien')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dokter.nama')
                    ->label('Dokter')
                    ->searchable()
                    ->sortable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('shift.nama')
                    ->label('Shift')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tarif')
                    ->label('Tarif')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jasa_paramedis')
                    ->label('Jasa Paramedis')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ]),
                Tables\Columns\TextColumn::make('validated_at')
                    ->label('Divalidasi')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ])
                    ->default('disetujui'),
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('tanggal_tindakan', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        // Get the pegawai record for the current user (paramedis)
        $pegawai = Pegawai::where('email', $user->email)
            ->where('jenis_pegawai', 'Paramedis')
            ->first();
        
        if (!$pegawai) {
            // If no paramedis record found, return empty query
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        // Only show tindakan assigned to this paramedis that are validated (approved)
        return parent::getEloquentQuery()
            ->where('paramedis_id', $pegawai->id)
            ->where('status_validasi', 'disetujui')
            ->with(['pasien', 'jenisTindakan', 'dokter', 'shift', 'validatedBy']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTindakanParamediss::route('/'),
            'view' => Pages\ViewTindakanParamedis::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
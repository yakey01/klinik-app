<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengeluaranResource\Pages;
use App\Filament\Resources\PengeluaranResource\RelationManagers;
use App\Models\Pengeluaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PengeluaranResource extends Resource
{
    protected static ?string $model = Pengeluaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    
    protected static ?string $navigationGroup = 'Master Data';
    
    protected static ?string $modelLabel = 'Pengeluaran';
    
    protected static ?string $pluralModelLabel = 'Master Pengeluaran';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Master Pengeluaran')
                    ->schema([
                        Forms\Components\TextInput::make('kode_pengeluaran')
                            ->label('Kode Pengeluaran')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('OUT001')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('nama_pengeluaran')
                            ->label('Nama Pengeluaran')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Alat Tulis Kantor'),

                        Forms\Components\Select::make('kategori')
                            ->label('Kategori')
                            ->options([
                                'operasional' => 'Operasional',
                                'medis' => 'Medis',
                                'maintenance' => 'Maintenance',
                                'administrasi' => 'Administrasi',
                                'lainnya' => 'Lainnya',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->maxLength(500)
                            ->placeholder('Deskripsi lengkap pengeluaran ini')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Validasi')
                    ->schema([
                        Forms\Components\Select::make('status_validasi')
                            ->label('Status Validasi')
                            ->options([
                                'pending' => 'Menunggu Validasi',
                                'disetujui' => 'Disetujui',
                                'ditolak' => 'Ditolak',
                            ])
                            ->default('disetujui')
                            ->required(),

                        Forms\Components\Hidden::make('input_by')
                            ->default(fn () => auth()->id()),

                        Forms\Components\Hidden::make('validasi_by')
                            ->default(fn () => auth()->id()),

                        Forms\Components\Hidden::make('validasi_at')
                            ->default(fn () => now()),

                        Forms\Components\Hidden::make('tanggal')
                            ->default(fn () => now()),

                        Forms\Components\Hidden::make('nominal')
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_pengeluaran')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nama_pengeluaran')
                    ->label('Nama Pengeluaran')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->nama_pengeluaran),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'operasional' => 'success',
                        'medis' => 'danger',
                        'maintenance' => 'warning',
                        'administrasi' => 'info',
                        'lainnya' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'operasional' => 'Operasional',
                        'medis' => 'Medis',
                        'maintenance' => 'Maintenance',
                        'administrasi' => 'Administrasi',
                        'lainnya' => 'Lainnya',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->keterangan)
                    ->placeholder('Tidak ada keterangan'),

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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori')
                    ->options([
                        'operasional' => 'Operasional',
                        'medis' => 'Medis',
                        'maintenance' => 'Maintenance',
                        'administrasi' => 'Administrasi',
                        'lainnya' => 'Lainnya',
                    ]),
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->options([
                        'pending' => 'Menunggu Validasi',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPengeluarans::route('/'),
            'create' => Pages\CreatePengeluaran::route('/create'),
            'view' => Pages\ViewPengeluaran::route('/{record}'),
            'edit' => Pages\EditPengeluaran::route('/{record}/edit'),
        ];
    }
}

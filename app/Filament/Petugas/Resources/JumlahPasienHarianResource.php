<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages;
use App\Models\JumlahPasienHarian;
use App\Models\Dokter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;

class JumlahPasienHarianResource extends Resource
{
    protected static ?string $model = JumlahPasienHarian::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'Input Jumlah Pasien';
    
    protected static ?string $navigationGroup = '📝 Input Data';
    
    protected static ?string $modelLabel = 'Jumlah Pasien Harian';
    
    protected static ?string $pluralModelLabel = 'Data Jumlah Pasien Harian';

    protected static ?int $navigationSort = 4;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Pasien Harian')
                    ->description('Input data jumlah pasien per hari')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->columnSpan(1),

                                Forms\Components\Select::make('poli')
                                    ->label('Poli')
                                    ->options([
                                        'umum' => 'Poli Umum',
                                        'gigi' => 'Poli Gigi',
                                    ])
                                    ->required()
                                    ->default('umum')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('jumlah_pasien_umum')
                                    ->label('Jumlah Pasien Umum')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1)
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('jumlah_pasien_bpjs')
                                    ->label('Jumlah Pasien BPJS')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1)
                                    ->required()
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Select::make('dokter_id')
                            ->label('Dokter Pelaksana')
                            ->relationship('dokter', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih dokter pelaksana'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('poli')
                    ->label('Poli')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'umum' => 'primary',
                        'gigi' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'umum' => 'Poli Umum',
                        'gigi' => 'Poli Gigi',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('dokter.nama_lengkap')
                    ->label('Dokter Pelaksana')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah_pasien_umum')
                    ->label('Pasien Umum')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah_pasien_bpjs')
                    ->label('Pasien BPJS')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_pasien')
                    ->label('Total Pasien')
                    ->getStateUsing(fn (JumlahPasienHarian $record): int => 
                        $record->jumlah_pasien_umum + $record->jumlah_pasien_bpjs
                    )
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Input Oleh')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('poli')
                    ->label('Filter Poli')
                    ->options([
                        'umum' => 'Poli Umum',
                        'gigi' => 'Poli Gigi',
                    ]),

                SelectFilter::make('dokter')
                    ->label('Filter Dokter')
                    ->relationship('dokter', 'nama_lengkap')
                    ->searchable()
                    ->preload(),

                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListJumlahPasienHarians::route('/'),
            'create' => Pages\CreateJumlahPasienHarian::route('/create'),
            'edit' => Pages\EditJumlahPasienHarian::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('input_by', auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
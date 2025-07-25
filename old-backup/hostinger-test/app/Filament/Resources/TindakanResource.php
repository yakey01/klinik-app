<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TindakanResource\Pages;
use App\Filament\Resources\TindakanResource\RelationManagers;
use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TindakanResource extends Resource
{
    protected static ?string $model = Tindakan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tindakan')
                    ->schema([
                        Forms\Components\Select::make('pasien_id')
                            ->label('Pasien')
                            ->relationship('pasien', 'nama')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('jenis_tindakan_id')
                            ->label('Jenis Tindakan')
                            ->relationship('jenisTindakan', 'nama')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $jenisTindakan = \App\Models\JenisTindakan::find($state);
                                    if ($jenisTindakan) {
                                        $set('tarif', $jenisTindakan->tarif);
                                        $set('jasa_dokter', $jenisTindakan->jasa_dokter);
                                        $set('jasa_paramedis', $jenisTindakan->jasa_paramedis);
                                        $set('jasa_non_paramedis', $jenisTindakan->jasa_non_paramedis);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('dokter_id')
                            ->label('Dokter')
                            ->relationship('dokter', 'nama_lengkap')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('paramedis_id')
                            ->label('Paramedis')
                            ->relationship('paramedis', 'nama_lengkap')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('non_paramedis_id')
                            ->label('Non-Paramedis')
                            ->relationship('nonParamedis', 'nama_lengkap')
                            ->searchable()
                            ->preload(),

                        Forms\Components\DateTimePicker::make('tanggal_tindakan')
                            ->label('Tanggal Tindakan')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('shift_id')
                            ->label('Shift')
                            ->relationship('shift', 'nama')
                            ->required()
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Struktur Tarif')
                    ->schema([
                        Forms\Components\TextInput::make('tarif')
                            ->label('Tarif Total (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $tarif = floatval($state ?? 0);
                                $persentaseJaspel = floatval($get('persentase_jaspel') ?? 40);
                                
                                $jasaPetugas = $tarif * ($persentaseJaspel / 100);
                                
                                $set('jaspel_petugas_calculated', number_format($jasaPetugas, 0, ',', '.'));
                            }),

                        Forms\Components\TextInput::make('persentase_jaspel')
                            ->label('Persentase Jaspel (%)')
                            ->numeric()
                            ->default(40)
                            ->suffix('%')
                            ->placeholder('40')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $tarif = floatval($get('tarif') ?? 0);
                                $persentaseJaspel = floatval($state ?? 40);
                                
                                $jasaPetugas = $tarif * ($persentaseJaspel / 100);
                                
                                $set('jaspel_petugas_calculated', number_format($jasaPetugas, 0, ',', '.'));
                            }),

                        Forms\Components\TextInput::make('jaspel_petugas_calculated')
                            ->label('Jaspel Petugas (Rp)')
                            ->prefix('Rp')
                            ->disabled()
                            ->placeholder('0')
                            ->dehydrated(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Rincian Jasa Medis')
                    ->schema([
                        Forms\Components\TextInput::make('jasa_dokter')
                            ->label('Jasa Dokter (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->placeholder('0'),

                        Forms\Components\TextInput::make('jasa_paramedis')
                            ->label('Jasa Paramedis (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->placeholder('0'),

                        Forms\Components\TextInput::make('jasa_non_paramedis')
                            ->label('Jasa Non-Paramedis (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->placeholder('0'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Status dan Catatan')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'selesai' => 'Selesai',
                                'batal' => 'Batal',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan')
                            ->placeholder('Catatan tindakan (opsional)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pasien.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenisTindakan.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dokter.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paramedis.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nonParamedis.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shift.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_tindakan')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tarif')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jasa_dokter')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jasa_paramedis')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jasa_non_paramedis')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTindakans::route('/'),
            'create' => Pages\CreateTindakan::route('/create'),
            'edit' => Pages\EditTindakan::route('/{record}/edit'),
        ];
    }
}

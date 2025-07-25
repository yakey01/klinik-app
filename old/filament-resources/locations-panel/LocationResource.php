<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\ViewField;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationGroup = 'Master Data';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $modelLabel = 'Lokasi Kerja';
    
    protected static ?string $pluralModelLabel = 'Lokasi Kerja';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Lokasi')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Klinik Harapan Jaya'),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->required()
                                    ->numeric()
                                    ->minValue(-90)
                                    ->maxValue(90)
                                    ->step(0.00000001)
                                    ->placeholder('-6.2000000'),
                                
                                Forms\Components\TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->required()
                                    ->numeric()
                                    ->minValue(-180)
                                    ->maxValue(180)
                                    ->step(0.00000001)
                                    ->placeholder('106.8000000'),
                            ]),
                        
                        Forms\Components\TextInput::make('radius')
                            ->label('Radius (meter)')
                            ->required()
                            ->numeric()
                            ->default(100)
                            ->minValue(10)
                            ->maxValue(1000)
                            ->helperText('Radius geofencing dalam meter'),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make('📍 Koordinat GPS & Geofencing')
                    ->description('Pilih lokasi pada peta OSM dengan GPS detection')
                    ->schema([
                        ViewField::make('osm_map')
                            ->view('filament.forms.components.leaflet-osm-map')
                            ->label('📍 Pilih Lokasi pada Peta OSM')
                            ->columnSpanFull()
                            ->dehydrated(false), // Don't save this field to database
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lokasi')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('latitude')
                    ->label('Latitude')
                    ->numeric(8)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('longitude')
                    ->label('Longitude')
                    ->numeric(8)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('radius')
                    ->label('Radius')
                    ->suffix(' m')
                    ->sortable(),
                    
                Tables\Columns\ViewColumn::make('map_preview')
                    ->label('Peta')
                    ->view('filament.tables.columns.location-map-preview')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Jumlah Pengguna')
                    ->counts('users')
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Lokasi')
                    ->modalDescription('Apakah Anda yakin ingin menghapus lokasi ini? Pengguna yang terkait akan kehilangan akses lokasi kerja.')
                    ->modalSubmitActionLabel('Ya, Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Lokasi')
                        ->modalDescription('Apakah Anda yakin ingin menghapus lokasi yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Hapus'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Belum Ada Lokasi')
            ->emptyStateDescription('Mulai dengan menambahkan lokasi kerja baru.')
            ->emptyStateIcon('heroicon-o-map-pin');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'view' => Pages\ViewLocation::route('/{record}'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
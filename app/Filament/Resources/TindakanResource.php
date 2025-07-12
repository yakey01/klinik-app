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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('pasien_id')
                    ->relationship('pasien', 'id')
                    ->required(),
                Forms\Components\Select::make('jenis_tindakan_id')
                    ->relationship('jenisTindakan', 'id')
                    ->required(),
                Forms\Components\Select::make('dokter_id')
                    ->relationship('dokter', 'name')
                    ->required(),
                Forms\Components\Select::make('paramedis_id')
                    ->relationship('paramedis', 'name'),
                Forms\Components\Select::make('non_paramedis_id')
                    ->relationship('nonParamedis', 'name'),
                Forms\Components\Select::make('shift_id')
                    ->relationship('shift', 'name')
                    ->required(),
                Forms\Components\DateTimePicker::make('tanggal_tindakan')
                    ->required(),
                Forms\Components\TextInput::make('tarif')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('jasa_dokter')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('jasa_paramedis')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('jasa_non_paramedis')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Textarea::make('catatan')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required(),
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

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftTemplateResource\Pages;
use App\Filament\Resources\ShiftTemplateResource\RelationManagers;
use App\Models\ShiftTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShiftTemplateResource extends Resource
{
    protected static ?string $model = ShiftTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationGroup = 'Kalender & Jadwal';
    
    protected static ?string $navigationLabel = 'Template Shift';
    
    protected static ?string $modelLabel = 'Template Shift';
    
    protected static ?string $pluralModelLabel = 'Template Shift';

    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_shift')
                    ->required(),
                Forms\Components\TextInput::make('jam_masuk')
                    ->required(),
                Forms\Components\TextInput::make('jam_pulang')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_shift')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jam_masuk'),
                Tables\Columns\TextColumn::make('jam_pulang'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListShiftTemplates::route('/'),
            'create' => Pages\CreateShiftTemplate::route('/create'),
            'edit' => Pages\EditShiftTemplate::route('/{record}/edit'),
        ];
    }
}

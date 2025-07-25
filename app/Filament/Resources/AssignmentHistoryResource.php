<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignmentHistoryResource\Pages;
use App\Filament\Resources\AssignmentHistoryResource\RelationManagers;
use App\Models\AssignmentHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AssignmentHistoryResource extends Resource
{
    protected static ?string $model = AssignmentHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('work_location_id')
                    ->relationship('workLocation', 'name')
                    ->required(),
                Forms\Components\Select::make('previous_work_location_id')
                    ->relationship('previousWorkLocation', 'name'),
                Forms\Components\TextInput::make('assigned_by')
                    ->numeric(),
                Forms\Components\TextInput::make('assignment_method')
                    ->required(),
                Forms\Components\Textarea::make('assignment_reasons')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('assignment_score')
                    ->numeric(),
                Forms\Components\Textarea::make('metadata')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('workLocation.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('previousWorkLocation.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assigned_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignment_method')
                    ->searchable(),
                Tables\Columns\TextColumn::make('assignment_score')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListAssignmentHistories::route('/'),
            'create' => Pages\CreateAssignmentHistory::route('/create'),
            'edit' => Pages\EditAssignmentHistory::route('/{record}/edit'),
        ];
    }
}

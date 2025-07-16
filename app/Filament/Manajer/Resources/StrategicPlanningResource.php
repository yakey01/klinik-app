<?php

namespace App\Filament\Manajer\Resources;

use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StrategicPlanningResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = null;
    
    protected static ?string $navigationLabel = 'Strategic Planning';
    
    protected static ?string $navigationGroup = '📈 Reports & Intelligence';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                    
                Tables\Columns\BadgeColumn::make('cached_primary_role')
                    ->label('Role')
                    ->colors([
                        'primary' => 'admin',
                        'success' => 'manajer',
                        'warning' => 'bendahara',
                        'info' => 'petugas',
                    ]),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Bergabung')
                    ->date(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('manajer') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Manajer\Resources\StrategicPlanningResource\Pages\ListStrategicPlannings::route('/'),
        ];
    }
}
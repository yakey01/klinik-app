<?php

namespace App\Filament\Manajer\Resources;

use App\Models\Pasien;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OperationalAnalyticsResource extends Resource
{
    protected static ?string $model = Pasien::class;

    protected static ?string $navigationIcon = null;
    
    protected static ?string $navigationLabel = 'Operational Analytics';
    
    protected static ?string $navigationGroup = '🏥 Operations Management';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Pasien')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->date()
                    ->sortable(),
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
            'index' => \App\Filament\Manajer\Resources\OperationalAnalyticsResource\Pages\ListOperationalAnalytics::route('/'),
        ];
    }
}
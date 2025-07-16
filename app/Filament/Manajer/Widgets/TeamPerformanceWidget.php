<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Pegawai;

class TeamPerformanceWidget extends BaseWidget
{
    protected static ?string $heading = 'Team Performance';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Pegawai::query()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Employee'),
                Tables\Columns\TextColumn::make('jabatan')
                    ->label('Position')
                    ->badge(),
                Tables\Columns\TextColumn::make('performance_score')
                    ->label('Score')
                    ->suffix('%')
                    ->default('85'),
            ]);
    }
}
<?php

namespace App\Filament\Manajer\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\PermohonanCuti;

class ApprovalQueueWidget extends BaseWidget
{
    protected static ?string $heading = 'Pending Approvals';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PermohonanCuti::where('status', 'pending')->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('pegawai.nama')
                    ->label('Employee'),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Start Date')
                    ->date(),
                Tables\Columns\TextColumn::make('durasi_hari')
                    ->label('Days')
                    ->suffix(' days'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors(['warning' => 'pending']),
            ]);
    }
}
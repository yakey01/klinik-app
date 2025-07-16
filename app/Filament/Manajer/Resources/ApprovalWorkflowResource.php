<?php

namespace App\Filament\Manajer\Resources;

use App\Models\Pendapatan;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApprovalWorkflowResource extends Resource
{
    protected static ?string $model = Pendapatan::class;

    protected static ?string $navigationIcon = null;
    
    protected static ?string $navigationLabel = 'Approval Workflows';
    
    protected static ?string $navigationGroup = '⚙️ Management Tools';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_pendapatan')
                    ->label('Item')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Amount')
                    ->money('IDR'),
                    
                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_validasi')
                    ->options([
                        'pending' => 'Pending',
                        'disetujui' => 'Approved',
                        'ditolak' => 'Rejected',
                    ]),
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
            'index' => \App\Filament\Manajer\Resources\ApprovalWorkflowResource\Pages\ListApprovalWorkflows::route('/'),
        ];
    }
}
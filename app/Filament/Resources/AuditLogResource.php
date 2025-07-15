<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = '⚙️ Administrasi Sistem';
    
    protected static ?string $navigationLabel = 'Audit Log';
    
    protected static ?string $modelLabel = 'Audit Log';
    
    protected static ?string $pluralModelLabel = 'Audit Logs';
    
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user_name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->placeholder('System'),

                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login' => 'info',
                        'logout' => 'gray',
                        'exported' => 'primary',
                        'imported' => 'primary',
                        'bulk_update' => 'warning',
                        'bulk_delete' => 'danger',
                        'validation_approved' => 'success',
                        'validation_rejected' => 'danger',
                        'validation_submitted' => 'info',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model_type')
                    ->label('Model')
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? class_basename($state) : '-'
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model_id')
                    ->label('ID Model')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->searchable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('user_role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'supervisor' => 'primary',
                        'petugas' => 'info',
                        'paramedis' => 'success',
                        'dokter' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Aksi')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'exported' => 'Exported',
                        'imported' => 'Imported',
                        'bulk_update' => 'Bulk Update',
                        'bulk_delete' => 'Bulk Delete',
                        'validation_approved' => 'Validation Approved',
                        'validation_rejected' => 'Validation Rejected',
                        'validation_submitted' => 'Validation Submitted',
                    ])
                    ->multiple(),

                SelectFilter::make('model_type')
                    ->label('Model')
                    ->options([
                        'App\Models\Pasien' => 'Pasien',
                        'App\Models\Dokter' => 'Dokter',
                        'App\Models\Tindakan' => 'Tindakan',
                        'App\Models\Pendapatan' => 'Pendapatan',
                        'App\Models\Pengeluaran' => 'Pengeluaran',
                        'App\Models\PendapatanHarian' => 'Pendapatan Harian',
                        'App\Models\PengeluaranHarian' => 'Pengeluaran Harian',
                        'App\Models\JumlahPasienHarian' => 'Jumlah Pasien Harian',
                        'App\Models\User' => 'User',
                    ])
                    ->multiple(),

                SelectFilter::make('user_role')
                    ->label('Role User')
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'supervisor' => 'Supervisor',
                        'petugas' => 'Petugas',
                        'paramedis' => 'Paramedis',
                        'dokter' => 'Dokter',
                    ])
                    ->multiple(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
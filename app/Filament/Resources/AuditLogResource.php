<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Filament\Resources\AuditLogResource\RelationManagers;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    
    protected static ?string $navigationLabel = 'Audit Logs';
    
    protected static ?string $modelLabel = 'Audit Log';
    
    protected static ?string $pluralModelLabel = 'Audit Logs';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?int $navigationSort = 4;
    
    // Make the resource read-only
    protected static bool $canCreate = false;
    
    protected static bool $canEdit = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user.name')
                    ->label('User')
                    ->disabled(),
                Forms\Components\TextInput::make('action_description')
                    ->label('Action')
                    ->disabled(),
                Forms\Components\TextInput::make('model_name')
                    ->label('Model')
                    ->disabled(),
                Forms\Components\TextInput::make('model_id')
                    ->label('Model ID')
                    ->disabled(),
                Forms\Components\KeyValue::make('old_values')
                    ->label('Previous Values')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('new_values')
                    ->label('New Values')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ip_address')
                    ->label('IP Address')
                    ->disabled(),
                Forms\Components\Textarea::make('user_agent')
                    ->label('User Agent')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->disabled(),
                Forms\Components\TextInput::make('method')
                    ->label('HTTP Method')
                    ->disabled(),
                Forms\Components\TextInput::make('created_at')
                    ->label('Timestamp')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
                
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable()
                    ->placeholder('System'),
                
                TextColumn::make('action_description')
                    ->label('Action')
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => match($record->risk_level) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    }),
                
                TextColumn::make('model_name')
                    ->label('Model')
                    ->searchable()
                    ->placeholder('N/A'),
                
                TextColumn::make('model_id')
                    ->label('ID')
                    ->sortable()
                    ->placeholder('N/A'),
                
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->toggleable()
                    ->copyable(),
                
                TextColumn::make('method')
                    ->label('Method')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'GET' => 'success',
                        'POST' => 'primary',
                        'PUT', 'PATCH' => 'warning',
                        'DELETE' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                
                TextColumn::make('url')
                    ->label('URL')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        AuditLog::ACTION_CREATED => 'Created',
                        AuditLog::ACTION_UPDATED => 'Updated',
                        AuditLog::ACTION_DELETED => 'Deleted',
                        AuditLog::ACTION_LOGIN => 'Login',
                        AuditLog::ACTION_LOGOUT => 'Logout',
                        AuditLog::ACTION_PASSWORD_RESET => 'Password Reset',
                        AuditLog::ACTION_ROLE_CHANGED => 'Role Changed',
                        AuditLog::ACTION_SYSTEM_SETTING_CHANGED => 'System Setting Changed',
                        AuditLog::ACTION_FEATURE_FLAG_TOGGLED => 'Feature Flag Toggled',
                        AuditLog::ACTION_MAINTENANCE_MODE_TOGGLED => 'Maintenance Mode Toggled',
                        AuditLog::ACTION_SECURITY_EVENT => 'Security Event',
                    ]),
                
                SelectFilter::make('risk_level')
                    ->options([
                        'high' => 'High Risk',
                        'medium' => 'Medium Risk', 
                        'low' => 'Low Risk',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        $highRiskActions = [
                            AuditLog::ACTION_DELETED,
                            AuditLog::ACTION_ROLE_CHANGED,
                            AuditLog::ACTION_PERMISSION_GRANTED,
                            AuditLog::ACTION_PERMISSION_REVOKED,
                            AuditLog::ACTION_SYSTEM_SETTING_CHANGED,
                            AuditLog::ACTION_MAINTENANCE_MODE_TOGGLED,
                            AuditLog::ACTION_BULK_OPERATION,
                            AuditLog::ACTION_BACKUP_RESTORED,
                            AuditLog::ACTION_SECURITY_EVENT,
                        ];
                        
                        $mediumRiskActions = [
                            AuditLog::ACTION_UPDATED,
                            AuditLog::ACTION_PASSWORD_RESET,
                            AuditLog::ACTION_FEATURE_FLAG_TOGGLED,
                            AuditLog::ACTION_DATA_EXPORT,
                            AuditLog::ACTION_DATA_IMPORT,
                            AuditLog::ACTION_BACKUP_CREATED,
                        ];
                        
                        return match($data['value']) {
                            'high' => $query->whereIn('action', $highRiskActions),
                            'medium' => $query->whereIn('action', $mediumRiskActions),
                            'low' => $query->whereNotIn('action', array_merge($highRiskActions, $mediumRiskActions)),
                            default => $query,
                        };
                    }),
                
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable(),
                
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From Date'),
                        DatePicker::make('created_until')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                
                Tables\Filters\Filter::make('recent')
                    ->query(fn (Builder $query): Builder => $query->recent(24))
                    ->label('Last 24 Hours'),
                
                Tables\Filters\Filter::make('high_risk')
                    ->query(fn (Builder $query): Builder => $query->highRisk())
                    ->label('High Risk Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('view_changes')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->old_values) || !empty($record->new_values))
                    ->modalHeading('Change Details')
                    ->modalContent(view('filament.audit.changes-modal'))
                    ->modalWidth('4xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($records) {
                            // Export audit logs to CSV
                            $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => "attachment; filename=\"$filename\"",
                            ];
                            
                            $callback = function() use ($records) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, ['Date', 'User', 'Action', 'Model', 'IP Address', 'URL']);
                                
                                foreach ($records as $record) {
                                    fputcsv($file, [
                                        $record->created_at->format('Y-m-d H:i:s'),
                                        $record->user?->name ?? 'System',
                                        $record->action_description,
                                        $record->model_name,
                                        $record->ip_address,
                                        $record->url,
                                    ]);
                                }
                                fclose($file);
                            };
                            
                            Notification::make()
                                ->title('Audit logs exported')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                        
                    Tables\Actions\BulkAction::make('cleanup')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Clean up old audit logs')
                        ->modalDescription('This will delete audit logs older than 90 days. This action cannot be undone.')
                        ->action(function () {
                            $deleted = AuditLog::cleanup(90);
                            Notification::make()
                                ->title("Cleaned up {$deleted} old audit logs")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s') // Auto-refresh every 30 seconds
            ->deferLoading();
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
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }
}

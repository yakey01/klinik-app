<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class SecurityLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    
    protected static ?string $navigationLabel = 'Security Logs';
    
    protected static ?string $modelLabel = 'Security Log';
    
    protected static ?string $pluralModelLabel = 'Security Logs';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?int $navigationSort = 7;
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(['super-admin', 'admin']);
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('action', [
                'login_success',
                'login_failed',
                'login_rate_limited',
                'login_inactive_user',
                'logout',
                'password_changed',
                'password_reset',
                'two_factor_enabled',
                'two_factor_disabled',
                'two_factor_verified',
                'two_factor_failed',
                'two_factor_recovery_used',
                'two_factor_recovery_failed',
                'session_terminated',
                'all_sessions_terminated',
                'account_locked',
                'account_unlocked',
                'role_changed',
                'permission_granted',
                'permission_revoked',
                'security_event',
                'suspicious_activity',
            ])
            ->with('user');
    }

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
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('metadata')
                    ->label('Metadata')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ip_address')
                    ->label('IP Address')
                    ->disabled(),
                Forms\Components\Textarea::make('user_agent')
                    ->label('User Agent')
                    ->disabled()
                    ->columnSpanFull(),
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
                    ->placeholder('System')
                    ->description(fn ($record) => $record->user?->email),
                
                TextColumn::make('action_description')
                    ->label('Security Event')
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => match($record->action) {
                        'login_success' => 'success',
                        'login_failed', 'login_rate_limited' => 'danger',
                        'two_factor_enabled', 'two_factor_verified' => 'success',
                        'two_factor_failed', 'two_factor_recovery_failed' => 'danger',
                        'password_changed', 'password_reset' => 'warning',
                        'account_locked' => 'danger',
                        'account_unlocked' => 'success',
                        'session_terminated', 'all_sessions_terminated' => 'warning',
                        'security_event', 'suspicious_activity' => 'danger',
                        default => 'gray',
                    }),
                
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 50) {
                            return $state;
                        }
                        return null;
                    })
                    ->toggleable(),
                
                TextColumn::make('url')
                    ->label('URL')
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 30) {
                            return $state;
                        }
                        return null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Event Type')
                    ->options([
                        'login_success' => 'Login Success',
                        'login_failed' => 'Login Failed',
                        'login_rate_limited' => 'Rate Limited',
                        'logout' => 'Logout',
                        'password_changed' => 'Password Changed',
                        'password_reset' => 'Password Reset',
                        'two_factor_enabled' => '2FA Enabled',
                        'two_factor_disabled' => '2FA Disabled',
                        'two_factor_verified' => '2FA Verified',
                        'two_factor_failed' => '2FA Failed',
                        'session_terminated' => 'Session Terminated',
                        'account_locked' => 'Account Locked',
                        'account_unlocked' => 'Account Unlocked',
                        'security_event' => 'Security Event',
                        'suspicious_activity' => 'Suspicious Activity',
                    ]),
                
                SelectFilter::make('severity')
                    ->label('Severity')
                    ->options([
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        $highSeverityActions = [
                            'login_failed',
                            'login_rate_limited',
                            'two_factor_failed',
                            'account_locked',
                            'security_event',
                            'suspicious_activity',
                        ];
                        
                        $mediumSeverityActions = [
                            'password_changed',
                            'password_reset',
                            'two_factor_disabled',
                            'session_terminated',
                            'all_sessions_terminated',
                        ];
                        
                        return match($data['value']) {
                            'high' => $query->whereIn('action', $highSeverityActions),
                            'medium' => $query->whereIn('action', $mediumSeverityActions),
                            'low' => $query->whereNotIn('action', array_merge($highSeverityActions, $mediumSeverityActions)),
                            default => $query,
                        };
                    }),
                
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                
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
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subHours(24)))
                    ->label('Last 24 Hours'),
                
                Tables\Filters\Filter::make('failed_attempts')
                    ->query(fn (Builder $query): Builder => $query->whereIn('action', ['login_failed', 'two_factor_failed']))
                    ->label('Failed Attempts Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('view_metadata')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->metadata))
                    ->modalHeading('Event Metadata')
                    ->modalContent(function ($record) {
                        return view('filament.modals.security-log-metadata', ['record' => $record]);
                    })
                    ->modalWidth('3xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($records) {
                            $filename = 'security_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => "attachment; filename=\"$filename\"",
                            ];
                            
                            $callback = function() use ($records) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, ['Date', 'User', 'Event', 'IP Address', 'Description', 'URL']);
                                
                                foreach ($records as $record) {
                                    fputcsv($file, [
                                        $record->created_at->format('Y-m-d H:i:s'),
                                        $record->user?->name ?? 'System',
                                        $record->action_description,
                                        $record->ip_address,
                                        $record->description,
                                        $record->url,
                                    ]);
                                }
                                fclose($file);
                            };
                            
                            Notification::make()
                                ->title('Security logs exported')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
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
            'index' => Pages\ListSecurityLogs::route('/'),
            'view' => Pages\ViewSecurityLog::route('/{record}'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $criticalCount = static::getEloquentQuery()
            ->whereIn('action', ['login_failed', 'security_event', 'suspicious_activity'])
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
            
        return $criticalCount > 0 ? (string) $criticalCount : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'danger' : null;
    }
}
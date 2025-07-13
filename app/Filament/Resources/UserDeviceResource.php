<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserDeviceResource\Pages;
use App\Filament\Resources\UserDeviceResource\RelationManagers;
use App\Models\UserDevice;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class UserDeviceResource extends Resource
{
    protected static ?string $model = UserDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationLabel = 'Device Management';
    protected static ?string $modelLabel = 'User Device';
    protected static ?string $pluralModelLabel = 'User Devices';
    protected static ?string $navigationGroup = 'Presensi';
    protected static ?int $navigationSort = 45;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Device Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('device_id')
                            ->label('Device ID')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('device_name')
                            ->label('Device Name')
                            ->placeholder('iPhone 13 Pro, Samsung Galaxy S23')
                            ->maxLength(255),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('device_type')
                                    ->label('Device Type')
                                    ->options([
                                        'mobile' => 'Mobile',
                                        'tablet' => 'Tablet',
                                        'web' => 'Web Browser',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('platform')
                                    ->label('Platform')
                                    ->options([
                                        'iOS' => 'iOS',
                                        'Android' => 'Android',
                                        'Web' => 'Web',
                                        'unknown' => 'Unknown',
                                    ])
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Technical Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('os_version')
                                    ->label('OS Version')
                                    ->placeholder('iOS 16.1, Android 13'),

                                Forms\Components\TextInput::make('browser_name')
                                    ->label('Browser Name')
                                    ->placeholder('Chrome, Safari, Mobile App'),
                            ]),

                        Forms\Components\TextInput::make('browser_version')
                            ->label('Browser Version'),

                        Forms\Components\Textarea::make('user_agent')
                            ->label('User Agent')
                            ->rows(2),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('ip_address')
                                    ->label('IP Address'),

                                Forms\Components\TextInput::make('mac_address')
                                    ->label('MAC Address'),
                            ]),

                        Forms\Components\Textarea::make('device_specs')
                            ->label('Device Specifications (JSON)')
                            ->rows(3)
                            ->placeholder('{"ram": "8GB", "storage": "256GB"}'),
                    ]),

                Forms\Components\Section::make('Security & Status')
                    ->schema([
                        Forms\Components\TextInput::make('device_fingerprint')
                            ->label('Device Fingerprint')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('push_token')
                            ->label('Push Token'),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),

                                Forms\Components\Toggle::make('is_primary')
                                    ->label('Primary Device')
                                    ->default(false),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Active',
                                        'suspended' => 'Suspended',
                                        'revoked' => 'Revoked',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(function ($record) {
                        $deviceCount = UserDevice::where('user_id', $record->user_id)->where('is_active', true)->count();
                        return "📱 {$deviceCount} device(s)";
                    }),

                Tables\Columns\TextColumn::make('formatted_device_info')
                    ->label('Device Info')
                    ->searchable(['device_name', 'platform', 'os_version'])
                    ->sortable(['device_name']),

                Tables\Columns\TextColumn::make('device_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mobile' => 'success',
                        'tablet' => 'info',
                        'web' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => 'revoked',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean(),

                Tables\Columns\IconColumn::make('verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->verified_at)),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'revoked' => 'Revoked',
                    ]),

                SelectFilter::make('device_type')
                    ->label('Device Type')
                    ->options([
                        'mobile' => 'Mobile',
                        'tablet' => 'Tablet',
                        'web' => 'Web',
                    ]),

                SelectFilter::make('platform')
                    ->label('Platform')
                    ->options([
                        'iOS' => 'iOS',
                        'Android' => 'Android',
                        'Web' => 'Web',
                    ]),

                Filter::make('verified')
                    ->label('Verified Only')
                    ->query(fn (Builder $query): Builder => $query->verified()),

                Filter::make('primary')
                    ->label('Primary Devices Only')
                    ->query(fn (Builder $query): Builder => $query->primary()),
                    
                Filter::make('multiple_devices')
                    ->label('🚨 Users with Multiple Devices')
                    ->query(function (Builder $query): Builder {
                        $usersWithMultiple = UserDevice::select('user_id')
                            ->where('is_active', true)
                            ->groupBy('user_id')
                            ->havingRaw('COUNT(*) > 1')
                            ->pluck('user_id');
                            
                        return $query->whereIn('user_id', $usersWithMultiple);
                    }),
                    
                Filter::make('unverified')
                    ->label('⚠️ Unverified Devices')
                    ->query(fn (Builder $query): Builder => $query->whereNull('verified_at')),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => is_null($record->verified_at))
                    ->action(function ($record) {
                        $record->verify();
                        Notification::make()
                            ->title('✅ Device verified successfully')
                            ->body($record->formatted_device_info . ' is now verified')
                            ->success()
                            ->send();
                    }),

                Action::make('set_primary')
                    ->label('Set Primary')
                    ->icon('heroicon-m-star')
                    ->color('warning')
                    ->visible(fn ($record) => !$record->is_primary && $record->status === 'active')
                    ->action(function ($record) {
                        // Remove primary status from other devices of the same user
                        UserDevice::where('user_id', $record->user_id)
                            ->where('id', '!=', $record->id)
                            ->update(['is_primary' => false]);
                        
                        // Set this device as primary
                        $record->update(['is_primary' => true]);
                        
                        Notification::make()
                            ->title('⭐ Primary device updated')
                            ->body($record->formatted_device_info . ' is now the primary device')
                            ->success()
                            ->send();
                    }),

                Action::make('force_single_device')
                    ->label('🔒 Force Single Device')
                    ->icon('heroicon-m-device-phone-mobile')
                    ->color('danger')
                    ->visible(function ($record) {
                        return UserDevice::where('user_id', $record->user_id)->where('is_active', true)->count() > 1;
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Force Single Device Policy')
                    ->modalDescription('This will deactivate all other devices for this user and keep only this device active.')
                    ->action(function ($record) {
                        // Deactivate all other devices for this user
                        $deactivatedCount = UserDevice::where('user_id', $record->user_id)
                            ->where('id', '!=', $record->id)
                            ->update([
                                'is_active' => false,
                                'status' => 'suspended',
                                'is_primary' => false
                            ]);
                        
                        // Make this device primary and active
                        $record->update([
                            'is_active' => true,
                            'status' => 'active',
                            'is_primary' => true
                        ]);
                        
                        Notification::make()
                            ->title('🔒 Single device policy enforced')
                            ->body("Deactivated {$deactivatedCount} other device(s). Only " . $record->formatted_device_info . ' remains active.')
                            ->success()
                            ->send();
                    }),

                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'revoked')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->revoke();
                        Notification::make()
                            ->title('❌ Device revoked successfully')
                            ->body($record->formatted_device_info . ' has been revoked')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_verify')
                        ->label('✅ Verify Selected')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            $verified = 0;
                            foreach ($records as $record) {
                                if (is_null($record->verified_at)) {
                                    $record->verify();
                                    $verified++;
                                }
                            }
                            
                            Notification::make()
                                ->title("✅ Verified {$verified} device(s)")
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('bulk_revoke')
                        ->label('❌ Revoke Selected')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $revoked = 0;
                            foreach ($records as $record) {
                                if ($record->status !== 'revoked') {
                                    $record->revoke();
                                    $revoked++;
                                }
                            }
                            
                            Notification::make()
                                ->title("❌ Revoked {$revoked} device(s)")
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('enforce_single_device_policy')
                        ->label('🔒 Enforce Single Device Policy')
                        ->icon('heroicon-m-shield-check')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Enforce Single Device Policy')
                        ->modalDescription('This will find users with multiple active devices and keep only their primary device active.')
                        ->action(function () {
                            $usersWithMultipleDevices = UserDevice::select('user_id')
                                ->where('is_active', true)
                                ->groupBy('user_id')
                                ->havingRaw('COUNT(*) > 1')
                                ->pluck('user_id');
                            
                            $totalDeactivated = 0;
                            
                            foreach ($usersWithMultipleDevices as $userId) {
                                // Get primary device or the most recent one
                                $primaryDevice = UserDevice::where('user_id', $userId)
                                    ->where('is_active', true)
                                    ->where('is_primary', true)
                                    ->first();
                                    
                                if (!$primaryDevice) {
                                    $primaryDevice = UserDevice::where('user_id', $userId)
                                        ->where('is_active', true)
                                        ->orderBy('last_login_at', 'desc')
                                        ->first();
                                }
                                
                                if ($primaryDevice) {
                                    // Deactivate all other devices
                                    $deactivated = UserDevice::where('user_id', $userId)
                                        ->where('id', '!=', $primaryDevice->id)
                                        ->where('is_active', true)
                                        ->update([
                                            'is_active' => false,
                                            'status' => 'suspended',
                                            'is_primary' => false
                                        ]);
                                    
                                    // Ensure primary device is marked correctly
                                    $primaryDevice->update([
                                        'is_primary' => true,
                                        'status' => 'active'
                                    ]);
                                    
                                    $totalDeactivated += $deactivated;
                                }
                            }
                            
                            Notification::make()
                                ->title('🔒 Single device policy enforced')
                                ->body("Found {$usersWithMultipleDevices->count()} users with multiple devices. Deactivated {$totalDeactivated} excess devices.")
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_login_at', 'desc')
            ->poll('60s'); // Auto refresh every minute
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
            'index' => Pages\ListUserDevices::route('/'),
            'create' => Pages\CreateUserDevice::route('/create'),
            'view' => Pages\ViewUserDevice::route('/{record}'),
            'edit' => Pages\EditUserDevice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

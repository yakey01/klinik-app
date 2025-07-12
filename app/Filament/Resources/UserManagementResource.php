<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserManagementResource\Pages;
use App\Models\User;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Spatie\Permission\Models\Role as SpatieRole;

class UserManagementResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'User Management';
    
    protected static ?string $modelLabel = 'User';
    
    protected static ?string $pluralModelLabel = 'Users';
    
    protected static ?string $navigationGroup = 'Admin Settings';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('👤 User Information')
                    ->description('Basic user account details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter full name')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->placeholder('user@dokterku.com')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('nip')
                            ->label('Employee ID (NIP)')
                            ->unique(User::class, 'nip', ignoreRecord: true)
                            ->placeholder('Auto-generated if empty')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('no_telepon')
                            ->label('Phone Number')
                            ->tel()
                            ->placeholder('08xxxxxxxxxx')
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('tanggal_bergabung')
                            ->label('Join Date')
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('🔐 Authentication & Security')
                    ->description('Password and access control')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->same('password_confirmation')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->helperText('Minimum 8 characters required')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(false)
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Account Active')
                            ->default(true)
                            ->helperText('Active users can log in and access their panels')
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('👥 Role Assignment')
                    ->description('Assign roles and permissions')
                    ->schema([
                        Forms\Components\Select::make('role_id')
                            ->label('Primary Role (Legacy)')
                            ->relationship('role', 'display_name')
                            ->searchable()
                            ->preload()
                            ->helperText('Legacy role system for backward compatibility')
                            ->columnSpan(1),

                        Forms\Components\Select::make('roles')
                            ->label('Spatie Roles')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->searchable()
                            ->helperText('New role-based permission system')
                            ->columnSpan(2),

                        Forms\Components\Select::make('permissions')
                            ->label('Direct Permissions')
                            ->multiple()
                            ->relationship('permissions', 'name')
                            ->preload()
                            ->searchable()
                            ->helperText('Additional permissions outside of roles')
                            ->columnSpan(3),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('📋 Additional Information')
                    ->description('Optional user details')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Admin Notes')
                            ->rows(3)
                            ->placeholder('Internal notes about this user...')
                            ->columnSpan(3),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=3b82f6&color=fff')
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->nip ? "NIP: {$record->nip}" : 'No NIP'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('role.display_name')
                    ->label('Primary Role')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Admin' => 'danger',
                        'Manajer' => 'warning',
                        'Bendahara' => 'info',
                        'Petugas' => 'success',
                        'Dokter' => 'primary',
                        'Paramedis' => 'emerald',
                        default => 'gray'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Spatie Roles')
                    ->badge()
                    ->separator(',')
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->color('primary'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_bergabung')
                    ->label('Join Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('no_telepon')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Primary Role')
                    ->relationship('role', 'display_name')
                    ->placeholder('All Roles'),

                Tables\Filters\SelectFilter::make('roles')
                    ->label('Spatie Roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->placeholder('All Roles'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Account Status')
                    ->placeholder('All Users')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),

                Tables\Filters\Filter::make('created_from')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From')
                            ->placeholder('Select date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('impersonate')
                    ->label('Login As')
                    ->icon('heroicon-o-user-circle')
                    ->color('warning')
                    ->visible(fn ($record) => auth()->user()->hasRole('super_admin') && $record->id !== auth()->id())
                    ->action(function ($record) {
                        // Implement impersonation logic here
                        Notification::make()
                            ->title('Feature Coming Soon')
                            ->body('User impersonation will be available in the next update.')
                            ->info()
                            ->send();
                    }),

                Tables\Actions\Action::make('reset_password')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reset User Password')
                    ->modalDescription('This will generate a new password and send it to the user\'s email.')
                    ->action(function ($record) {
                        $newPassword = \Str::random(12);
                        $record->update(['password' => Hash::make($newPassword)]);
                        
                        // Here you would send an email with the new password
                        Notification::make()
                            ->title('Password Reset')
                            ->body("New password generated: {$newPassword}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->iconButton(),

                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => true]));
                            
                            Notification::make()
                                ->title('Users Activated')
                                ->body(count($records) . ' users have been activated.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => false]));
                            
                            Notification::make()
                                ->title('Users Deactivated')
                                ->body(count($records) . ' users have been deactivated.')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->searchable()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->persistFiltersInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserManagements::route('/'),
            'create' => Pages\CreateUserManagement::route('/create'),
            'edit' => Pages\EditUserManagement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'warning' : 'primary';
    }
}
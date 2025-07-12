<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleManagementResource\Pages;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class RoleManagementResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    
    protected static ?string $navigationLabel = 'Role Management';
    
    protected static ?string $modelLabel = 'Role';
    
    protected static ?string $pluralModelLabel = 'Roles';
    
    protected static ?string $navigationGroup = 'Admin Settings';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🛡️ Role Information')
                    ->description('Basic role details and metadata')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->unique(Role::class, 'name', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('e.g., super_admin, manager, user')
                            ->helperText('Use lowercase with underscores for system consistency')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('display_name')
                            ->label('Display Name')
                            ->maxLength(255)
                            ->placeholder('e.g., Super Administrator, Manager, User')
                            ->helperText('Human-readable name shown in the interface')
                            ->columnSpan(1),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Describe the role responsibilities and access level...')
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('🔐 Permissions')
                    ->description('Assign permissions to this role')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Role Permissions')
                            ->relationship('permissions', 'name')
                            ->options(function () {
                                return Permission::all()
                                    ->groupBy(function ($permission) {
                                        // Group permissions by resource (before the first underscore)
                                        $parts = explode('_', $permission->name);
                                        return count($parts) > 1 ? $parts[0] : 'General';
                                    })
                                    ->map(function ($permissions, $group) {
                                        return $permissions->pluck('name', 'id')->toArray();
                                    })
                                    ->toArray();
                            })
                            ->columns(3)
                            ->searchable()
                            ->bulkToggleable()
                            ->gridDirection('row'),
                    ]),

                Forms\Components\Section::make('👥 Users with this Role')
                    ->description('Users currently assigned to this role')
                    ->schema([
                        Forms\Components\Placeholder::make('users_count')
                            ->label('Total Users')
                            ->content(function (?Role $record) {
                                if (!$record) return '0';
                                return $record->users()->count() . ' users have this role';
                            }),

                        Forms\Components\Placeholder::make('recent_users')
                            ->label('Recent Users')
                            ->content(function (?Role $record) {
                                if (!$record) return 'No users assigned yet';
                                
                                $recentUsers = $record->users()
                                    ->latest()
                                    ->take(5)
                                    ->get(['name', 'email']);
                                
                                if ($recentUsers->isEmpty()) {
                                    return 'No users assigned to this role';
                                }
                                
                                $userList = $recentUsers->map(fn ($user) => 
                                    "<div class='flex items-center space-x-2 mb-1'>
                                        <span class='font-medium'>{$user->name}</span>
                                        <span class='text-sm text-gray-500'>({$user->email})</span>
                                    </div>"
                                )->join('');
                                
                                return new \Illuminate\Support\HtmlString($userList);
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (?Role $record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable()
                    ->icon('heroicon-m-identification'),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Display Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->description ?: 'No description'),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state === 0 => 'gray',
                        $state <= 5 => 'success',
                        $state <= 20 => 'warning',
                        default => 'danger'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_users')
                    ->label('Has Users')
                    ->query(fn (Builder $query): Builder => $query->has('users')),

                Tables\Filters\Filter::make('no_users')
                    ->label('No Users')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('users')),

                Tables\Filters\SelectFilter::make('guard_name')
                    ->label('Guard')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('assign_users')
                    ->label('Assign Users')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('users')
                            ->label('Select Users')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return \App\Models\User::pluck('name', 'id');
                            })
                            ->helperText('Select users to assign this role to'),
                    ])
                    ->action(function (array $data, Role $record) {
                        foreach ($data['users'] as $userId) {
                            $user = \App\Models\User::find($userId);
                            if ($user && !$user->hasRole($record)) {
                                $user->assignRole($record);
                            }
                        }
                        
                        Notification::make()
                            ->title('Users Assigned')
                            ->body('Selected users have been assigned to the ' . $record->name . ' role.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('clone_permissions')
                    ->label('Clone Permissions')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->form([
                        Forms\Components\Select::make('source_role')
                            ->label('Copy Permissions From')
                            ->options(fn (Role $record) => 
                                Role::where('id', '!=', $record->id)
                                    ->pluck('name', 'id')
                            )
                            ->required()
                            ->helperText('Select a role to copy permissions from'),
                    ])
                    ->action(function (array $data, Role $record) {
                        $sourceRole = Role::find($data['source_role']);
                        if ($sourceRole) {
                            $record->syncPermissions($sourceRole->permissions);
                            
                            Notification::make()
                                ->title('Permissions Copied')
                                ->body("Permissions copied from {$sourceRole->name} to {$record->name}.")
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make()
                    ->iconButton(),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading(fn (Role $record) => "Delete role '{$record->name}'?")
                    ->modalDescription('This will remove the role and all its associations. This action cannot be undone.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assign_permission')
                        ->label('Assign Permission to All')
                        ->icon('heroicon-o-key')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('permission')
                                ->label('Select Permission')
                                ->options(Permission::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            $permission = Permission::find($data['permission']);
                            foreach ($records as $role) {
                                $role->givePermissionTo($permission);
                            }
                            
                            Notification::make()
                                ->title('Permission Assigned')
                                ->body("Permission '{$permission->name}' assigned to " . count($records) . ' roles.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoleManagements::route('/'),
            'create' => Pages\CreateRoleManagement::route('/create'),
            'edit' => Pages\EditRoleManagement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
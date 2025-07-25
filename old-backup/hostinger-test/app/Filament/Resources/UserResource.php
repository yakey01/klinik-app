<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Request;
use App\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = '👥 USER MANAGEMENT';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'Pengguna';
    
    protected static ?string $pluralModelLabel = 'Pengguna';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('view_any_user') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasPermissionTo('create_user') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasPermissionTo('update_user') ?? false;
    }

    public static function canDelete($record): bool
    {
        // Allow admin users to delete users (temporary fix for Tina deletion)
        $user = auth()->user();
        return $user && ($user->hasRole('admin') || $user->hasPermissionTo('delete_user'));
    }

    public static function canDeleteAny(): bool
    {
        // Allow admin users to delete any user (temporary fix for Tina deletion)
        $user = auth()->user();
        return $user && ($user->hasRole('admin') || $user->hasPermissionTo('delete_any_user'));
    }

    public static function form(Form $form): Form
    {
        $source = request()->get('source'); // Detect source from URL parameter
        
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pribadi')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->nullable()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rules([
                                'nullable',
                                'email',
                                function () {
                                    return new \App\Rules\PreventDuplicateAccounts(
                                        request()->route('record')
                                    );
                                }
                            ])
                            ->helperText('Email opsional - bisa kosong jika user login dengan username')
                            ->placeholder('Masukkan alamat email (opsional)')
                            ->dehydrated(true)
                            ->live(onBlur: true)
                            ->dehydrateStateUsing(function (?string $state) {
                                // Return null for empty strings to properly handle nullable field
                                return filled($state) ? trim($state) : null;
                            })
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                if (empty($state)) return;
                                
                                // Real-time validation feedback for email
                                $existing = \App\Models\User::where('email', $state)
                                    ->when(request()->route('record'), function ($query) {
                                        return $query->where('id', '!=', request()->route('record'));
                                    })
                                    ->first();
                                    
                                if ($existing) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('⚠️ Email Sudah Digunakan')
                                        ->body("Email '{$state}' sudah digunakan oleh user '{$existing->name}' (Username: {$existing->username}). Silakan gunakan email yang berbeda.")
                                        ->danger()
                                        ->persistent()
                                        ->send();
                                }
                            }),
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP')
                            ->maxLength(255)
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->required(function (Forms\Get $get) use ($source) {
                                // NIP is required for certain roles only
                                $roleId = $get('role_id') ?? request()->get('role_id');
                                if ($roleId) {
                                    $role = \App\Models\Role::find($roleId);
                                    // NIP required for: petugas, bendahara, paramedis
                                    // NIP optional for: dokter, pegawai, non_paramedis
                                    return $role && in_array($role->name, ['petugas', 'bendahara', 'paramedis']);
                                }
                                return false; // Default not required
                            })
                            ->helperText(function (Forms\Get $get) use ($source) {
                                $roleId = $get('role_id') ?? request()->get('role_id');
                                if ($roleId) {
                                    $role = \App\Models\Role::find($roleId);
                                    if ($role && in_array($role->name, ['dokter', 'pegawai', 'non_paramedis'])) {
                                        return '📋 NIP opsional untuk role ' . ($role->display_name ?? $role->name) . ' - boleh dikosongkan';
                                    }
                                }
                                return 'NIP harus unik dalam sistem - tidak boleh sama dengan NIP lain';
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                if (empty($state)) return;
                                
                                // Real-time validation feedback for NIP uniqueness
                                $existing = \App\Models\User::where('nip', $state)
                                    ->when(request()->route('record'), function ($query) {
                                        return $query->where('id', '!=', request()->route('record'));
                                    })
                                    ->first();
                                    
                                if ($existing) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('⚠️ NIP Sudah Digunakan')
                                        ->body("NIP '{$state}' sudah digunakan oleh user '{$existing->name}' (Username: {$existing->username}). Silakan gunakan NIP yang berbeda.")
                                        ->danger()
                                        ->persistent()
                                        ->send();
                                }
                            }),
                        Forms\Components\TextInput::make('no_telepon')
                            ->label('No. Telepon')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('tanggal_bergabung')
                            ->label('Tanggal Bergabung')
                            ->default(now()),
                    ])->columns(2),
                    
                Forms\Components\Section::make('🔐 Akun & Keamanan')
                    ->description('Pengaturan username, password, dan role user')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('username')
                                    ->label('Username Login')
                                    ->maxLength(50)
                                    ->nullable()
                                    ->placeholder('Masukkan username unik untuk login')
                                    ->helperText('Username harus unik - tidak boleh sama dengan username lain dalam sistem')
                                    ->rules([
                                        'regex:/^[a-zA-Z0-9\s.,-]+$/',
                                        function () {
                                            return new \App\Rules\UniqueUsernamePerRole(
                                                request()->get('role_id'),
                                                request()->route('record')
                                            );
                                        }
                                    ])
                                    ->minLength(3)
                                    ->suffixIcon('heroicon-m-user')
                                    ->required(function (Forms\Get $get) {
                                        $roleId = $get('role_id') ?? request()->get('role_id');
                                        if ($roleId) {
                                            $role = \App\Models\Role::find($roleId);
                                            return $role && in_array($role->name, ['petugas', 'bendahara', 'paramedis']);
                                        }
                                        return false;
                                    })
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                        if (empty($state)) return;
                                        
                                        // Check if username already exists
                                        $existing = \App\Models\User::where('username', $state)
                                            ->when(request()->route('record'), function ($query) {
                                                return $query->where('id', '!=', request()->route('record'));
                                            })
                                            ->first();
                                            
                                        if ($existing) {
                                            $set('username', '');
                                            \Filament\Notifications\Notification::make()
                                                ->title('Username Sudah Digunakan')
                                                ->body("Username '{$state}' sudah digunakan oleh user lain. Silakan pilih username yang berbeda.")
                                                ->danger()
                                                ->send();
                                        }
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('password')
                                    ->label('Password Baru')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->minLength(6)
                                    ->maxLength(50)
                                    ->placeholder(fn (string $operation): string => 
                                        $operation === 'create' ? 'Masukkan password' : 'Kosongkan jika tidak ingin mengubah')
                                    ->helperText('Minimal 6 karakter')
                                    ->suffixIcon('heroicon-m-key')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Konfirmasi Password')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->placeholder('Ketik ulang password')
                                    ->helperText('Harus sama dengan password baru')
                                    ->same('password')
                                    ->requiredWith('password')
                                    ->suffixIcon('heroicon-m-check-circle')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Placeholder::make('password_security_info')
                            ->label('ℹ️ Informasi Keamanan Password')
                            ->content(function () {
                                return new \Illuminate\Support\HtmlString('
                                    <div class="text-sm text-gray-600 dark:text-gray-400 bg-amber-50 dark:bg-amber-900/20 p-3 rounded-lg border border-amber-200 dark:border-amber-800">
                                        <ul class="space-y-1">
                                            <li>• Password minimal 6 karakter, maksimal 50 karakter</li>
                                            <li>• Gunakan kombinasi huruf besar, huruf kecil, angka, dan simbol</li>
                                            <li>• Konfirmasi password harus sama persis dengan password baru</li>
                                            <li>• Klik ikon mata (👁) untuk melihat/menyembunyikan password</li>
                                            <li>• Untuk edit: kosongkan jika tidak ingin mengubah password</li>
                                        </ul>
                                    </div>
                                ');
                            })
                            ->columnSpan('full'),
                        
                        // Role selection for user management
                        Forms\Components\Select::make('role_id')
                            ->label('Menentukan role pengguna sebagai:')
                            ->options(function () use ($source) {
                                // For user management and staff management, focus on the three main roles
                                if (!$source || $source === 'user_management' || $source === 'staff_management') {
                                    return \App\Models\Role::whereIn('name', ['petugas', 'bendahara', 'paramedis'])
                                        ->where('is_active', true)
                                        ->pluck('display_name', 'id');
                                }
                                
                                // For other sources, show all roles
                                return \App\Models\Role::where('is_active', true)
                                    ->pluck('display_name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder('Pilih role untuk user ini')
                            ->visible(fn () => $source !== 'dokter' && $source !== 'pegawai')
                            ->hint(function () use ($source) {
                                if (!$source || $source === 'user_management' || $source === 'staff_management') {
                                    return 'Admin bisa membuat satu pegawai memiliki role sebagai Bendahara, Paramedis atau Petugas';
                                }
                                return 'Pilih role untuk user ini';
                            })
                            ->helperText('Role akan menentukan akses dan permissions user dalam sistem')
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // Clear username when role changes to force revalidation
                                $set('username', '');
                            }),
                            
                        // Role information for dokter source
                        Forms\Components\Placeholder::make('role_info_dokter')
                            ->label('Role yang akan ditetapkan')
                            ->content('Role: Dokter (otomatis)')
                            ->visible(fn () => $source === 'dokter'),
                            
                        // Employee type selection for pegawai source
                        Forms\Components\Select::make('employee_type')
                            ->label('Jenis Pegawai')
                            ->options([
                                'paramedis' => 'Paramedis',
                                'non_paramedis' => 'Non-Paramedis',
                            ])
                            ->required()
                            ->default('non_paramedis')
                            ->visible(fn () => $source === 'pegawai')
                            ->live()
                            ->hint('Pilih jenis pegawai untuk menentukan role'),
                            
                        // Show selected role for pegawai
                        Forms\Components\Placeholder::make('role_preview')
                            ->label('Role yang akan ditetapkan')
                            ->content(function (Forms\Get $get) {
                                $employeeType = $get('employee_type');
                                return match($employeeType) {
                                    'paramedis' => 'Role: Paramedis',
                                    'non_paramedis' => 'Role: Petugas',
                                    default => 'Role: (belum dipilih)'
                                };
                            })
                            ->visible(fn () => $source === 'pegawai'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(2),
                    
                Forms\Components\Section::make('ℹ️ Panduan User Management')
                    ->description('Informasi penting untuk manajemen pengguna')
                    ->schema([
                        Forms\Components\Placeholder::make('user_management_info')
                            ->label('Persyaratan Username dan Validasi Akun')
                            ->content(function () {
                                return new \Illuminate\Support\HtmlString('
                                    <div class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <h4 class="font-semibold mb-2 text-blue-800 dark:text-blue-200">📧 Aturan Email:</h4>
                                        <ul class="space-y-1 mb-3">
                                            <li>• <strong>Email OPSIONAL</strong> - user dapat dibuat tanpa email</li>
                                            <li>• Jika email diisi, harus dalam format yang valid</li>
                                            <li>• Email harus unik jika digunakan</li>
                                            <li>• User tanpa email harus login menggunakan username</li>
                                        </ul>
                                        
                                        <h4 class="font-semibold mb-2 text-blue-800 dark:text-blue-200">📋 Aturan Username:</h4>
                                        <ul class="space-y-1 mb-3">
                                            <li>• <strong>Username WAJIB</strong> untuk role: Petugas, Bendahara, Paramedis</li>
                                            <li>• Username harus <strong>unik per role</strong></li>
                                            <li>• Username <strong>tidak boleh sama</strong> antar Petugas, Bendahara, dan Paramedis</li>
                                            <li>• Minimal 3 karakter, maksimal 50 karakter</li>
                                            <li>• Boleh menggunakan huruf, angka, spasi, titik, dan koma</li>
                                        </ul>
                                        
                                        <h4 class="font-semibold mb-2 text-blue-800 dark:text-blue-200">🆔 Aturan NIP:</h4>
                                        <ul class="space-y-1 mb-3">
                                            <li>• <strong>NIP WAJIB</strong> untuk role: Petugas, Bendahara, Paramedis</li>
                                            <li>• <strong>NIP OPSIONAL</strong> untuk role: Dokter, Pegawai, Non-Paramedis</li>
                                            <li>• Jika diisi, NIP harus <strong>unik</strong> dalam seluruh sistem</li>
                                            <li>• NIP boleh dikosongkan untuk Dokter dan Pegawai</li>
                                            <li>• Sistem akan mencegah duplikasi NIP secara otomatis</li>
                                        </ul>
                                        
                                        <h4 class="font-semibold mb-2 text-blue-800 dark:text-blue-200">🔒 Validasi Anti-Duplikasi:</h4>
                                        <ul class="space-y-1">
                                            <li>• Sistem akan <strong>mencegah akun duplikat</strong> berdasarkan nama dan email</li>
                                            <li>• Peringatan akan muncul jika terdeteksi kemungkinan duplikasi</li>
                                            <li>• Admin dapat melihat semua akun yang dibuat</li>
                                            <li>• Password default dapat direset oleh admin</li>
                                        </ul>
                                    </div>
                                ');
                            })
                            ->columnSpan('full'),
                    ])
                    ->collapsible()
                    ->collapsed(true),
                    
                // Hidden field to store source information
                Forms\Components\Hidden::make('source')
                    ->default($source),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->placeholder('(belum diset)'),
                Tables\Columns\TextColumn::make('role.display_name')
                    ->label('Role')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_telepon')
                    ->label('No. Telepon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal_bergabung')
                    ->label('Tanggal Bergabung')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Role')
                    ->relationship('role', 'display_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('user_management_roles')
                    ->label('Role Management (Petugas/Bendahara/Paramedis)')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereHas('role', fn ($q) => 
                            $q->whereIn('name', ['petugas', 'bendahara', 'paramedis'])
                        )
                    )
                    ->toggle(),
                Tables\Filters\Filter::make('is_active')
                    ->label('Status Aktif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->toggle(),
                Tables\Filters\Filter::make('has_username')
                    ->label('Memiliki Username')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('username'))
                    ->toggle(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat User')
                        ->icon('heroicon-o-eye')
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->label('Edit User')
                        ->icon('heroicon-o-pencil')
                        ->color('warning'),
                    Tables\Actions\Action::make('reset_password')
                        ->label('Reset Password')
                        ->icon('heroicon-o-key')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Password User')
                        ->modalDescription('Apakah Anda yakin ingin mereset password user ini? Password akan direset menjadi "password".')
                        ->action(function ($record) {
                            $record->update([
                                'password' => bcrypt('password'),
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Password berhasil direset')
                                ->body('Password user telah direset menjadi "password"')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->is_active ? 'Nonaktifkan User' : 'Aktifkan User')
                        ->modalDescription(fn ($record) => $record->is_active ? 'Apakah Anda yakin ingin menonaktifkan user ini?' : 'Apakah Anda yakin ingin mengaktifkan user ini?')
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Status berhasil diubah')
                                ->body($record->is_active ? 'User telah diaktifkan' : 'User telah dinonaktifkan')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus User')
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ])
                ->label('Kelola Akun')
                ->icon('heroicon-m-cog-6-tooth')
                ->size('sm')
                ->color('primary')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
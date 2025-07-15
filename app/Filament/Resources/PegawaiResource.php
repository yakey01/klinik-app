<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PegawaiResource\Pages;
use App\Models\Pegawai;
use App\Models\EmployeeCard;
use App\Models\User;
use App\Models\Role;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PegawaiResource extends Resource
{
    protected static ?string $model = Pegawai::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Manajemen Pegawai';

    protected static ?string $modelLabel = 'Pegawai';

    protected static ?string $pluralModelLabel = 'Pegawai';

    protected static ?string $navigationGroup = 'SDM';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pegawai')
                    ->schema([
                        Forms\Components\TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('nik')
                            ->label('NIK Pegawai')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Masukkan NIK pegawai')
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->maxDate(now()->subYears(17))
                            ->minDate(now()->subYears(80)) 
                            ->helperText('Usia minimal 17 tahun')
                            ->columnSpan(1),

                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Informasi Pekerjaan')
                    ->schema([
                        Forms\Components\TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->required()
                            ->placeholder('e.g. Perawat, Kasir, IT Support')
                            ->columnSpan(1),

                        Forms\Components\Select::make('jenis_pegawai')
                            ->label('Jenis Pegawai')
                            ->options([
                                'Paramedis' => 'Paramedis',
                                'Non-Paramedis' => 'Non-Paramedis',
                            ])
                            ->required()
                            ->default('Non-Paramedis')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('aktif')
                            ->label('Status Aktif')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('🔐 Manajemen Akun Login')
                    ->description('Pengaturan akun login pegawai (khusus admin)')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('username')
                                    ->label('Username Login')
                                    ->unique(table: 'pegawais', column: 'username', ignoreRecord: true)
                                    ->nullable()
                                    ->placeholder('Auto-generate jika kosong')
                                    ->helperText('Username untuk login (huruf, angka, spasi, titik, koma diizinkan)')
                                    ->rules(['nullable', 'regex:/^[a-zA-Z0-9\s.,-]+$/'])
                                    ->minLength(3)
                                    ->maxLength(50)
                                    ->suffixIcon('heroicon-m-user')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state) {
                                        \Log::info('PegawaiResource: Username field updated', [
                                            'new_value' => $state,
                                            'length' => strlen($state ?? ''),
                                            'validation_result' => preg_match('/^[a-zA-Z0-9\s.,-]+$/', $state ?? '') ? 'VALID' : 'INVALID'
                                        ]);
                                    })
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('password')
                                    ->label('Password Baru')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->placeholder(fn (string $operation): string => 
                                        $operation === 'create' ? 'Auto-generate jika kosong' : 'Kosongkan jika tidak ingin mengubah password')
                                    ->helperText('Minimal 6 karakter')
                                    ->minLength(6)
                                    ->maxLength(50)
                                    ->suffixIcon('heroicon-m-key')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Konfirmasi Password')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(false)
                                    ->placeholder('Ketik ulang password baru')
                                    ->helperText('Harus sama dengan password baru')
                                    ->same('password')
                                    ->requiredWith('password')
                                    ->suffixIcon('heroicon-m-check-circle')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('status_akun')
                                    ->label('Status Akun Login')
                                    ->options([
                                        'Aktif' => '✅ Aktif - Dapat Login',
                                        'Suspend' => '❌ Suspend - Tidak Dapat Login',
                                    ])
                                    ->default('Aktif')
                                    ->helperText('Status akun login pegawai')
                                    ->suffixIcon('heroicon-m-shield-check')
                                    ->columnSpan(1),
                            ]),

                        Forms\Components\Placeholder::make('password_info')
                            ->label('ℹ️ Informasi Password')
                            ->content(function () {
                                return new \Illuminate\Support\HtmlString('
                                    <div class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <ul class="space-y-1">
                                            <li>• Password minimal 6 karakter, maksimal 50 karakter</li>
                                            <li>• Gunakan kombinasi huruf, angka, dan simbol untuk keamanan</li>
                                            <li>• Konfirmasi password harus sama dengan password baru</li>
                                            <li>• Klik ikon mata untuk melihat/menyembunyikan password</li>
                                            <li>• Jika kosong, sistem akan auto-generate password</li>
                                        </ul>
                                    </div>
                                ');
                            })
                            ->columnSpan('full'),
                    ])
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 3,
                '2xl' => 3,
            ])
            ->paginated([12, 24, 48, 96, 'all'])
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('📋 Tampilan Tabel')
            )
            ->columns([
                Tables\Columns\Layout\View::make('filament.components.pegawai-card-simple')
                    ->components([
                        // Hidden columns for search functionality
                        Tables\Columns\TextColumn::make('nama_lengkap')
                            ->searchable()
                            ->toggleable(isToggledHiddenByDefault: true),
                        Tables\Columns\TextColumn::make('nik')
                            ->searchable()
                            ->toggleable(isToggledHiddenByDefault: true),
                        Tables\Columns\TextColumn::make('jabatan')
                            ->searchable()
                            ->toggleable(isToggledHiddenByDefault: true),
                        Tables\Columns\TextColumn::make('jenis_pegawai')
                            ->toggleable(isToggledHiddenByDefault: true),
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_pegawai')
                    ->label('Jenis Pegawai')
                    ->options([
                        'Paramedis' => 'Paramedis',
                        'Non-Paramedis' => 'Non-Paramedis',
                    ])
                    ->placeholder('Semua Jenis'),

                Tables\Filters\SelectFilter::make('aktif')
                    ->label('Status')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Tidak Aktif',
                    ])
                    ->placeholder('Semua Status'),

                Tables\Filters\SelectFilter::make('status_akun')
                    ->label('Status Login')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Suspend' => 'Suspend',
                    ])
                    ->placeholder('Semua Status Login')
                    ->visible(fn () => auth()->user()?->hasRole('admin')),

                Tables\Filters\TernaryFilter::make('has_login_account')
                    ->label('Akun Login')
                    ->placeholder('Semua')
                    ->trueLabel('Punya Akun Login')
                    ->falseLabel('Belum Punya Akun Login')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('username')->whereNotNull('password'),
                        false: fn ($query) => $query->whereNull('username')->orWhereNull('password'),
                    )
                    ->visible(fn () => auth()->user()?->hasRole('admin')),

                Tables\Filters\TernaryFilter::make('has_user_account')
                    ->label('Akun User')
                    ->placeholder('Semua')
                    ->trueLabel('Punya Akun User')
                    ->falseLabel('Belum Punya Akun User')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('user_id'),
                        false: fn ($query) => $query->whereNull('user_id'),
                    ),

                Tables\Filters\Filter::make('jabatan')
                    ->form([
                        Forms\Components\TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->placeholder('Cari berdasarkan jabatan...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['jabatan'],
                                fn (Builder $query, $jabatan): Builder => $query->where('jabatan', 'like', "%{$jabatan}%"),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Basic Actions
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Detail')
                        ->icon('heroicon-m-eye')
                        ->modalHeading(fn ($record) => 'Detail Pegawai: '.$record->nama_lengkap)
                        ->modalContent(fn ($record) => view('filament.pages.pegawai-detail', ['record' => $record])),
                    Tables\Actions\EditAction::make()
                        ->label('Edit Data')
                        ->icon('heroicon-m-pencil-square'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Hapus')
                        ->icon('heroicon-m-trash'),
                    
                    // Account Actions (Admin only)
                    Tables\Actions\Action::make('create_account')
                        ->label('Buat Akun Login')
                        ->icon('heroicon-m-user-plus')
                        ->color('success')
                        ->visible(fn ($record) => !$record->has_login_account && auth()->user()?->hasRole('admin'))
                        ->action(function ($record) {
                            $result = $record->createLoginAccount();
                            
                            if ($result['success']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Akun Login Berhasil Dibuat')
                                    ->body("Username: {$result['username']}<br>Password: {$result['password']}")
                                    ->success()
                                    ->persistent()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Membuat Akun')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Buat Akun Login Pegawai')
                        ->modalDescription('Akun login akan dibuat secara otomatis dengan username dan password yang di-generate sistem.')
                        ->modalSubmitActionLabel('Buat Akun'),

                    Tables\Actions\Action::make('reset_password')
                        ->label('Reset Password')
                        ->icon('heroicon-m-key')
                        ->color('warning')
                        ->visible(fn ($record) => $record->has_login_account && auth()->user()?->hasRole('admin'))
                        ->action(function ($record) {
                            $result = $record->resetPassword();
                            
                            if ($result['success']) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Password Berhasil Direset')
                                    ->body("Password baru: {$result['password']}")
                                    ->success()
                                    ->persistent()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Reset Password')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Reset Password')
                        ->modalDescription('Password akan direset dan password baru akan di-generate secara otomatis.')
                        ->modalSubmitActionLabel('Reset Password'),

                    // User Management Actions
                    Action::make('create_user_account')
                        ->label('Buat Akun User')
                        ->icon('heroicon-m-identification')
                        ->color('info')
                        ->visible(fn ($record) => true) // Always visible now - can create multiple accounts
                        ->form([
                            Forms\Components\Select::make('jenis_pegawai_for_role')
                                ->label('Menentukan role pengguna sebagai:')
                                ->options(function ($record) {
                                    // Get existing roles for this pegawai
                                    $existingRoles = $record->users()->with('role')->get()->pluck('role.name')->toArray();
                                    
                                    // All available roles
                                    $allRoles = [
                                        'Paramedis' => 'Paramedis (role: paramedis)',
                                        'Petugas' => 'Petugas (role: petugas)',
                                        'Bendahara' => 'Bendahara (role: bendahara)',
                                        'Pegawai' => 'Pegawai (role: pegawai)',
                                    ];
                                    
                                    // Filter out existing roles
                                    $availableRoles = [];
                                    foreach ($allRoles as $key => $label) {
                                        $roleName = strtolower($key);
                                        if (!in_array($roleName, $existingRoles)) {
                                            $availableRoles[$key] = $label;
                                        }
                                    }
                                    
                                    return $availableRoles;
                                })
                                ->default(fn ($record) => $record->jenis_pegawai === 'Paramedis' ? 'Paramedis' : 'Petugas')
                                ->required()
                                ->live()
                                ->helperText(function ($record) {
                                    $existingRoles = $record->users()->with('role')->get()->pluck('role.display_name')->toArray();
                                    if (count($existingRoles) > 0) {
                                        return 'Role yang sudah ada: ' . implode(', ', $existingRoles) . '. Pilih role baru yang belum ada.';
                                    }
                                    return 'Admin bisa membuat satu pegawai memiliki multiple role sebagai Bendahara, Pegawai atau Petugas';
                                }),
                                
                            Forms\Components\TextInput::make('custom_username')
                                ->label('Username Login (Opsional)')
                                ->placeholder('Biarkan kosong untuk auto-generate berdasarkan nama')
                                ->maxLength(50)
                                ->minLength(3)
                                ->regex('/^[a-zA-Z0-9._-]+$/')
                                ->helperText('Username harus unik per role. Format: huruf, angka, titik, underscore, dash')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state, $record) {
                                    if (empty($state)) return;
                                    
                                    // Get selected role
                                    $selectedRole = $get('jenis_pegawai_for_role');
                                    if (!$selectedRole) return;
                                    
                                    $roleName = match($selectedRole) {
                                        'Paramedis' => 'paramedis',
                                        'Petugas' => 'petugas',
                                        'Bendahara' => 'bendahara',
                                        'Pegawai' => 'pegawai',
                                        default => 'petugas'
                                    };
                                    
                                    $role = \App\Models\Role::where('name', $roleName)->first();
                                    if (!$role) return;
                                    
                                    // Check if username exists for this role or other critical roles
                                    $existingUser = \App\Models\User::where('username', $state)
                                        ->when($record?->user_id, function ($query) use ($record) {
                                            return $query->where('id', '!=', $record->user_id);
                                        })
                                        ->first();
                                        
                                    if ($existingUser) {
                                        $set('custom_username', '');
                                        \Filament\Notifications\Notification::make()
                                            ->title('Username Sudah Digunakan')
                                            ->body("Username '{$state}' sudah digunakan oleh {$existingUser->role->display_name}. Username harus unik untuk setiap role.")
                                            ->danger()
                                            ->send();
                                    }
                                }),
                                
                            Forms\Components\TextInput::make('custom_email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Masukkan email pegawai')
                                ->helperText('Email harus unik dan valid - setiap pegawai hanya boleh punya 1 email')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state, $record) {
                                    if (empty($state)) return;
                                    
                                    $existingUser = \App\Models\User::where('email', $state)
                                        ->when($record?->user_id, function ($query) use ($record) {
                                            return $query->where('id', '!=', $record->user_id);
                                        })
                                        ->first();
                                        
                                    if ($existingUser) {
                                        $set('custom_email', '');
                                        \Filament\Notifications\Notification::make()
                                            ->title('Email Sudah Digunakan')
                                            ->body("Email '{$state}' sudah digunakan oleh {$existingUser->name}.")
                                            ->danger()
                                            ->send();
                                    }
                                }),
                                
                            Forms\Components\TextInput::make('custom_password')
                                ->label('Password Kustom (Opsional)')
                                ->password()
                                ->revealable()
                                ->placeholder('Biarkan kosong untuk auto-generate')
                                ->minLength(6)
                                ->maxLength(50)
                                ->helperText('Minimal 6 karakter. Biarkan kosong untuk password otomatis 8 karakter'),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                // Map selected role to actual role name
                                $roleName = match($data['jenis_pegawai_for_role']) {
                                    'Paramedis' => 'paramedis',
                                    'Petugas' => 'petugas',
                                    'Bendahara' => 'bendahara',
                                    'Pegawai' => 'pegawai',
                                    default => 'petugas'
                                };
                                
                                $role = Role::where('name', $roleName)->first();
                                
                                if (!$role) {
                                    Notification::make()
                                        ->title('Gagal Membuat Akun User')
                                        ->body("Role '{$roleName}' tidak ditemukan dalam sistem.")
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Handle username - use custom or auto-generate
                                if (!empty($data['custom_username'])) {
                                    $username = $data['custom_username'];
                                    
                                    // Validate uniqueness with role-specific check
                                    $existingUser = User::where('username', $username)->first();
                                    if ($existingUser) {
                                        Notification::make()
                                            ->title('Gagal Membuat Akun User')
                                            ->body("Username '{$username}' sudah digunakan oleh {$existingUser->role->display_name}. Silakan pilih username lain.")
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                } else {
                                    // Auto-generate username with role prefix for uniqueness
                                    $rolePrefix = match($roleName) {
                                        'paramedis' => 'pm',
                                        'petugas' => 'pt',
                                        'bendahara' => 'bd',
                                        'pegawai' => 'pg',
                                        default => 'usr'
                                    };
                                    
                                    $baseUsername = strtolower(str_replace([' ', '.', ','], '', $record->nama_lengkap));
                                    $baseUsername = Str::ascii($baseUsername);
                                    $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername);
                                    $baseUsername = substr($baseUsername, 0, 15);
                                    
                                    $username = $rolePrefix . '_' . $baseUsername;
                                    $counter = 1;
                                    
                                    while (User::where('username', $username)->exists()) {
                                        $username = $rolePrefix . '_' . $baseUsername . $counter;
                                        $counter++;
                                    }
                                }

                                // Handle password - use custom or auto-generate
                                $password = !empty($data['custom_password']) ? $data['custom_password'] : Str::random(8);

                                // Use the provided email (required field)
                                $email = $data['custom_email'];
                                
                                // Double check email uniqueness
                                $existingEmailUser = User::where('email', $email)->first();
                                if ($existingEmailUser) {
                                    Notification::make()
                                        ->title('Gagal Membuat Akun User')
                                        ->body("Email '{$email}' sudah digunakan oleh {$existingEmailUser->name}. Gunakan email yang berbeda.")
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                $user = User::create([
                                    'role_id' => $role->id,
                                    'pegawai_id' => $record->id, // New: link to pegawai
                                    'name' => $record->nama_lengkap,
                                    'username' => $username,
                                    'email' => $email,
                                    'password' => Hash::make($password),
                                    'nip' => $record->nik,
                                    'tanggal_bergabung' => now()->toDateString(),
                                    'is_active' => $record->aktif,
                                ]);

                                // Legacy: update user_id if this is the first user account for pegawai
                                if (!$record->user_id) {
                                    $record->update(['user_id' => $user->id]);
                                }

                                Notification::make()
                                    ->title('Akun User Berhasil Dibuat')
                                    ->body("Username: {$username}<br>Email: {$email}<br>Password: {$password}<br>Role: {$role->display_name}")
                                    ->success()
                                    ->persistent()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Gagal Membuat Akun User')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Buat Akun User Pegawai')
                        ->modalDescription('Akun User akan dibuat dan dihubungkan dengan record pegawai ini.')
                        ->modalSubmitActionLabel('Buat Akun User'),

                    // Advanced User Management
                    Action::make('manage_user_account')
                        ->label('Kelola Akun User')
                        ->icon('heroicon-m-cog-6-tooth')
                        ->color('warning')
                        ->visible(fn ($record) => $record->users()->count() > 0)
                        ->form([
                            Forms\Components\Section::make('Pilih User Account')
                                ->schema([
                                    Forms\Components\Select::make('user_account_id')
                                        ->label('Pilih User Account yang akan dikelola')
                                        ->options(function ($record) {
                                            return $record->users()->with('role')->get()->mapWithKeys(function ($user) {
                                                return [$user->id => "{$user->username} ({$user->role->display_name})"];
                                            });
                                        })
                                        ->required()
                                        ->live()
                                        ->helperText('Pilih user account yang ingin Anda kelola'),
                                ]),
                                
                            Forms\Components\Section::make('Informasi User Saat Ini')
                                ->schema([
                                    Forms\Components\Placeholder::make('current_user_info')
                                        ->label('Detail User')
                                        ->content(function (Forms\Get $get, $record) {
                                            $userId = $get('user_account_id');
                                            if (!$userId) return 'Pilih user account terlebih dahulu';
                                            
                                            $user = \App\Models\User::with('role')->find($userId);
                                            if (!$user) return 'User tidak ditemukan';
                                            
                                            return new \Illuminate\Support\HtmlString("
                                                <div class='space-y-2'>
                                                    <div><strong>Nama:</strong> {$user->name}</div>
                                                    <div><strong>Username:</strong> {$user->username}</div>
                                                    <div><strong>Email:</strong> {$user->email}</div>
                                                    <div><strong>Role:</strong> <span class='px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm'>{$user->role->display_name}</span></div>
                                                    <div><strong>Status:</strong> <span class='px-2 py-1 " . ($user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . " rounded text-sm'>" . ($user->is_active ? 'Aktif' : 'Nonaktif') . "</span></div>
                                                    <div><strong>Dibuat:</strong> {$user->created_at->format('d/m/Y H:i')}</div>
                                                </div>
                                            ");
                                        }),
                                ]),
                                
                            Forms\Components\Section::make('Ubah Role User')
                                ->schema([
                                    Forms\Components\Select::make('new_role')
                                        ->label('Role Baru')
                                        ->options([
                                            'paramedis' => 'Paramedis',
                                            'petugas' => 'Petugas', 
                                            'bendahara' => 'Bendahara',
                                            'pegawai' => 'Pegawai',
                                        ])
                                        ->default(function ($record) {
                                            return $record->user?->role?->name;
                                        })
                                        ->helperText('Mengubah role akan mempengaruhi akses user dalam sistem'),
                                ]),
                                
                            Forms\Components\Section::make('Ubah Username & Email')
                                ->schema([
                                    Forms\Components\TextInput::make('new_username')
                                        ->label('Username Baru')
                                        ->placeholder('Biarkan kosong jika tidak ingin mengubah')
                                        ->maxLength(50)
                                        ->minLength(3)
                                        ->regex('/^[a-zA-Z0-9._-]+$/')
                                        ->helperText('Username harus unik. Format: huruf, angka, titik, underscore, dash')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state, $record) {
                                            if (empty($state)) return;
                                            
                                            $existingUser = \App\Models\User::where('username', $state)
                                                ->where('id', '!=', $record->user_id)
                                                ->first();
                                                
                                            if ($existingUser) {
                                                $set('new_username', '');
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Username Sudah Digunakan')
                                                    ->body("Username '{$state}' sudah digunakan oleh {$existingUser->role->display_name}.")
                                                    ->danger()
                                                    ->send();
                                            }
                                        }),
                                        
                                    Forms\Components\TextInput::make('new_email')
                                        ->label('Email Baru')
                                        ->email()
                                        ->placeholder('Biarkan kosong jika tidak ingin mengubah')
                                        ->maxLength(255)
                                        ->helperText('Email harus unik - setiap pegawai hanya boleh punya 1 email')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state, $record) {
                                            if (empty($state)) return;
                                            
                                            $existingUser = \App\Models\User::where('email', $state)
                                                ->where('id', '!=', $record->user_id)
                                                ->first();
                                                
                                            if ($existingUser) {
                                                $set('new_email', '');
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Email Sudah Digunakan')
                                                    ->body("Email '{$state}' sudah digunakan oleh {$existingUser->name}.")
                                                    ->danger()
                                                    ->send();
                                            }
                                        }),
                                ])->columns(2),
                                
                            Forms\Components\Section::make('Reset Password')
                                ->schema([
                                    Forms\Components\TextInput::make('new_password')
                                        ->label('Password Baru')
                                        ->password()
                                        ->revealable()
                                        ->placeholder('Biarkan kosong jika tidak ingin mengubah')
                                        ->minLength(6)
                                        ->maxLength(50)
                                        ->helperText('Minimal 6 karakter. Kosongkan jika tidak ingin mengubah password'),
                                        
                                    Forms\Components\Toggle::make('generate_random_password')
                                        ->label('Generate Password Random')
                                        ->helperText('Jika diaktifkan, akan generate password 8 karakter random'),
                                ]),
                                
                            Forms\Components\Section::make('Status Akun')
                                ->schema([
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Status Aktif')
                                        ->default(function ($record) {
                                            return $record->user?->is_active ?? true;
                                        })
                                        ->helperText('Nonaktifkan untuk melarang user login'),
                                ]),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                $user = $record->user;
                                if (!$user) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('User tidak ditemukan')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                $updates = [];
                                $changesMessage = [];
                                
                                // Update role if changed
                                if (!empty($data['new_role']) && $data['new_role'] !== $user->role->name) {
                                    $newRole = \App\Models\Role::where('name', $data['new_role'])->first();
                                    if ($newRole) {
                                        $updates['role_id'] = $newRole->id;
                                        $changesMessage[] = "Role diubah dari '{$user->role->display_name}' ke '{$newRole->display_name}'";
                                    }
                                }
                                
                                // Update username if provided
                                if (!empty($data['new_username']) && $data['new_username'] !== $user->username) {
                                    // Double check uniqueness
                                    $existingUser = \App\Models\User::where('username', $data['new_username'])
                                        ->where('id', '!=', $user->id)
                                        ->first();
                                        
                                    if ($existingUser) {
                                        Notification::make()
                                            ->title('Username Sudah Digunakan')
                                            ->body("Username '{$data['new_username']}' sudah digunakan oleh {$existingUser->role->display_name}")
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                    
                                    $oldUsername = $user->username;
                                    $updates['username'] = $data['new_username'];
                                    $changesMessage[] = "Username diubah dari '{$oldUsername}' ke '{$data['new_username']}'";
                                }
                                
                                // Update email if provided
                                if (!empty($data['new_email']) && $data['new_email'] !== $user->email) {
                                    // Double check uniqueness
                                    $existingEmailUser = \App\Models\User::where('email', $data['new_email'])
                                        ->where('id', '!=', $user->id)
                                        ->first();
                                        
                                    if ($existingEmailUser) {
                                        Notification::make()
                                            ->title('Email Sudah Digunakan')
                                            ->body("Email '{$data['new_email']}' sudah digunakan oleh {$existingEmailUser->name}")
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                    
                                    $oldEmail = $user->email;
                                    $updates['email'] = $data['new_email'];
                                    $changesMessage[] = "Email diubah dari '{$oldEmail}' ke '{$data['new_email']}'";
                                }
                                
                                // Update password
                                $newPassword = null;
                                if (!empty($data['generate_random_password']) && $data['generate_random_password']) {
                                    $newPassword = \Illuminate\Support\Str::random(8);
                                    $updates['password'] = \Illuminate\Support\Facades\Hash::make($newPassword);
                                    $changesMessage[] = "Password di-reset (random)";
                                } elseif (!empty($data['new_password'])) {
                                    $newPassword = $data['new_password'];
                                    $updates['password'] = \Illuminate\Support\Facades\Hash::make($newPassword);
                                    $changesMessage[] = "Password diubah (custom)";
                                }
                                
                                // Update status
                                if (isset($data['is_active']) && $data['is_active'] != $user->is_active) {
                                    $updates['is_active'] = $data['is_active'];
                                    $changesMessage[] = $data['is_active'] ? "Status diaktifkan" : "Status dinonaktifkan";
                                }
                                
                                // Apply updates
                                if (!empty($updates)) {
                                    $user->update($updates);
                                    
                                    $notificationBody = "Perubahan berhasil:\n• " . implode("\n• ", $changesMessage);
                                    if ($newPassword) {
                                        $notificationBody .= "\n\nPassword baru: {$newPassword}";
                                    }
                                    
                                    Notification::make()
                                        ->title('User Berhasil Diperbarui')
                                        ->body($notificationBody)
                                        ->success()
                                        ->persistent()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Tidak Ada Perubahan')
                                        ->body('Tidak ada data yang diubah')
                                        ->warning()
                                        ->send();
                                }
                                
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Gagal Memperbarui User')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->modalHeading('Kelola Akun User Advanced')
                        ->modalDescription('Ubah role, username, password, dan status user')
                        ->modalSubmitActionLabel('Simpan Perubahan')
                        ->modalWidth('4xl'),

                    // Card Actions
                    Action::make('create_card')
                        ->label('Buat Kartu ID')
                        ->icon('heroicon-o-identification')
                        ->color('primary')
                        ->visible(fn ($record) => !EmployeeCard::where('pegawai_id', $record->id)->exists())
                        ->action(function ($record) {
                            $existingCard = EmployeeCard::where('pegawai_id', $record->id)->first();
                            
                            if ($existingCard) {
                                Notification::make()
                                    ->title('Kartu sudah ada!')
                                    ->body('Pegawai ini sudah memiliki kartu.')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            
                            $user = $record->user;
                            
                            $card = EmployeeCard::create([
                                'pegawai_id' => $record->id,
                                'user_id' => $user?->id,
                                'card_number' => EmployeeCard::generateCardNumber(),
                                'card_type' => 'standard',
                                'design_template' => 'default',
                                'employee_name' => $record->nama_lengkap,
                                'employee_id' => $record->nik,
                                'position' => $record->jabatan,
                                'department' => $record->jenis_pegawai,
                                'role_name' => $user?->role?->display_name,
                                'join_date' => $user?->tanggal_bergabung,
                                'photo_path' => $record->foto,
                                'issued_date' => now()->toDateString(),
                                'is_active' => true,
                                'created_by' => Auth::id(),
                            ]);
                            
                            $service = app(\App\Services\CardGenerationService::class);
                            $result = $service->generateCard($card);
                            
                            if ($result['success']) {
                                $card->update([
                                    'pdf_path' => $result['pdf_path'],
                                    'generated_at' => now(),
                                ]);
                                
                                Notification::make()
                                    ->title('Kartu berhasil dibuat!')
                                    ->body('Kartu ID untuk ' . $record->nama_lengkap . ' telah dibuat.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Gagal membuat kartu')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    Action::make('view_card')
                        ->label('Lihat Kartu')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(fn ($record) => '/admin/employee-cards/' . EmployeeCard::where('pegawai_id', $record->id)->first()?->id)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => EmployeeCard::where('pegawai_id', $record->id)->exists()),
                ])
                ->label('Kelola')
                ->icon('heroicon-m-cog-6-tooth')
                ->size('sm')
                ->color('primary')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPegawais::route('/'),
            'create' => Pages\CreatePegawai::route('/create'),
            'edit' => Pages\EditPegawai::route('/{record}/edit'),
        ];
    }
}

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
                'xl' => 4,
                '2xl' => 5,
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
                        ->visible(fn ($record) => !$record->user_id)
                        ->form([
                            Forms\Components\Select::make('jenis_pegawai_for_role')
                                ->label('Jenis Pegawai untuk Role')
                                ->options([
                                    'Paramedis' => 'Paramedis (role: paramedis)',
                                    'Non-Paramedis' => 'Non-Paramedis (role: petugas)',
                                ])
                                ->default(fn ($record) => $record->jenis_pegawai)
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                $roleName = $data['jenis_pegawai_for_role'] === 'Paramedis' ? 'paramedis' : 'petugas';
                                
                                $role = Role::where('name', $roleName)->first();
                                
                                if (!$role) {
                                    Notification::make()
                                        ->title('Gagal Membuat Akun User')
                                        ->body("Role '{$roleName}' tidak ditemukan dalam sistem.")
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                $baseUsername = strtolower(str_replace([' ', '.', ','], '', $record->nama_lengkap));
                                $baseUsername = Str::ascii($baseUsername);
                                $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername);
                                $baseUsername = substr($baseUsername, 0, 20);
                                
                                $username = $baseUsername;
                                $counter = 1;
                                
                                while (User::where('username', $username)->exists()) {
                                    $username = $baseUsername . $counter;
                                    $counter++;
                                }

                                $password = Str::random(8);

                                $user = User::create([
                                    'role_id' => $role->id,
                                    'name' => $record->nama_lengkap,
                                    'username' => $username,
                                    'password' => Hash::make($password),
                                    'nip' => $record->nik,
                                    'tanggal_bergabung' => now()->toDateString(),
                                    'is_active' => $record->aktif,
                                ]);

                                $record->update(['user_id' => $user->id]);

                                Notification::make()
                                    ->title('Akun User Berhasil Dibuat')
                                    ->body("Username: {$username}<br>Password: {$password}<br>Role: {$roleName}")
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

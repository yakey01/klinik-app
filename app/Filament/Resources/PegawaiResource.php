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
                            ->native(false)
                            ->maxDate(now()->subYears(17))
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
            ])
            ->toggleColumnsTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('📋 Tampilan Tabel')
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Header: Photo + Name + NIK
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\ImageColumn::make('foto')
                            ->circular()
                            ->size(50)
                            ->defaultImageUrl(fn ($record) => $record->default_avatar),
                        
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('nama_lengkap')
                                ->weight(FontWeight::Bold)
                                ->size('sm')
                                ->limit(20)
                                ->tooltip(fn ($record) => $record->nama_lengkap),
                            Tables\Columns\TextColumn::make('nik')
                                ->color('gray')
                                ->size('xs')
                                ->prefix('NIK: '),
                        ])->space(1),
                    ]),
                    
                    // Body: Job Info
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('jabatan')
                            ->icon('heroicon-m-briefcase')
                            ->color('primary')
                            ->size('xs')
                            ->limit(15)
                            ->tooltip(fn ($record) => $record->jabatan),
                        
                        Tables\Columns\TextColumn::make('jenis_pegawai')
                            ->badge()
                            ->size('xs')
                            ->color(fn ($state) => match ($state) {
                                'Paramedis' => 'info',
                                'Non-Paramedis' => 'success',
                                default => 'gray',
                            }),
                    ])->space(1),
                    
                    // Footer: Status + Card Info
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\IconColumn::make('aktif')
                            ->boolean()
                            ->trueIcon('heroicon-m-check-circle')
                            ->falseIcon('heroicon-m-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger')
                            ->size('sm'),
                        
                        Tables\Columns\TextColumn::make('employee_card_status')
                            ->getStateUsing(function ($record) {
                                $hasCard = \App\Models\EmployeeCard::where('pegawai_id', $record->id)->exists();
                                return $hasCard ? '🆔' : '❌';
                            })
                            ->tooltip(function ($record) {
                                $hasCard = \App\Models\EmployeeCard::where('pegawai_id', $record->id)->exists();
                                return $hasCard ? 'Sudah ada kartu' : 'Belum ada kartu';
                            })
                            ->size('sm'),
                    ]),
                    
                    // User Account Status Row
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('user_account_status')
                            ->getStateUsing(fn ($record) => $record->user_id ? 'Akun User Aktif' : 'Belum Punya Akun User')
                            ->badge()
                            ->color(fn ($record) => $record->user_id ? 'success' : 'warning')
                            ->size('xs'),
                        
                        Tables\Columns\TextColumn::make('user.username')
                            ->icon('heroicon-m-identification')
                            ->color('info')
                            ->size('xs')
                            ->limit(15)
                            ->placeholder('—')
                            ->tooltip(fn ($record) => $record->user?->username ? 'User Username: ' . $record->user->username : 'Belum punya User account'),
                    ]),
                ])->space(2),
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
                            ->required()
                            ->helperText('Pilih jenis pegawai untuk menentukan role User yang akan dibuat'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            // Determine role based on selected employee type
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

                            // Generate username from name
                            $baseUsername = strtolower(str_replace([' ', '.', ','], '', $record->nama_lengkap));
                            $baseUsername = Str::ascii($baseUsername);
                            $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername);
                            $baseUsername = substr($baseUsername, 0, 20);
                            
                            $username = $baseUsername;
                            $counter = 1;
                            
                            // Ensure username uniqueness
                            while (User::where('username', $username)->exists()) {
                                $username = $baseUsername . $counter;
                                $counter++;
                            }

                            // Generate random password
                            $password = Str::random(8);

                            // Create User account
                            $user = User::create([
                                'role_id' => $role->id,
                                'name' => $record->nama_lengkap,
                                'username' => $username,
                                'password' => Hash::make($password),
                                'nip' => $record->nik,
                                'tanggal_bergabung' => now()->toDateString(),
                                'is_active' => $record->aktif,
                            ]);

                            // Link the user to the pegawai record
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
                    ->modalDescription('Akun User akan dibuat dan dihubungkan dengan record pegawai ini. Username dan password akan di-generate otomatis dengan role sesuai jenis pegawai.')
                    ->modalSubmitActionLabel('Buat Akun User'),

                Action::make('link_existing_user')
                    ->label('Link User Existing')
                    ->icon('heroicon-m-link')
                    ->color('warning')
                    ->visible(fn ($record) => !$record->user_id)
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Pilih User')
                            ->options(function () {
                                return User::whereHas('role', function ($query) {
                                    $query->whereIn('name', ['paramedis', 'petugas']);
                                })
                                ->whereDoesntHave('pegawai')
                                ->get()
                                ->mapWithKeys(function ($user) {
                                    $roleName = $user->role?->display_name ?: $user->role?->name;
                                    return [$user->id => "{$user->name} ({$user->username}) - Role: {$roleName}"];
                                });
                            })
                            ->searchable()
                            ->required()
                            ->helperText('Hanya menampilkan User dengan role "paramedis" atau "petugas" yang belum terhubung ke pegawai lain'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $user = User::find($data['user_id']);
                            if (!$user) {
                                throw new \Exception('User tidak ditemukan');
                            }

                            $record->update(['user_id' => $user->id]);

                            Notification::make()
                                ->title('User Berhasil Dihubungkan')
                                ->body("User {$user->name} ({$user->username}) berhasil dihubungkan dengan pegawai {$record->nama_lengkap}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Menghubungkan User')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->modalHeading('Hubungkan User Existing')
                    ->modalDescription('Pilih User account yang sudah ada untuk dihubungkan dengan record pegawai ini.')
                    ->modalSubmitActionLabel('Hubungkan User'),

                Action::make('view_user_account')
                    ->label('Lihat User')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->visible(fn ($record) => $record->user_id)
                    ->action(function ($record) {
                        $user = $record->user;
                        if ($user) {
                            $userRole = optional($user->role)->display_name ?: optional($user->role)->name ?: 'Tidak ada role';
                            $userStatus = $user->is_active ? 'Aktif' : 'Nonaktif';
                            $joinDate = optional($user->tanggal_bergabung)->format('d/m/Y') ?: 'Tidak diketahui';
                            
                            Notification::make()
                                ->title('Informasi Akun User')
                                ->body("
                                    <strong>Nama:</strong> {$user->name}<br>
                                    <strong>Username:</strong> {$user->username}<br>
                                    <strong>Email:</strong> {$user->email}<br>
                                    <strong>Role:</strong> {$userRole}<br>
                                    <strong>Status:</strong> {$userStatus}<br>
                                    <strong>Bergabung:</strong> {$joinDate}
                                ")
                                ->info()
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('unlink_user_account')
                    ->label('Putus Link User')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->user_id)
                    ->action(function ($record) {
                        try {
                            $userName = $record->user?->name;
                            
                            // Only unlink, don't delete the User record
                            $record->update(['user_id' => null]);

                            Notification::make()
                                ->title('Link User Berhasil Diputus')
                                ->body("Pegawai {$record->nama_lengkap} tidak lagi terhubung dengan User {$userName}. User account tetap ada di sistem.")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Memutus Link User')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Putus Link User Account')
                    ->modalDescription('Link antara record pegawai dan User account akan diputus. User account tidak akan dihapus dan tetap ada di sistem.')
                    ->modalSubmitActionLabel('Putus Link'),

                Action::make('create_card')
                    ->label('🆔 Buat Kartu')
                    ->icon('heroicon-o-identification')
                    ->color('primary')
                    ->action(function ($record) {
                        // Check if card already exists
                        $existingCard = EmployeeCard::where('pegawai_id', $record->id)->first();
                        
                        if ($existingCard) {
                            Notification::make()
                                ->title('⚠️ Kartu sudah ada!')
                                ->body('Pegawai ini sudah memiliki kartu. Silakan edit atau hapus kartu yang ada.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Create new card
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
                        
                        // Generate the card
                        $service = app(\App\Services\CardGenerationService::class);
                        $result = $service->generateCard($card);
                        
                        if ($result['success']) {
                            $card->update([
                                'pdf_path' => $result['pdf_path'],
                                'generated_at' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('🎉 Kartu berhasil dibuat!')
                                ->body('Kartu ID untuk ' . $record->nama_lengkap . ' telah dibuat dan digenerate.')
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('Lihat Kartu')
                                        ->url('/admin/employee-cards/' . $card->id)
                                        ->button(),
                                ])
                                ->send();
                        } else {
                            Notification::make()
                                ->title('❌ Gagal membuat kartu')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => !EmployeeCard::where('pegawai_id', $record->id)->exists()),
                
                Action::make('view_card')
                    ->label('👁️ Lihat Kartu')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => '/admin/employee-cards/' . EmployeeCard::where('pegawai_id', $record->id)->first()?->id)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => EmployeeCard::where('pegawai_id', $record->id)->exists()),
                
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->modalHeading(fn ($record) => 'Detail Pegawai: '.$record->nama_lengkap)
                    ->modalContent(fn ($record) => view('filament.pages.pegawai-detail', ['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
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

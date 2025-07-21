<?php

namespace App\Filament\Resources\PegawaiResource\Pages;

use App\Filament\Resources\PegawaiResource;
use App\Models\Pegawai;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\IconSize;
use Illuminate\Support\Facades\Hash;
use Filament\Actions;

class EditPegawaiWizard extends EditRecord
{
    protected static string $resource = PegawaiResource::class;
    
    protected static ?string $title = 'Edit Data Pegawai';
    
    // Use a simple step-by-step form instead of Livewire wizard
    protected ?string $heading = 'Edit Data Pegawai';
    protected ?string $subheading = 'Perbarui informasi pegawai dengan mudah';
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Step 1: Basic Information
                Forms\Components\Section::make('👤 Informasi Dasar')
                    ->description('Data pribadi dan kontak pegawai')
                    ->icon('heroicon-m-user')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('nama_lengkap')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Masukkan nama lengkap')
                                    ->prefixIcon('heroicon-m-user')
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('nik')
                                    ->label('NIK Pegawai')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Masukkan NIK pegawai')
                                    ->prefixIcon('heroicon-m-identification')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email Pegawai')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Masukkan email pegawai')
                                    ->prefixIcon('heroicon-m-envelope')
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
                            ]),
                    ]),

                // Step 2: Job Information
                Forms\Components\Section::make('💼 Informasi Pekerjaan')
                    ->description('Data jabatan dan status pegawai')
                    ->icon('heroicon-m-briefcase')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('jabatan')
                                    ->label('Jabatan')
                                    ->required()
                                    ->placeholder('e.g. Perawat, Kasir, IT Support')
                                    ->prefixIcon('heroicon-m-briefcase')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('jenis_pegawai')
                                    ->label('Jenis Pegawai')
                                    ->options([
                                        'Paramedis' => '👩‍⚕️ Paramedis',
                                        'Non-Paramedis' => '👨‍💼 Non-Paramedis',
                                    ])
                                    ->required()
                                    ->default('Non-Paramedis')
                                    ->columnSpan(1),

                                Forms\Components\Toggle::make('aktif')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->helperText('Nonaktifkan untuk suspend pegawai')
                                    ->columnSpan(1),
                            ]),
                    ]),

                // Step 3: Account Management (Admin Only)
                Forms\Components\Section::make('🔐 Manajemen Akun Login')
                    ->description('Pengaturan akun dan akses sistem (khusus admin)')
                    ->icon('heroicon-m-key')
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn() => auth()->user()?->hasRole('admin'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('username')
                                    ->label('Username Login')
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Masukkan username login')
                                    ->prefixIcon('heroicon-m-at-symbol')
                                    ->helperText('Username untuk login ke sistem')
                                    ->columnSpan(1),

                                Forms\Components\Select::make('status_akun')
                                    ->label('Status Akun')
                                    ->options([
                                        'aktif' => '✅ Aktif',
                                        'nonaktif' => '❌ Nonaktif',
                                        'suspended' => '⏸️ Suspended',
                                    ])
                                    ->default('aktif')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('password')
                                    ->label('Password Baru')
                                    ->password()
                                    ->placeholder('Kosongkan jika tidak ingin mengubah password')
                                    ->helperText('Kosongkan untuk mempertahankan password lama')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Konfirmasi Password')
                                    ->password()
                                    ->same('password')
                                    ->placeholder('Konfirmasi password baru')
                                    ->columnSpan(1),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create_user_account')
                ->label('Buat Akun User')
                ->icon('heroicon-m-user-plus')
                ->color('success')
                ->url(fn() => url('/admin/users/create?source=staff_management'))
                ->tooltip('Buat akun login untuk Petugas, Bendahara, atau Pegawai')
                ->openUrlInNewTab(false)
                ->visible(fn() => auth()->user()?->hasPermissionTo('create_user')),
                
            Actions\DeleteAction::make()
                ->label('Hapus Karyawan')
                ->requiresConfirmation(),
        ];
    }
    
    public function getTitle(): string
    {
        return 'Edit Pegawai: ' . $this->record->nama_lengkap;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data pegawai berhasil diperbarui';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove password_confirmation field (it's not a database field)
        unset($data['password_confirmation']);
        
        // Only process auth fields if user is admin
        if (auth()->user()?->hasRole('admin')) {
            // Hash password if provided and not empty
            if (!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
                $data['password_changed_at'] = now();
                $data['password_reset_by'] = auth()->id();
            } else {
                // Remove password field if empty to avoid overwriting existing password
                unset($data['password']);
            }
        } else {
            // Remove auth fields if not admin
            unset($data['username'], $data['password'], $data['status_akun']);
        }
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Refresh the record to get latest data
        $this->record = $this->record->fresh();
        $record = $this->getRecord();
        
        // SYNC username, password, and other data to related User record if exists
        if ($record->user_id && $record->user) {
            $syncData = [
                'username' => $record->username,
                'name' => $record->nama_lengkap,
                'nip' => $record->nik
            ];
            
            // Sync password if pegawai has password
            if (!empty($record->password)) {
                $syncData['password'] = $record->password;
            }
            
            $record->user->update($syncData);
        }
        
        // Force refresh form with new data
        $this->fillForm();
    }
}
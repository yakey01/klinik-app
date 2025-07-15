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
    
    protected static ?string $navigationGroup = 'User Management';
    
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
        return auth()->user()?->hasPermissionTo('delete_user') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasPermissionTo('delete_any_user') ?? false;
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
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('nip')
                            ->label('NIP')
                            ->maxLength(255),
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
                                    ->unique(ignoreRecord: true)
                                    ->nullable()
                                    ->placeholder('Opsional - dapat digunakan sebagai alternatif login')
                                    ->helperText('Username untuk login (huruf, angka, spasi, titik, koma diizinkan)')
                                    ->rules(['regex:/^[a-zA-Z0-9\s.,-]+$/'])
                                    ->minLength(3)
                                    ->suffixIcon('heroicon-m-user')
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
                        
                        // Dynamic role selection based on source
                        Forms\Components\Select::make('role_id')
                            ->label('Role')
                            ->relationship('role', 'display_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->visible(fn () => $source !== 'dokter' && $source !== 'pegawai')
                            ->hint('Pilih role untuk user ini'),
                            
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
                Tables\Filters\Filter::make('is_active')
                    ->label('Status Aktif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
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
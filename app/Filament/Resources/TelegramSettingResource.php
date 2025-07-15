<?php

namespace App\Filament\Resources;

use App\Enums\TelegramNotificationType;
use App\Filament\Resources\TelegramSettingResource\Pages;
use App\Models\TelegramSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TelegramSettingResource extends Resource
{
    protected static ?string $model = TelegramSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Telegram Bot';

    protected static ?string $modelLabel = 'Pengaturan Telegram';

    protected static ?string $pluralModelLabel = 'Pengaturan Telegram';

    protected static ?string $navigationGroup = 'System Administration';

    protected static ?int $navigationSort = 72;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Konfigurasi Role')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('👥 Role')
                            ->options([
                                'admin' => '🔧 Admin',
                                'manajer' => '👔 Manajer',
                                'bendahara' => '💼 Bendahara',
                                'petugas' => '🏥 Petugas',
                                'dokter' => '👨‍⚕️ Dokter',
                                'paramedis' => '👩‍⚕️ Paramedis',
                                'non_paramedis' => '👥 Non Paramedis',
                            ])
                            ->required()
                            ->native(false)
                            ->reactive()
                            ->disabled(function ($record) {
                                // Only disable when editing
                                return $record !== null;
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('notification_types', []);
                                $set('user_id', null);
                                $set('user_name', null);
                                // Set default role_type based on role
                                if (in_array($state, ['dokter', 'paramedis', 'non_paramedis'])) {
                                    $set('role_type', 'general');
                                } else {
                                    $set('role_type', null);
                                }
                            })
                            ->helperText(function ($record) {
                                if ($record) {
                                    return '🔒 Role tidak dapat diubah setelah dibuat.';
                                }

                                return '📝 Pilih role untuk konfigurasi Telegram. Untuk dokter, paramedis, dan non_paramedis, Anda dapat memilih pengguna spesifik.';
                            }),

                        Forms\Components\Select::make('role_type')
                            ->label('📋 Tipe Konfigurasi')
                            ->options([
                                'general' => '🌐 Umum (Semua '.ucfirst('role').')',
                                'specific_user' => '👤 Pengguna Spesifik',
                            ])
                            ->default('general')
                            ->required()
                            ->native(false)
                            ->reactive()
                            ->visible(function (callable $get) {
                                $role = $get('role');

                                return in_array($role, ['dokter', 'paramedis', 'non_paramedis']);
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'general') {
                                    $set('user_id', null);
                                    $set('user_name', null);
                                }
                            })
                            ->rules([
                                function (callable $get) {
                                    return function ($attribute, $value, $fail) use ($get) {
                                        if ($value === 'general') {
                                            $role = $get('role');
                                            $existing = TelegramSetting::where('role', $role)
                                                ->where('role_type', 'general')
                                                ->exists();

                                            if ($existing) {
                                                $fail("Sudah ada konfigurasi umum untuk role {$role}. Gunakan Edit untuk mengubah yang sudah ada.");
                                            }
                                        }
                                    };
                                },
                            ])
                            ->helperText('Pilih apakah konfigurasi untuk semua pengguna dalam role ini atau pengguna spesifik'),

                        Forms\Components\Select::make('user_id')
                            ->label('👤 Pilih Pengguna')
                            ->options(function (callable $get) {
                                $role = $get('role');
                                if (! $role || ! in_array($role, ['dokter', 'paramedis', 'non_paramedis'])) {
                                    return [];
                                }

                                return TelegramSetting::getAvailableUsersForRole($role);
                            })
                            ->searchable()
                            ->required(function (callable $get) {
                                return $get('role_type') === 'specific_user';
                            })
                            ->visible(function (callable $get) {
                                $role = $get('role');
                                $roleType = $get('role_type');

                                return in_array($role, ['dokter', 'paramedis', 'non_paramedis']) && $roleType === 'specific_user';
                            })
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $role = $get('role');
                                    $user = \App\Models\User::find($state);
                                    if ($user) {
                                        $set('user_name', $user->name);
                                    }
                                }
                            })
                            ->validationMessages([
                                'required' => 'Pilih pengguna untuk konfigurasi spesifik.',
                            ])
                            ->rules([
                                function (callable $get) {
                                    return function ($attribute, $value, $fail) use ($get) {
                                        if ($value && $get('role_type') === 'specific_user') {
                                            $role = $get('role');
                                            $existing = TelegramSetting::where('user_id', $value)
                                                ->where('role', $role)
                                                ->where('role_type', 'specific_user')
                                                ->exists();

                                            if ($existing) {
                                                $user = \App\Models\User::find($value);
                                                $userName = $user ? $user->name : 'User';
                                                $fail("Pengguna {$userName} sudah memiliki konfigurasi Telegram untuk role {$role}.");
                                            }
                                        }
                                    };
                                },
                            ])
                            ->helperText('Pilih pengguna spesifik untuk menerima notifikasi Telegram'),

                        Forms\Components\Hidden::make('user_name'),

                        Forms\Components\TextInput::make('chat_id')
                            ->label('📲 Chat ID Telegram')
                            ->required()
                            ->numeric()
                            ->placeholder('Contoh: 123456789')
                            ->helperText('Chat ID grup/user Telegram (hanya angka, max 15 digit)')
                            ->rule('digits_between:1,15')
                            ->unique(table: TelegramSetting::class, ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Chat ID sudah digunakan untuk role lain.',
                                'digits_between' => 'Chat ID harus berupa angka 1-15 digit.',
                                'numeric' => 'Chat ID harus berupa angka.',
                            ]),

                        Forms\Components\CheckboxList::make('notification_types')
                            ->label('📢 Jenis Notifikasi')
                            ->options(function (callable $get) {
                                $role = $get('role');
                                if (! $role) {
                                    return TelegramNotificationType::getAllOptions();
                                }

                                return TelegramSetting::getRoleNotifications($role);
                            })
                            ->columns(1)
                            ->helperText('Pilih jenis notifikasi yang relevan untuk role ini')
                            ->descriptions(function (callable $get) {
                                $role = $get('role');
                                if (! $role) {
                                    return [];
                                }

                                $descriptions = [];
                                foreach (TelegramNotificationType::getForRole($role) as $type) {
                                    $descriptions[$type->value] = $type->description();
                                }

                                return $descriptions;
                            }),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Aktifkan/nonaktifkan notifikasi untuk role ini'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->formatStateUsing(function ($record) {
                        return $record->getDisplayName();
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manajer' => 'warning',
                        'bendahara' => 'success',
                        'petugas' => 'info',
                        'dokter' => 'primary',
                        'paramedis' => 'gray',
                        'non_paramedis' => 'slate',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('role_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'general' => '🌐 Umum',
                        'specific_user' => '👤 Spesifik',
                        default => '-'
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'general' => 'info',
                        'specific_user' => 'warning',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('chat_id')
                    ->label('Chat ID')
                    ->limit(20)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return $column->getState();
                    }),

                Tables\Columns\TextColumn::make('formatted_notification_types')
                    ->label('Jenis Notifikasi')
                    ->state(function ($record) {
                        $formatted = $record->getFormattedNotificationTypes();

                        if (empty($formatted)) {
                            return 'Tidak ada';
                        }

                        return count($formatted).' jenis: '.implode(', ', array_slice($formatted, 0, 2)).
                               (count($formatted) > 2 ? '...' : '');
                    })
                    ->badge()
                    ->color('info')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        $formatted = $record->getFormattedNotificationTypes();
                        if (empty($formatted)) {
                            return 'Tidak ada notifikasi yang dipilih';
                        }

                        return 'Notifikasi aktif: '.implode(', ', $formatted);
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'manajer' => 'Manajer',
                        'bendahara' => 'Bendahara',
                        'petugas' => 'Petugas',
                        'dokter' => 'Dokter',
                        'paramedis' => 'Paramedis',
                        'non_paramedis' => 'Non Paramedis',
                    ]),
                Tables\Filters\SelectFilter::make('role_type')
                    ->label('Tipe Konfigurasi')
                    ->options([
                        'general' => 'Umum',
                        'specific_user' => 'Pengguna Spesifik',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('test_notification')
                    ->label('Test Notifikasi')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->action(function (TelegramSetting $record) {
                        try {
                            // Check if chat_id exists before sending
                            if (! $record->chat_id) {
                                throw new \Exception('Chat ID tidak tersedia untuk role '.$record->role);
                            }

                            $telegramService = app(\App\Services\TelegramService::class);
                            $message = "🧪 *Test Notification*\n\n".
                                      "Role: *{$record->role}*\n".
                                      'Waktu: '.now()->format('d M Y H:i:s')."\n\n".
                                      '✅ Telegram bot berfungsi dengan baik!';

                            $result = $telegramService->sendMessage($record->chat_id, $message);

                            if ($result) {
                                Notification::make()
                                    ->title('Test Berhasil!')
                                    ->body('Notifikasi test berhasil dikirim ke '.$record->role)
                                    ->success()
                                    ->send();
                            } else {
                                throw new \Exception('Gagal mengirim pesan');
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Test Gagal!')
                                ->body('Error: '.$e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTelegramSettings::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}

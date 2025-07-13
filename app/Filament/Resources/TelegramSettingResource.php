<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TelegramSettingResource\Pages;
use App\Models\TelegramSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class TelegramSettingResource extends Resource
{
    protected static ?string $model = TelegramSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Telegram Bot';

    protected static ?string $modelLabel = 'Pengaturan Telegram';

    protected static ?string $pluralModelLabel = 'Pengaturan Telegram';

    protected static ?string $navigationGroup = 'Sistem & Integrasi';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Konfigurasi Role')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                'admin' => 'Admin',
                                'manajer' => 'Manajer', 
                                'bendahara' => 'Bendahara',
                                'petugas' => 'Petugas',
                                'dokter' => 'Dokter',
                                'paramedis' => 'Paramedis',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('chat_id')
                            ->label('Chat ID Telegram')
                            ->required()
                            ->placeholder('Contoh: 123456789')
                            ->helperText('Chat ID grup atau channel Telegram untuk role ini'),

                        Forms\Components\CheckboxList::make('notification_types')
                            ->label('Jenis Notifikasi')
                            ->options([
                                'income_created' => '💰 Pendapatan Baru',
                                'expense_created' => '💸 Pengeluaran Baru', 
                                'patient_created' => '👥 Pasien Baru',
                                'user_created' => '👤 User Baru',
                                'daily_recap' => '📊 Rekap Harian',
                                'weekly_recap' => '📈 Rekap Mingguan',
                            ])
                            ->columns(2)
                            ->helperText('Pilih jenis notifikasi yang akan dikirim ke role ini'),

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
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'manajer' => 'warning',
                        'bendahara' => 'success',
                        'petugas' => 'info',
                        'dokter' => 'primary',
                        'paramedis' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('chat_id')
                    ->label('Chat ID')
                    ->limit(20)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return $column->getState();
                    }),

                Tables\Columns\TextColumn::make('notification_types')
                    ->label('Jenis Notifikasi')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'Tidak ada';
                        $types = is_array($state) ? $state : json_decode($state, true);
                        $types = $types ?? [];
                        return count($types) . ' jenis notifikasi';
                    })
                    ->badge()
                    ->color('info'),

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
                            $telegramService = app(\App\Services\TelegramService::class);
                            $message = "🧪 *Test Notification*\n\n" .
                                      "Role: *{$record->role}*\n" .
                                      "Waktu: " . now()->format('d M Y H:i:s') . "\n\n" .
                                      "✅ Telegram bot berfungsi dengan baik!";
                            
                            $result = $telegramService->sendMessage($record->chat_id, $message);
                            
                            if ($result) {
                                Notification::make()
                                    ->title('Test Berhasil!')
                                    ->body('Notifikasi test berhasil dikirim ke ' . $record->role)
                                    ->success()
                                    ->send();
                            } else {
                                throw new \Exception('Gagal mengirim pesan');
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Test Gagal!')
                                ->body('Error: ' . $e->getMessage())
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
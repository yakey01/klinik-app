<?php

namespace App\Filament\Resources\TelegramSettingResource\Pages;

use App\Filament\Resources\TelegramSettingResource;
use App\Services\TelegramService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms;
use Illuminate\Support\Facades\Http;

class ManageTelegramSettings extends ManageRecords
{
    protected static string $resource = TelegramSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bot_config')
                ->label('⚙️ Konfigurasi Bot')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->modal()
                ->modalHeading('Section 1: Konfigurasi Bot Telegram')
                ->modalDescription('Setup token bot dan admin chat ID')
                ->form([
                    \Filament\Forms\Components\TextInput::make('bot_token')
                        ->label('🔐 Token Bot Telegram')
                        ->placeholder('Contoh: 1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijk')
                        ->helperText('Token bot dari @BotFather. Format: angka:huruf_random')
                        ->password()
                        ->revealable()
                        ->required()
                        ->default(fn() => \App\Models\SystemConfig::where('key', 'TELEGRAM_BOT_TOKEN')->value('value')),
                        
                    \Filament\Forms\Components\TextInput::make('admin_chat_id')
                        ->label('📲 Admin Chat ID')
                        ->placeholder('Contoh: 123456789')
                        ->helperText('Chat ID admin utama untuk fallback notifikasi')
                        ->required()
                        ->default(fn() => \App\Models\SystemConfig::where('key', 'TELEGRAM_ADMIN_CHAT_ID')->value('value')),
                ])
                ->action(function (array $data) {
                    try {
                        // Save bot token
                        \App\Models\SystemConfig::updateOrCreate(
                            ['key' => 'TELEGRAM_BOT_TOKEN'],
                            [
                                'value' => $data['bot_token'],
                                'description' => 'Token bot Telegram dari BotFather',
                                'category' => 'telegram'
                            ]
                        );
                        
                        // Save admin chat ID
                        \App\Models\SystemConfig::updateOrCreate(
                            ['key' => 'TELEGRAM_ADMIN_CHAT_ID'],
                            [
                                'value' => $data['admin_chat_id'],
                                'description' => 'Chat ID admin utama untuk fallback notifikasi',
                                'category' => 'telegram'
                            ]
                        );
                        
                        \Filament\Notifications\Notification::make()
                            ->title('✅ Konfigurasi Bot Berhasil Disimpan!')
                            ->body('Token bot dan Admin Chat ID telah diperbarui.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('❌ Error Menyimpan Konfigurasi')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('test_admin')
                ->label('🧪 Test Admin')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Test Notifikasi ke Admin')
                ->modalDescription('Kirim notifikasi test ke admin chat ID yang dikonfigurasi')
                ->action(function () {
                    try {
                        $adminChatId = \App\Models\SystemConfig::where('key', 'TELEGRAM_ADMIN_CHAT_ID')->value('value');
                        
                        if (!$adminChatId) {
                            throw new \Exception('Admin Chat ID belum dikonfigurasi. Gunakan tombol Konfigurasi Bot terlebih dahulu.');
                        }
                        
                        // Validate chat_id is numeric
                        if (!is_numeric($adminChatId)) {
                            throw new \Exception('Admin Chat ID harus berupa angka.');
                        }
                        
                        $telegramService = app(\App\Services\TelegramService::class);
                        $message = "🧪 *Test Notifikasi Admin*\n\n" .
                                  "Chat ID: *{$adminChatId}*\n" .
                                  "Waktu: " . now()->format('d M Y H:i:s') . "\n\n" .
                                  "✅ Konfigurasi Telegram admin berhasil!";
                        
                        $result = $telegramService->sendMessage((string)$adminChatId, $message);
                        
                        if ($result) {
                            \Filament\Notifications\Notification::make()
                                ->title('✅ Test Berhasil!')
                                ->body('Notifikasi test berhasil dikirim ke admin.')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('Gagal mengirim notifikasi. Periksa Chat ID dan token bot.');
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('❌ Test Gagal!')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('bot_info')
                ->label('Info Bot')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modal()
                ->modalHeading('Informasi Bot Telegram')
                ->modalDescription('Detail dan status bot Telegram Dokterku')
                ->modalContent(function () {
                    try {
                        $telegramService = app(TelegramService::class);
                        $botInfo = $telegramService->getBotInfo();
                        
                        if ($botInfo) {
                            $content = view('filament.telegram.bot-info', compact('botInfo'))->render();
                        } else {
                            $content = '<div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-red-800">❌ Tidak dapat terhubung ke bot Telegram</p>
                                <p class="text-sm text-red-600 mt-1">Periksa token bot di file .env</p>
                            </div>';
                        }
                        
                        return new \Illuminate\Support\HtmlString($content);
                    } catch (\Exception $e) {
                        return new \Illuminate\Support\HtmlString(
                            '<div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                <p class="text-red-800">❌ Error: ' . $e->getMessage() . '</p>
                            </div>'
                        );
                    }
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),

            Actions\Action::make('test_all')
                ->label('Test Semua')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Test Notifikasi ke Semua Role')
                ->modalDescription('Kirim notifikasi test ke semua role yang aktif')
                ->action(function () {
                    $activeSettings = \App\Models\TelegramSetting::where('is_active', true)->get();
                    $telegramService = app(TelegramService::class);
                    
                    $successCount = 0;
                    $failCount = 0;
                    
                    foreach ($activeSettings as $setting) {
                        try {
                            $message = "🧪 *Test Notification - Semua Role*\n\n" .
                                      "Role: *{$setting->role}*\n" .
                                      "Waktu: " . now()->format('d M Y H:i:s') . "\n\n" .
                                      "✅ Bot Telegram Dokterku berfungsi dengan baik!";
                            
                            // Check if chat_id exists before sending
                            if (!$setting->chat_id) {
                                throw new \Exception('Chat ID tidak tersedia untuk role ' . $setting->role);
                            }
                            
                            $result = $telegramService->sendMessage($setting->chat_id, $message);
                            
                            if ($result) {
                                $successCount++;
                            } else {
                                $failCount++;
                            }
                        } catch (\Exception $e) {
                            $failCount++;
                        }
                    }
                    
                    if ($successCount > 0) {
                        Notification::make()
                            ->title('Test Selesai!')
                            ->body("Berhasil: {$successCount}, Gagal: {$failCount}")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Test Gagal!')
                            ->body('Tidak ada notifikasi yang berhasil dikirim')
                            ->danger()
                            ->send();
                    }
                }),

            Actions\CreateAction::make()
                ->label('➕ Tambah Role')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->modalHeading('Tambah Pengaturan Telegram Role')
                ->modalDescription(function () {
                    // Check for roles that need configuration (no chat_id or empty)
                    $rolesNeedingConfig = \App\Models\TelegramSetting::where(function($query) {
                        $query->whereNull('chat_id')->orWhere('chat_id', '');
                    })->pluck('role')->toArray();
                    
                    $allRoles = [
                        'admin' => '🔧 Admin',
                        'manajer' => '👔 Manajer', 
                        'bendahara' => '💼 Bendahara',
                        'petugas' => '🏥 Petugas'
                    ];
                    
                    // Check for roles that don't exist in database at all
                    $existingRoles = \App\Models\TelegramSetting::pluck('role')->toArray();
                    $missingRoles = array_diff(array_keys($allRoles), $existingRoles);
                    
                    $needsConfiguration = array_merge($rolesNeedingConfig, $missingRoles);
                    
                    if (empty($needsConfiguration)) {
                        return '✅ Semua role sudah dikonfigurasi lengkap. Gunakan tombol Edit untuk mengubah pengaturan.';
                    }
                    
                    $roleNames = array_map(fn($role) => $allRoles[$role] ?? $role, $needsConfiguration);
                    return '📝 Role yang perlu dikonfigurasi Chat ID: ' . implode(', ', $roleNames);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TelegramSettingResource\Widgets\TelegramStatsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return 'Pengaturan Telegram Bot';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola notifikasi Telegram untuk setiap role sistem';
    }
}
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
                ->label('Tambah Role')
                ->icon('heroicon-o-plus')
                ->modalHeading('Tambah Pengaturan Telegram Role'),
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
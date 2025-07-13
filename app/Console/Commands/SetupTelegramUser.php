<?php

namespace App\Console\Commands;

use App\Models\TelegramSetting;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Models\SystemConfig;
use Illuminate\Console\Command;

class SetupTelegramUser extends Command
{
    protected $signature = 'telegram:setup {role}';
    protected $description = 'Interactive setup for Telegram notifications by role';

    public function handle()
    {
        $role = $this->argument('role');
        
        $this->info("=== Setup Telegram Notifications for: {$role} ===\n");
        
        // Step 1: Show bot info
        $token = SystemConfig::where('key', 'TELEGRAM_BOT_TOKEN')->value('value');
        if (!$token) {
            $this->error("Telegram bot token not configured!");
            return 1;
        }
        
        config(['telegram.bots.dokterku.token' => $token]);
        
        try {
            $botInfo = Telegram::getMe();
            $botUsername = $botInfo->getUsername();
            
            $this->info("Bot: @{$botUsername}");
            $this->line("Bot ID: {$botInfo->getId()}");
        } catch (\Exception $e) {
            $this->error("Error getting bot info: " . $e->getMessage());
            return 1;
        }
        
        // Step 2: Instructions
        $this->line("\n📋 Instructions:");
        $this->line("1. Open Telegram on your phone/computer");
        $this->line("2. Search for: @{$botUsername}");
        $this->line("3. Start a conversation by sending: /start");
        $this->line("4. Send any message like: 'Hello bot'");
        $this->line("5. Come back here and press ENTER\n");
        
        $this->ask("Press ENTER after you've sent a message to the bot");
        
        // Step 3: Get recent updates
        try {
            $updates = Telegram::getUpdates(['limit' => 10]);
            
            if (empty($updates)) {
                $this->warn("No recent messages found. Make sure you sent a message to the bot.");
                return 1;
            }
            
            $this->info("\nRecent conversations:");
            $choices = [];
            $chatData = [];
            
            foreach ($updates as $i => $update) {
                if ($update->getMessage()) {
                    $chat = $update->getMessage()->getChat();
                    $from = $update->getMessage()->getFrom();
                    $chatId = $chat->getId();
                    $text = $update->getMessage()->getText();
                    
                    $displayName = $from->getFirstName();
                    if ($from->getLastName()) $displayName .= ' ' . $from->getLastName();
                    if ($from->getUsername()) $displayName .= ' (@' . $from->getUsername() . ')';
                    
                    $choice = "{$displayName} - \"{$text}\"";
                    if (!in_array($choice, $choices)) {
                        $choices[] = $choice;
                        $chatData[] = [
                            'chat_id' => $chatId,
                            'name' => $displayName,
                            'message' => $text
                        ];
                    }
                }
            }
            
            if (empty($choices)) {
                $this->warn("No valid conversations found.");
                return 1;
            }
            
            // Step 4: Select the correct user
            $choices[] = "None of the above";
            $selected = $this->choice("\nWhich conversation is for the {$role}?", $choices);
            
            if ($selected === "None of the above") {
                $this->info("Please send a message to the bot and try again.");
                return 0;
            }
            
            // Find selected chat data
            $selectedIndex = array_search($selected, $choices);
            $selectedChat = $chatData[$selectedIndex];
            
            // Step 5: Update database
            $setting = TelegramSetting::updateOrCreate(
                ['role' => $role],
                [
                    'chat_id' => $selectedChat['chat_id'],
                    'is_active' => true,
                    'notification_types' => $this->getDefaultNotificationTypes($role)
                ]
            );
            
            $this->info("\n✅ Telegram setup completed!");
            $this->line("Role: {$role}");
            $this->line("User: {$selectedChat['name']}");
            $this->line("Chat ID: {$selectedChat['chat_id']}");
            
            // Step 6: Send test message
            if ($this->confirm("Send a test message?", true)) {
                $testMessage = "🎉 <b>Setup Berhasil!</b>\n\n";
                $testMessage .= "✅ Role: {$role}\n";
                $testMessage .= "✅ Notifikasi Telegram sudah aktif\n";
                $testMessage .= "📅 " . now()->format('d/m/Y H:i:s') . "\n";
                $testMessage .= "🏥 <i>Dokterku - SAHABAT MENUJU SEHAT</i>";
                
                $response = Telegram::sendMessage([
                    'chat_id' => $selectedChat['chat_id'],
                    'text' => $testMessage,
                    'parse_mode' => 'HTML'
                ]);
                
                if ($response->getMessageId()) {
                    $this->info("🎯 Test message sent successfully!");
                } else {
                    $this->warn("Failed to send test message");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function getDefaultNotificationTypes($role): array
    {
        return match($role) {
            'bendahara' => ['pendapatan', 'pengeluaran', 'pasien', 'validasi_disetujui', 'rekap_harian'],
            'admin' => ['user_baru', 'backup_gagal'],
            'manajer' => ['rekap_mingguan', 'rekap_harian'],
            'petugas' => ['rekap_harian'],
            default => ['rekap_harian']
        };
    }
}
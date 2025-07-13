<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use App\Models\TelegramSetting;
use Illuminate\Console\Command;

class TestTelegramNotification extends Command
{
    protected $signature = 'telegram:test {role} {--message=Test notification}';
    protected $description = 'Test Telegram notification for a specific role';

    public function handle(TelegramService $telegramService)
    {
        $role = $this->argument('role');
        $message = $this->option('message');
        
        $this->info("Testing Telegram notification for role: {$role}");
        
        // Get bot info
        try {
            $botInfo = $telegramService->getBotInfo();
            $this->info("Bot: @{$botInfo['username']} (ID: {$botInfo['id']})");
        } catch (\Exception $e) {
            $this->error("Bot Error: " . $e->getMessage());
            return 1;
        }
        
        // Get role settings
        $setting = TelegramSetting::where('role', $role)->first();
        if (!$setting) {
            $this->error("No Telegram settings found for role: {$role}");
            return 1;
        }
        
        $this->info("Role Settings:");
        $this->line("  Chat ID: {$setting->chat_id}");
        $this->line("  Active: " . ($setting->is_active ? 'Yes' : 'No'));
        $this->line("  Notification Types: " . implode(', ', $setting->notification_types ?? []));
        
        // Test send
        $this->info("\nSending test message...");
        $testMessage = "🧪 <b>Test Notifikasi</b>\n\n";
        $testMessage .= "📱 Role: {$role}\n";
        $testMessage .= "💬 Message: {$message}\n";
        $testMessage .= "📅 " . now()->format('d/m/Y H:i:s') . "\n";
        $testMessage .= "🏥 <i>Dokterku - SAHABAT MENUJU SEHAT</i>";
        
        $result = $telegramService->sendMessage($setting->chat_id, $testMessage);
        
        if ($result) {
            $this->info("✅ Message sent successfully!");
            $this->line("If you don't receive the message, please:");
            $this->line("1. Start a conversation with @{$botInfo['username']} on Telegram");
            $this->line("2. Send any message to the bot (like '/start')");
            $this->line("3. Get your Chat ID and update the Telegram settings");
        } else {
            $this->error("❌ Failed to send message");
            $this->line("Check the logs for more details");
        }
        
        return 0;
    }
}
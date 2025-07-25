<?php

namespace App\Console\Commands;

use Telegram\Bot\Laravel\Facades\Telegram;
use App\Models\SystemConfig;
use Illuminate\Console\Command;

class GetTelegramChatId extends Command
{
    protected $signature = 'telegram:get-chat-ids';
    protected $description = 'Get recent chat IDs from Telegram bot updates';

    public function handle()
    {
        $this->info("Getting recent Telegram updates to find Chat IDs...");
        
        // Get token from database
        $token = SystemConfig::where('key', 'TELEGRAM_BOT_TOKEN')->value('value');
        
        if (!$token) {
            $this->error("Telegram bot token not found in database");
            return 1;
        }
        
        // Set token dynamically
        config(['telegram.bots.dokterku.token' => $token]);
        
        try {
            $updates = Telegram::getUpdates();
            
            if (empty($updates)) {
                $this->warn("No recent updates found.");
                $this->line("To get your Chat ID:");
                $this->line("1. Go to Telegram and search for your bot");
                $this->line("2. Send any message to the bot (like '/start')");
                $this->line("3. Run this command again");
                return 0;
            }
            
            $this->info("Recent Chat IDs found:");
            $this->line("");
            
            $chatIds = [];
            foreach ($updates as $update) {
                if ($update->getMessage()) {
                    $chatId = $update->getMessage()->getChat()->getId();
                    $firstName = $update->getMessage()->getFrom()->getFirstName();
                    $username = $update->getMessage()->getFrom()->getUsername();
                    $text = $update->getMessage()->getText();
                    
                    if (!in_array($chatId, $chatIds)) {
                        $chatIds[] = $chatId;
                        $this->line("Chat ID: <comment>{$chatId}</comment>");
                        $this->line("  User: {$firstName}" . ($username ? " (@{$username})" : ""));
                        $this->line("  Last Message: {$text}");
                        $this->line("");
                    }
                }
            }
            
            $this->info("Use these Chat IDs in your Telegram Settings for the appropriate roles.");
            
        } catch (\Exception $e) {
            $this->error("Error getting updates: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
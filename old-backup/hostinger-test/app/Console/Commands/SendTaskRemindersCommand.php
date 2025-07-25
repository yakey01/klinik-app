<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Services\TelegramService;
use Exception;

class SendTaskRemindersCommand extends Command
{
    protected $signature = 'notifications:send-reminders';
    protected $description = 'Send task reminders to users';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->info('📢 Sending task reminders...');
            
            $telegramService = new TelegramService();
            $notificationService = new NotificationService($telegramService);
            
            $result = $notificationService->sendTaskReminders();
            
            if ($result['success']) {
                $this->info("✅ Successfully sent {$result['reminders_sent']} reminders");
                return self::SUCCESS;
            } else {
                $this->error('❌ Failed to send reminders');
                return self::FAILURE;
            }
            
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
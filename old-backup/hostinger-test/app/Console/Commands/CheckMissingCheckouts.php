<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class CheckMissingCheckouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:check-missing-checkouts {--date= : Check for specific date (Y-m-d format)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for missing check-outs and send reminders';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date');
        
        if ($date) {
            $this->info("Checking for missing check-outs on {$date}...");
        } else {
            $this->info('Checking for missing check-outs from yesterday...');
        }
        
        $reminderCount = $this->notificationService->checkMissingCheckouts();
        
        if ($reminderCount > 0) {
            $this->info("✅ Sent {$reminderCount} missing check-out reminders");
        } else {
            $this->info('✅ No missing check-outs found');
        }
        
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;

class ProcessNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process {--type=all : Type of processing (all, scheduled, cleanup, reminders, late-checkins)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process notifications (scheduled, cleanup, reminders, late check-ins)';

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
        $type = $this->option('type');
        
        $this->info('Processing notifications...');
        
        $results = [];
        
        if ($type === 'all' || $type === 'scheduled') {
            $this->info('Processing scheduled notifications...');
            $processed = $this->notificationService->processScheduledNotifications();
            $results['scheduled'] = $processed;
            $this->info("Processed {$processed} scheduled notifications");
        }
        
        if ($type === 'all' || $type === 'cleanup') {
            $this->info('Cleaning up expired notifications...');
            $cleaned = $this->notificationService->cleanupExpiredNotifications();
            $results['cleanup'] = $cleaned;
            $this->info("Cleaned up {$cleaned} expired notifications");
        }
        
        if ($type === 'all' || $type === 'reminders') {
            $this->info('Scheduling attendance reminders...');
            $scheduled = $this->notificationService->scheduleAttendanceReminders(now()->addDay());
            $results['reminders'] = $scheduled;
            $this->info("Scheduled {$scheduled} attendance reminders for tomorrow");
        }
        
        if ($type === 'all' || $type === 'late-checkins') {
            $this->info('Checking for late check-ins...');
            $alerts = $this->notificationService->checkLateCheckIns();
            $results['late_checkins'] = $alerts;
            $this->info("Sent {$alerts} late check-in alerts");
        }
        
        $this->info('Notification processing completed!');
        
        // Display summary
        $this->table(
            ['Type', 'Count'],
            collect($results)->map(fn($count, $type) => [$type, $count])->toArray()
        );
        
        return 0;
    }
}

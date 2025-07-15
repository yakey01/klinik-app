<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'site_name',
                'value' => 'Dokterku Admin System',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Site Name',
                'description' => 'The name of the application displayed in the header',
                'is_public' => true,
                'is_readonly' => false,
            ],
            [
                'key' => 'site_description',
                'value' => 'Comprehensive clinic management system',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Site Description',
                'description' => 'Brief description of the application',
                'is_public' => true,
                'is_readonly' => false,
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'group' => 'general',
                'label' => 'System Timezone',
                'description' => 'Default timezone for the application',
                'is_public' => false,
                'is_readonly' => false,
            ],
            [
                'key' => 'date_format',
                'value' => 'Y-m-d',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Date Format',
                'description' => 'Default date format for display',
                'is_public' => true,
                'is_readonly' => false,
            ],
            [
                'key' => 'time_format',
                'value' => 'H:i',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Time Format',
                'description' => 'Default time format for display',
                'is_public' => true,
                'is_readonly' => false,
            ],

            // Security Settings
            [
                'key' => 'session_timeout',
                'value' => '120',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Session Timeout (minutes)',
                'description' => 'How long user sessions remain active',
                'is_public' => false,
                'is_readonly' => false,
            ],
            [
                'key' => 'max_login_attempts',
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Max Login Attempts',
                'description' => 'Maximum failed login attempts before lockout',
                'is_public' => false,
                'is_readonly' => false,
            ],
            [
                'key' => 'require_2fa',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'security',
                'label' => 'Require 2FA',
                'description' => 'Require two-factor authentication for all users',
                'is_public' => false,
                'is_readonly' => false,
            ],
            [
                'key' => 'password_min_length',
                'value' => '8',
                'type' => 'integer',
                'group' => 'security',
                'label' => 'Minimum Password Length',
                'description' => 'Minimum required password length',
                'is_public' => false,
                'is_readonly' => false,
            ],

            // Notification Settings
            [
                'key' => 'email_notifications',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'label' => 'Email Notifications',
                'description' => 'Enable email notifications system-wide',
                'is_public' => false,
                'is_readonly' => false,
            ],
            [
                'key' => 'telegram_notifications',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'label' => 'Telegram Notifications',
                'description' => 'Enable Telegram notifications system-wide',
                'is_public' => false,
                'is_readonly' => false,
            ],
            [
                'key' => 'notification_retention_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'notification',
                'label' => 'Notification Retention (days)',
                'description' => 'How long to keep notifications in the system',
                'is_public' => false,
                'is_readonly' => false,
            ],

            // System Settings
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'system',
                'label' => 'Maintenance Mode',
                'description' => 'Enable maintenance mode to restrict access',
                'is_public' => true,
                'is_readonly' => false,
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'System is under maintenance. Please try again later.',
                'type' => 'string',
                'group' => 'system',
                'label' => 'Maintenance Message',
                'description' => 'Message to display during maintenance',
                'is_public' => true,
                'is_readonly' => false,
            ],
            [
                'key' => 'debug_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'system',
                'label' => 'Debug Mode',
                'description' => 'Enable debug mode for troubleshooting',
                'is_public' => false,
                'is_readonly' => false,
            ],
            [
                'key' => 'backup_retention_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'system',
                'label' => 'Backup Retention (days)',
                'description' => 'How long to keep system backups',
                'is_public' => false,
                'is_readonly' => false,
            ],

            // UI Settings
            [
                'key' => 'default_theme',
                'value' => 'dark',
                'type' => 'string',
                'group' => 'ui',
                'label' => 'Default Theme',
                'description' => 'Default theme for new users',
                'is_public' => true,
                'is_readonly' => false,
            ],
            [
                'key' => 'records_per_page',
                'value' => '10',
                'type' => 'integer',
                'group' => 'ui',
                'label' => 'Records Per Page',
                'description' => 'Default number of records to show per page',
                'is_public' => true,
                'is_readonly' => false,
            ],
            [
                'key' => 'enable_animations',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'ui',
                'label' => 'Enable Animations',
                'description' => 'Enable UI animations and transitions',
                'is_public' => true,
                'is_readonly' => false,
            ],

            // API Settings
            [
                'key' => 'api_rate_limit',
                'value' => '100',
                'type' => 'integer',
                'group' => 'api',
                'label' => 'API Rate Limit',
                'description' => 'Maximum API requests per minute per user',
                'is_public' => false,
                'is_readonly' => false,
            ],
            [
                'key' => 'api_timeout',
                'value' => '30',
                'type' => 'integer',
                'group' => 'api',
                'label' => 'API Timeout (seconds)',
                'description' => 'Default timeout for API requests',
                'is_public' => false,
                'is_readonly' => false,
            ],
            [
                'key' => 'api_versioning',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'api',
                'label' => 'API Versioning',
                'description' => 'Enable API versioning support',
                'is_public' => false,
                'is_readonly' => false,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('System settings seeded successfully!');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FeatureFlag;

class FeatureFlagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            // Core System Features
            [
                'key' => 'advanced_reporting',
                'name' => 'Advanced Reporting',
                'description' => 'Enable advanced reporting features with custom filters and exports',
                'is_enabled' => true,
                'conditions' => json_encode(['roles' => ['admin', 'manajer']]),
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'reporting']),
            ],
            [
                'key' => 'bulk_operations',
                'name' => 'Bulk Operations',
                'description' => 'Enable bulk edit, delete, and import/export operations',
                'is_enabled' => true,
                'conditions' => json_encode(['roles' => ['admin', 'manajer', 'bendahara']]),
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'operations']),
            ],
            [
                'key' => 'audit_logging',
                'name' => 'Audit Logging',
                'description' => 'Track all user actions and system changes',
                'is_enabled' => true,
                'conditions' => null,
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => true,
                'meta' => json_encode(['version' => '1.0', 'module' => 'security']),
            ],
            [
                'key' => 'two_factor_auth',
                'name' => 'Two-Factor Authentication',
                'description' => 'Enable 2FA for enhanced security',
                'is_enabled' => false,
                'conditions' => null,
                'environment' => null,
                'starts_at' => null,
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'security']),
            ],

            // UI/UX Features
            [
                'key' => 'dark_mode',
                'name' => 'Dark Mode',
                'description' => 'Enable dark theme support',
                'is_enabled' => true,
                'conditions' => null,
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'ui']),
            ],
            [
                'key' => 'mobile_responsive',
                'name' => 'Mobile Responsive Design',
                'description' => 'Enhanced mobile and tablet experience',
                'is_enabled' => true,
                'conditions' => null,
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'ui']),
            ],
            [
                'key' => 'keyboard_shortcuts',
                'name' => 'Keyboard Shortcuts',
                'description' => 'Enable keyboard shortcuts for power users',
                'is_enabled' => false,
                'conditions' => null,
                'environment' => null,
                'starts_at' => null,
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'ui']),
            ],
            [
                'key' => 'advanced_search',
                'name' => 'Advanced Search',
                'description' => 'Enhanced search with filters and autocomplete',
                'is_enabled' => true,
                'conditions' => null,
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'ui']),
            ],

            // API Features
            [
                'key' => 'api_v2',
                'name' => 'API Version 2',
                'description' => 'Enable new API version with enhanced features',
                'is_enabled' => true,
                'conditions' => null,
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '2.0', 'module' => 'api']),
            ],
            [
                'key' => 'api_rate_limiting',
                'name' => 'API Rate Limiting',
                'description' => 'Implement rate limiting for API endpoints',
                'is_enabled' => true,
                'conditions' => null,
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => true,
                'meta' => json_encode(['version' => '1.0', 'module' => 'api']),
            ],
            [
                'key' => 'api_documentation',
                'name' => 'API Documentation',
                'description' => 'Interactive API documentation with Swagger',
                'is_enabled' => true,
                'conditions' => json_encode(['roles' => ['admin', 'manajer']]),
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'api']),
            ],

            // Notification Features
            [
                'key' => 'real_time_notifications',
                'name' => 'Real-time Notifications',
                'description' => 'WebSocket-based real-time notifications',
                'is_enabled' => false,
                'conditions' => null,
                'environment' => null,
                'starts_at' => null,
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'notifications']),
            ],
            [
                'key' => 'email_notifications',
                'name' => 'Email Notifications',
                'description' => 'Send notifications via email',
                'is_enabled' => true,
                'conditions' => null,
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'notifications']),
            ],
            [
                'key' => 'telegram_notifications',
                'name' => 'Telegram Notifications',
                'description' => 'Send notifications via Telegram',
                'is_enabled' => true,
                'conditions' => null,
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'notifications']),
            ],

            // Experimental Features
            [
                'key' => 'ai_assistant',
                'name' => 'AI Assistant',
                'description' => 'AI-powered assistant for common tasks',
                'is_enabled' => false,
                'conditions' => json_encode(['roles' => ['admin'], 'percentage' => 10]),
                'environment' => 'development',
                'starts_at' => null,
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '0.1', 'module' => 'ai', 'experimental' => true]),
            ],
            [
                'key' => 'voice_commands',
                'name' => 'Voice Commands',
                'description' => 'Voice-activated commands for hands-free operation',
                'is_enabled' => false,
                'conditions' => json_encode(['roles' => ['admin'], 'percentage' => 5]),
                'environment' => 'development',
                'starts_at' => null,
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '0.1', 'module' => 'ui', 'experimental' => true]),
            ],

            // Integration Features
            [
                'key' => 'webhook_integrations',
                'name' => 'Webhook Integrations',
                'description' => 'Send data to external systems via webhooks',
                'is_enabled' => true,
                'conditions' => json_encode(['roles' => ['admin', 'manajer']]),
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'integrations']),
            ],
            [
                'key' => 'export_integrations',
                'name' => 'Export Integrations',
                'description' => 'Export data to various formats and systems',
                'is_enabled' => true,
                'conditions' => json_encode(['roles' => ['admin', 'manajer', 'bendahara']]),
                'environment' => null,
                'starts_at' => now(),
                'ends_at' => null,
                'is_permanent' => false,
                'meta' => json_encode(['version' => '1.0', 'module' => 'integrations']),
            ],
        ];

        foreach ($features as $feature) {
            FeatureFlag::updateOrCreate(
                ['key' => $feature['key']],
                $feature
            );
        }

        $this->command->info('Feature flags seeded successfully!');
    }
}

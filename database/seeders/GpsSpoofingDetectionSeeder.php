<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GpsSpoofingDetectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createGpsSpoofingDetections();
    }

    private function createGpsSpoofingDetections(): void
    {
        // Get existing users
        $users = \App\Models\User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Creating sample user...');
            $users = collect([\App\Models\User::factory()->create()]);
        }

        $this->command->info('Creating GPS spoofing detections...');

        // Create GPS spoofing detections for existing users
        foreach ($users as $user) {
            // Create mostly clean detections (no spoofing)
            \App\Models\GpsSpoofingDetection::factory()
                ->count(rand(20, 30))
                ->clean()
                ->for($user)
                ->create();

            // Create some spoofed detections
            \App\Models\GpsSpoofingDetection::factory()
                ->count(rand(2, 5))
                ->spoofed()
                ->for($user)
                ->create();

            // Create today's detections
            \App\Models\GpsSpoofingDetection::factory()
                ->count(rand(3, 6))
                ->today()
                ->for($user)
                ->create();

            // Create some blocked detections
            \App\Models\GpsSpoofingDetection::factory()
                ->count(rand(1, 3))
                ->blocked()
                ->for($user)
                ->create();
        }

        // Create some additional samples with specific scenarios
        $sampleUser = $users->first();
        
        // High-risk spoofing attempt
        \App\Models\GpsSpoofingDetection::factory()
            ->for($sampleUser)
            ->create([
                'attempted_at' => now()->subHours(2),
                'is_spoofed' => true,
                'risk_level' => 'critical',
                'risk_score' => 95,
                'mock_location_detected' => true,
                'fake_gps_app_detected' => true,
                'developer_mode_detected' => true,
                'detected_fake_apps' => 'Fake GPS Location Pro',
                'action_taken' => 'blocked',
                'admin_notes' => 'Multiple spoofing indicators detected, user blocked immediately'
            ]);

        // Suspicious but not confirmed spoofing
        \App\Models\GpsSpoofingDetection::factory()
            ->for($sampleUser)
            ->create([
                'attempted_at' => now()->subHours(4),
                'is_spoofed' => false,
                'risk_level' => 'medium',
                'risk_score' => 45,
                'impossible_travel_detected' => true,
                'travel_speed_kmh' => 250.5,
                'action_taken' => 'flagged',
                'admin_notes' => 'Possible impossible travel detected, flagged for review'
            ]);

        // Clean detection with perfect conditions
        \App\Models\GpsSpoofingDetection::factory()
            ->for($sampleUser)
            ->create([
                'attempted_at' => now()->subHour(),
                'is_spoofed' => false,
                'risk_level' => 'low',
                'risk_score' => 5,
                'accuracy' => 3.2,
                'action_taken' => 'none',
                'admin_notes' => 'Perfect location conditions, no spoofing indicators'
            ]);

        $this->command->info('GPS spoofing detection seeding completed!');
        
        // Display summary
        $summary = \App\Models\GpsSpoofingDetection::getDetectionSummary();
        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Total Detections', $summary['total']],
                ['Spoofed', $summary['spoofed']],
                ['Blocked', $summary['blocked']],
                ['High Risk', $summary['high_risk']],
                ['Unreviewed', $summary['unreviewed']],
                ['Today', $summary['today']],
                ['Spoofing Rate', $summary['spoofing_rate'] . '%'],
            ]
        );
    }
}

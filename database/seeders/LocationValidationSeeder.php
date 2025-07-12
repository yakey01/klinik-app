<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationValidationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createLocationValidations();
    }

    private function createLocationValidations(): void
    {
        // Get existing users
        $users = \App\Models\User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('No users found. Creating sample user...');
            $users = collect([\App\Models\User::factory()->create()]);
        }

        $this->command->info('Creating location validations...');

        // Create location validations for existing users
        foreach ($users as $user) {
            // Create some validations within zone (successful)
            \App\Models\LocationValidation::factory()
                ->count(rand(15, 25))
                ->withinZone()
                ->for($user)
                ->create();

            // Create some validations outside zone (failed)
            \App\Models\LocationValidation::factory()
                ->count(rand(3, 8))
                ->outsideZone()
                ->for($user)
                ->create();

            // Create today's validations
            \App\Models\LocationValidation::factory()
                ->count(rand(2, 4))
                ->today()
                ->for($user)
                ->create();

            // Create specific check-in and check-out examples
            \App\Models\LocationValidation::factory()
                ->checkIn()
                ->withinZone()
                ->for($user)
                ->create([
                    'validation_time' => now()->startOfDay()->addHours(8),
                    'notes' => 'Regular morning check-in'
                ]);

            \App\Models\LocationValidation::factory()
                ->checkOut()
                ->withinZone()
                ->for($user)
                ->create([
                    'validation_time' => now()->startOfDay()->addHours(17),
                    'notes' => 'Regular evening check-out'
                ]);
        }

        // Create some additional samples with specific scenarios
        $sampleUser = $users->first();
        
        // Weekend work scenario
        \App\Models\LocationValidation::factory()
            ->for($sampleUser)
            ->create([
                'validation_time' => now()->subDays(2)->setHour(9),
                'attendance_type' => 'check_in',
                'is_within_zone' => true,
                'distance_from_zone' => 0,
                'notes' => 'Weekend overtime work'
            ]);

        // Late arrival scenario
        \App\Models\LocationValidation::factory()
            ->for($sampleUser)
            ->create([
                'validation_time' => now()->subDay()->setHour(9)->setMinute(30),
                'attendance_type' => 'check_in',
                'is_within_zone' => false,
                'distance_from_zone' => 250.5,
                'notes' => 'Trying to check in from parking area'
            ]);

        // Emergency check-out scenario
        \App\Models\LocationValidation::factory()
            ->for($sampleUser)
            ->create([
                'validation_time' => now()->subDay()->setHour(15),
                'attendance_type' => 'check_out',
                'is_within_zone' => true,
                'distance_from_zone' => 0,
                'notes' => 'Emergency early departure'
            ]);

        $this->command->info('Location validation seeding completed!');
        
        // Display summary
        $summary = \App\Models\LocationValidation::getValidationSummary();
        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Total Validations', $summary['total']],
                ['Valid (Within Zone)', $summary['valid']],
                ['Invalid (Outside Zone)', $summary['invalid']],
                ['Check-ins', $summary['check_ins']],
                ['Check-outs', $summary['check_outs']],
                ['Today', $summary['today']],
                ['Success Rate', $summary['success_rate'] . '%'],
            ]
        );
    }
}

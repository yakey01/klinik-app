<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorkLocation;
use App\Models\Location;
use App\Models\User;

class MigrateWorkLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:work-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from work_locations to locations table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration from work_locations to locations...');

        try {
            // Get all work locations
            $workLocations = WorkLocation::all();
            
            if ($workLocations->isEmpty()) {
                $this->info('No work locations found to migrate.');
                return;
            }

            $this->info("Found {$workLocations->count()} work locations to migrate.");

            // Get admin user for created_by field
            $admin = User::first();
            if (!$admin) {
                $this->error('No admin user found. Please create at least one user first.');
                return;
            }

            $migratedCount = 0;
            $skippedCount = 0;

            foreach ($workLocations as $workLocation) {
                // Check if location already exists with same name
                $existingLocation = Location::where('name', $workLocation->name)->first();
                
                if ($existingLocation) {
                    $this->warn("Location '{$workLocation->name}' already exists, skipping...");
                    $skippedCount++;
                    continue;
                }

                // Create new location
                $location = Location::create([
                    'name' => $workLocation->name,
                    'latitude' => $workLocation->latitude,
                    'longitude' => $workLocation->longitude,
                    'radius' => $workLocation->radius_meters ?? 100, // Default 100m if null
                    'created_by' => $admin->id,
                    'created_at' => $workLocation->created_at,
                    'updated_at' => $workLocation->updated_at,
                ]);

                $this->info("✓ Migrated: {$workLocation->name}");
                $migratedCount++;

                // Update users who have this work_location_id
                $usersWithThisLocation = User::where('work_location_id', $workLocation->id)->get();
                
                if ($usersWithThisLocation->isNotEmpty()) {
                    foreach ($usersWithThisLocation as $user) {
                        $user->update(['location_id' => $location->id]);
                    }
                    
                    $this->info("  → Updated {$usersWithThisLocation->count()} users to use new location");
                }
            }

            $this->newLine();
            $this->info("Migration completed!");
            $this->info("✓ Migrated: {$migratedCount} locations");
            $this->info("⚠ Skipped: {$skippedCount} locations (already exist)");

            if ($migratedCount > 0) {
                $this->newLine();
                $this->warn('Next steps:');
                $this->warn('1. Verify the migrated data in admin panel (/admin/locations)');
                $this->warn('2. Test the geofencing functionality');
                $this->warn('3. Once verified, you can drop the work_locations table');
            }

        } catch (\Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
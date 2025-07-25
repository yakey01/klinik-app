<?php

namespace App\Listeners;

use App\Events\WorkLocationUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearWorkLocationCache implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(WorkLocationUpdated $event): void
    {
        $workLocation = $event->workLocation;
        
        Log::info('WorkLocation updated, clearing related caches', [
            'work_location_id' => $workLocation->id,
            'name' => $workLocation->name,
            'changed_fields' => $event->changedFields
        ]);

        // Clear specific work location cache
        Cache::forget("work_location_{$workLocation->id}");
        
        // Clear all user-related caches for users assigned to this location
        $users = $workLocation->users()->get(['id']);
        
        foreach ($users as $user) {
            $this->clearUserRelatedCaches($user->id);
        }
        
        // Clear general dashboard caches
        $this->clearGeneralCaches();
        
        Log::info('Work location caches cleared successfully', [
            'work_location_id' => $workLocation->id,
            'affected_users' => $users->count()
        ]);
    }

    /**
     * Clear user-specific caches
     */
    private function clearUserRelatedCaches(int $userId): void
    {
        $cacheKeys = [
            "paramedis_dashboard_stats_{$userId}",
            "user_work_location_{$userId}",
            "attendance_status_{$userId}",
            "user_attendance_validation_{$userId}",
            "user_geofence_data_{$userId}",
            "user_location_permissions_{$userId}",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear general caches that might be affected
     */
    private function clearGeneralCaches(): void
    {
        $generalCacheKeys = [
            'work_locations_active',
            'work_locations_all',
            'geofence_locations',
            'attendance_locations',
        ];

        foreach ($generalCacheKeys as $key) {
            Cache::forget($key);
        }

        // Clear pattern-based caches
        $this->clearCachePattern('dashboard_stats_*');
        $this->clearCachePattern('work_location_*');
        $this->clearCachePattern('attendance_*');
    }

    /**
     * Clear cache keys matching a pattern
     */
    private function clearCachePattern(string $pattern): void
    {
        try {
            // For Redis cache
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Cache::getRedis()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            }
            // For other cache drivers, we can't easily clear by pattern
            // so we rely on specific key clearing above
        } catch (\Exception $e) {
            Log::warning('Failed to clear cache pattern: ' . $pattern, [
                'error' => $e->getMessage()
            ]);
        }
    }
}
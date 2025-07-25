<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\WorkLocation;
use App\Models\User;
use Carbon\Carbon;

class DebugGPSCoordinates extends Command
{
    protected $signature = 'debug:gps-coordinates {user_id?}';
    protected $description = 'Debug GPS coordinates issue in attendance system';

    public function handle()
    {
        $this->info('🔍 Debugging GPS Coordinates Issue');
        $this->info('=' . str_repeat('=', 50));

        // Get recent check-ins
        $this->info("\n📍 Recent Check-in Coordinates (Last 24 hours):");
        $recentAttendances = Attendance::where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $coordinateGroups = [];
        foreach ($recentAttendances as $att) {
            $coordKey = $att->latitude . ',' . $att->longitude;
            if (!isset($coordinateGroups[$coordKey])) {
                $coordinateGroups[$coordKey] = [
                    'count' => 0,
                    'users' => [],
                    'times' => [],
                    'lat' => $att->latitude,
                    'lon' => $att->longitude
                ];
            }
            $coordinateGroups[$coordKey]['count']++;
            $coordinateGroups[$coordKey]['users'][] = $att->user_id;
            $coordinateGroups[$coordKey]['times'][] = $att->created_at->format('H:i:s');
        }

        foreach ($coordinateGroups as $coords => $data) {
            $this->info("\nCoordinates: {$coords}");
            $this->info("  Count: {$data['count']} check-ins");
            $this->info("  Users: " . implode(', ', array_unique($data['users'])));
            $this->info("  Times: " . implode(', ', $data['times']));
            
            // Reverse geocode
            $this->reverseGeocode($data['lat'], $data['lon']);
        }

        // Get work locations
        $this->info("\n🏢 Work Locations:");
        $workLocations = WorkLocation::where('is_active', true)->get();
        foreach ($workLocations as $wl) {
            $this->info("\n{$wl->name} (ID: {$wl->id}):");
            $this->info("  Address: {$wl->address}");
            $this->info("  Coordinates: {$wl->latitude}, {$wl->longitude}");
            $this->info("  Radius: {$wl->radius_meters}m");
            $this->reverseGeocode($wl->latitude, $wl->longitude);
            
            // Calculate distance from Jakarta coords
            $distance = $wl->calculateDistance(-6.2088, 106.8456);
            $this->info("  Distance from Jakarta (-6.2088, 106.8456): " . number_format($distance, 2) . "m");
        }

        // Check specific user if provided
        $userId = $this->argument('user_id');
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $this->info("\n👤 User: {$user->name} (ID: {$user->id})");
                $this->info("  Work Location ID: {$user->work_location_id}");
                
                if ($user->workLocation) {
                    $wl = $user->workLocation;
                    $this->info("  Assigned to: {$wl->name}");
                    $this->info("  Location: {$wl->latitude}, {$wl->longitude}");
                    
                    // Check last attendance
                    $lastAttendance = Attendance::where('user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($lastAttendance) {
                        $this->info("\n  Last Check-in:");
                        $this->info("    Time: {$lastAttendance->created_at}");
                        $this->info("    Coordinates: {$lastAttendance->latitude}, {$lastAttendance->longitude}");
                        
                        $distance = $wl->calculateDistance($lastAttendance->latitude, $lastAttendance->longitude);
                        $this->info("    Distance from work location: " . number_format($distance, 2) . "m");
                        $this->info("    Within geofence? " . ($distance <= $wl->radius_meters ? '✅ YES' : '❌ NO'));
                    }
                }
            }
        }

        // Check for hardcoded coordinates pattern
        $this->info("\n⚠️  Checking for Hardcoded Coordinates:");
        $jakartaCoords = Attendance::where('latitude', -6.2088)
            ->where('longitude', 106.8456)
            ->count();
        
        if ($jakartaCoords > 0) {
            $this->error("  Found {$jakartaCoords} check-ins with exact Jakarta coordinates (-6.2088, 106.8456)");
            $this->error("  This suggests coordinates are being hardcoded instead of using actual GPS!");
            
            // Get users affected
            $affectedUsers = Attendance::where('latitude', -6.2088)
                ->where('longitude', 106.8456)
                ->distinct('user_id')
                ->pluck('user_id');
            
            $this->error("  Affected users: " . implode(', ', $affectedUsers->toArray()));
        }

        $this->info("\n💡 Recommendations:");
        $this->info("  1. Check if the mobile app is properly requesting GPS permissions");
        $this->info("  2. Verify the LeafletMap component is passing actual GPS coordinates");
        $this->info("  3. Check if there's a fallback to default coordinates when GPS fails");
        $this->info("  4. Ensure the GPS detection runs before allowing check-in");

        return 0;
    }

    private function reverseGeocode($lat, $lon)
    {
        try {
            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: DokterKu App Debug\r\n",
                    'timeout' => 5
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['display_name'])) {
                    $this->info("  Location: " . $data['display_name']);
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
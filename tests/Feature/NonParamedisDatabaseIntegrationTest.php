<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\NonParamedisAttendance;
use App\Models\WorkLocation;
use App\Models\Schedule;
use App\Models\Shift;
use App\Services\GpsValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;

class NonParamedisDatabaseIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private WorkLocation $workLocation;
    private GpsValidationService $gpsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::factory()->create(['name' => 'non_paramedis']);
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true
        ]);
        
        $this->workLocation = WorkLocation::factory()->create([
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'is_active' => true,
            'location_type' => 'main_office'
        ]);

        $this->gpsService = app(GpsValidationService::class);
    }

    /** @test */
    public function test_database_connection_is_working()
    {
        $this->assertTrue(DB::connection()->getDatabaseName() !== null);
        
        // Test basic query execution
        $result = DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
    }

    /** @test */
    public function test_non_paramedis_attendance_model_relationships()
    {
        $attendance = NonParamedisAttendance::factory()->create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id
        ]);

        // Test user relationship
        $this->assertInstanceOf(User::class, $attendance->user);
        $this->assertEquals($this->user->id, $attendance->user->id);

        // Test work location relationship
        $this->assertInstanceOf(WorkLocation::class, $attendance->workLocation);
        $this->assertEquals($this->workLocation->id, $attendance->workLocation->id);

        // Test inverse relationships
        $this->assertTrue($this->user->nonParamedisAttendances()->exists());
        $this->assertEquals($attendance->id, $this->user->nonParamedisAttendances()->first()->id);
    }

    /** @test */
    public function test_attendance_data_integrity_constraints()
    {
        // Test required fields
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        NonParamedisAttendance::create([
            // Missing required user_id and attendance_date
        ]);
    }

    /** @test */
    public function test_attendance_date_casting_and_storage()
    {
        $today = Carbon::today();
        $checkInTime = Carbon::now()->setTime(8, 30, 0);
        $checkOutTime = Carbon::now()->setTime(17, 45, 0);

        $attendance = NonParamedisAttendance::create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => $today,
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'status' => 'checked_out'
        ]);

        $attendance->refresh();

        // Test date casting
        $this->assertInstanceOf(Carbon::class, $attendance->attendance_date);
        $this->assertEquals($today->toDateString(), $attendance->attendance_date->toDateString());

        // Test datetime casting
        $this->assertInstanceOf(Carbon::class, $attendance->check_in_time);
        $this->assertInstanceOf(Carbon::class, $attendance->check_out_time);
        
        // Test time accuracy
        $this->assertEquals('08:30:00', $attendance->check_in_time->format('H:i:s'));
        $this->assertEquals('17:45:00', $attendance->check_out_time->format('H:i:s'));
    }

    /** @test */
    public function test_gps_coordinates_precision_storage()
    {
        $latitude = -6.20012345;
        $longitude = 106.81667890;
        $accuracy = 5.75;

        $attendance = NonParamedisAttendance::create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => Carbon::today(),
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'check_in_accuracy' => $accuracy,
            'status' => 'checked_in'
        ]);

        $attendance->refresh();

        // Test decimal precision (should be stored with 8 decimal places for coordinates)
        $this->assertEquals(-6.20012345, (float) $attendance->check_in_latitude);
        $this->assertEquals(106.81667890, (float) $attendance->check_in_longitude);
        $this->assertEquals(5.75, (float) $attendance->check_in_accuracy);
    }

    /** @test */
    public function test_json_fields_storage_and_retrieval()
    {
        $deviceInfo = [
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
            'platform' => 'iOS',
            'app_version' => '1.0.0'
        ];

        $gpsMetadata = [
            'accuracy' => 5.0,
            'timestamp' => Carbon::now()->toISOString(),
            'provider' => 'gps'
        ];

        $attendance = NonParamedisAttendance::create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => Carbon::today(),
            'device_info' => $deviceInfo,
            'gps_metadata' => $gpsMetadata,
            'status' => 'checked_in'
        ]);

        $attendance->refresh();

        // Test JSON field casting
        $this->assertIsArray($attendance->device_info);
        $this->assertIsArray($attendance->gps_metadata);
        
        // Test data integrity
        $this->assertEquals($deviceInfo['user_agent'], $attendance->device_info['user_agent']);
        $this->assertEquals($gpsMetadata['accuracy'], $attendance->gps_metadata['accuracy']);
    }

    /** @test */
    public function test_work_duration_calculation_accuracy()
    {
        $checkInTime = Carbon::now()->setTime(8, 0, 0);
        $checkOutTime = Carbon::now()->setTime(17, 30, 0); // 9.5 hours later

        $attendance = NonParamedisAttendance::create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => Carbon::today(),
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'status' => 'checked_out'
        ]);

        // Test work duration calculation
        $expectedMinutes = 9.5 * 60; // 570 minutes
        $actualMinutes = $attendance->calculateWorkDuration();
        
        $this->assertEquals($expectedMinutes, $actualMinutes);

        // Test automatic duration update
        $attendance->updateWorkDuration();
        $this->assertEquals($expectedMinutes, $attendance->total_work_minutes);

        // Test formatted duration
        $this->assertEquals('9 jam 30 menit', $attendance->formatted_work_duration);
    }

    /** @test */
    public function test_attendance_status_transitions()
    {
        $attendance = NonParamedisAttendance::create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => Carbon::today(),
            'status' => 'checked_in'
        ]);

        // Test initial state
        $this->assertTrue($attendance->isCheckedIn());
        $this->assertFalse($attendance->isCheckedOut());
        $this->assertFalse($attendance->isComplete());

        // Update to checked out
        $attendance->update([
            'check_out_time' => Carbon::now(),
            'status' => 'checked_out'
        ]);

        $this->assertFalse($attendance->isCheckedIn());
        $this->assertTrue($attendance->isCheckedOut());
        $this->assertTrue($attendance->isComplete());
    }

    /** @test */
    public function test_approval_workflow_data_integrity()
    {
        $admin = User::factory()->create(['name' => 'Admin User']);
        
        $attendance = NonParamedisAttendance::create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => Carbon::today(),
            'status' => 'checked_out',
            'approval_status' => 'pending'
        ]);

        // Test approval
        $approvalNotes = 'Approved after verification';
        $attendance->approve($admin, $approvalNotes);

        $attendance->refresh();
        
        $this->assertEquals('approved', $attendance->approval_status);
        $this->assertEquals($admin->id, $attendance->approved_by);
        $this->assertEquals($approvalNotes, $attendance->approval_notes);
        $this->assertNotNull($attendance->approved_at);
        $this->assertInstanceOf(Carbon::class, $attendance->approved_at);

        // Test approver relationship
        $this->assertInstanceOf(User::class, $attendance->approver);
        $this->assertEquals($admin->id, $attendance->approver->id);
    }

    /** @test */
    public function test_attendance_queries_performance()
    {
        // Create test data
        $users = User::factory()->count(10)->create(['role_id' => $this->user->role_id]);
        $workLocations = WorkLocation::factory()->count(3)->create(['is_active' => true]);

        $attendances = collect();
        foreach ($users as $user) {
            for ($day = 1; $day <= 30; $day++) {
                $attendances->push([
                    'user_id' => $user->id,
                    'work_location_id' => $workLocations->random()->id,
                    'attendance_date' => Carbon::now()->subDays($day),
                    'check_in_time' => Carbon::now()->subDays($day)->setTime(8, 0),
                    'check_out_time' => Carbon::now()->subDays($day)->setTime(17, 0),
                    'total_work_minutes' => 540,
                    'status' => 'checked_out',
                    'approval_status' => 'approved',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        NonParamedisAttendance::insert($attendances->toArray());

        // Track query count
        $queryCount = 0;
        DB::listen(function (QueryExecuted $query) use (&$queryCount) {
            $queryCount++;
        });

        // Test efficient queries
        $monthlyAttendances = NonParamedisAttendance::where('user_id', $this->user->id)
            ->whereBetween('attendance_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->with(['user', 'workLocation'])
            ->get();

        // Verify query efficiency (should use eager loading)
        $this->assertLessThan(5, $queryCount); // Should be 2-3 queries max with proper eager loading
        $this->assertGreaterThan(0, $monthlyAttendances->count());
    }

    /** @test */
    public function test_database_indexes_are_effective()
    {
        // Create large dataset
        $users = User::factory()->count(100)->create(['role_id' => $this->user->role_id]);
        
        $attendances = collect();
        foreach ($users as $user) {
            for ($day = 1; $day <= 365; $day++) {
                $attendances->push([
                    'user_id' => $user->id,
                    'work_location_id' => $this->workLocation->id,
                    'attendance_date' => Carbon::now()->subDays($day),
                    'status' => 'checked_out',
                    'approval_status' => 'approved',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        NonParamedisAttendance::insert($attendances->chunk(1000)->first()->toArray());

        // Measure query performance
        $startTime = microtime(true);
        
        $result = NonParamedisAttendance::where('user_id', $this->user->id)
            ->where('attendance_date', Carbon::today())
            ->first();
            
        $queryTime = microtime(true) - $startTime;

        // Query should complete quickly (less than 100ms) with proper indexing
        $this->assertLessThan(0.1, $queryTime);
    }

    /** @test */
    public function test_concurrent_attendance_operations()
    {
        // Test concurrent check-in attempts (simulating race conditions)
        $attendance1 = null;
        $attendance2 = null;
        $exception1 = null;
        $exception2 = null;

        // Simulate concurrent transactions
        DB::transaction(function () use (&$attendance1, &$exception1) {
            try {
                $attendance1 = NonParamedisAttendance::create([
                    'user_id' => $this->user->id,
                    'work_location_id' => $this->workLocation->id,
                    'attendance_date' => Carbon::today(),
                    'check_in_time' => Carbon::now(),
                    'status' => 'checked_in'
                ]);
            } catch (\Exception $e) {
                $exception1 = $e;
            }
        });

        DB::transaction(function () use (&$attendance2, &$exception2) {
            try {
                $attendance2 = NonParamedisAttendance::create([
                    'user_id' => $this->user->id,
                    'work_location_id' => $this->workLocation->id,
                    'attendance_date' => Carbon::today(),
                    'check_in_time' => Carbon::now(),
                    'status' => 'checked_in'
                ]);
            } catch (\Exception $e) {
                $exception2 = $e;
            }
        });

        // One should succeed, one should fail (or be handled by unique constraint)
        $this->assertTrue(($attendance1 && !$attendance2) || (!$attendance1 && $attendance2));
    }

    /** @test */
    public function test_gps_validation_with_real_coordinates()
    {
        // Jakarta coordinates (realistic test data)
        $officeLocation = [
            'latitude' => -6.200000,
            'longitude' => 106.816666
        ];

        $validCoordinates = [
            'latitude' => -6.200010,  // ~1.11 meters from office
            'longitude' => 106.816676,
            'accuracy' => 5.0
        ];

        $invalidCoordinates = [
            'latitude' => -6.300000,  // ~11 km from office
            'longitude' => 106.900000,
            'accuracy' => 5.0
        ];

        // Test valid GPS validation
        $validResult = $this->gpsService->validateLocation(
            $validCoordinates['latitude'],
            $validCoordinates['longitude'],
            $validCoordinates['accuracy']
        );

        $this->assertTrue($validResult['is_valid']);
        $this->assertLessThan(100, $validResult['distance']); // Within office radius

        // Test invalid GPS validation
        $invalidResult = $this->gpsService->validateLocation(
            $invalidCoordinates['latitude'],
            $invalidCoordinates['longitude'],
            $invalidCoordinates['accuracy']
        );

        $this->assertFalse($invalidResult['is_valid']);
        $this->assertGreaterThan(1000, $invalidResult['distance']); // Far from office
    }

    /** @test */
    public function test_bulk_operations_performance()
    {
        $startTime = microtime(true);

        // Bulk create attendance records
        $attendances = collect();
        for ($i = 1; $i <= 1000; $i++) {
            $attendances->push([
                'user_id' => $this->user->id,
                'work_location_id' => $this->workLocation->id,
                'attendance_date' => Carbon::now()->subDays($i),
                'status' => 'checked_out',
                'approval_status' => 'approved',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Use chunked bulk insert for better performance
        $attendances->chunk(100)->each(function ($chunk) {
            NonParamedisAttendance::insert($chunk->toArray());
        });

        $insertTime = microtime(true) - $startTime;

        // Bulk operations should complete within reasonable time
        $this->assertLessThan(5.0, $insertTime); // Less than 5 seconds

        // Verify all records were inserted
        $count = NonParamedisAttendance::where('user_id', $this->user->id)->count();
        $this->assertEquals(1000, $count);
    }

    /** @test */
    public function test_data_consistency_across_relationships()
    {
        // Create related data
        $shift = Shift::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $this->user->id,
            'shift_id' => $shift->id,
            'date' => Carbon::today()
        ]);

        $attendance = NonParamedisAttendance::create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => Carbon::today(),
            'check_in_time' => Carbon::parse($shift->start_time),
            'check_out_time' => Carbon::parse($shift->end_time),
            'status' => 'checked_out'
        ]);

        // Test data consistency
        $this->assertEquals($this->user->id, $schedule->user_id);
        $this->assertEquals($this->user->id, $attendance->user_id);
        $this->assertEquals($schedule->date->toDateString(), $attendance->attendance_date->toDateString());

        // Test cascading updates (if implemented)
        $this->user->update(['name' => 'Updated Name']);
        
        $this->assertEquals('Updated Name', $attendance->fresh()->user->name);
        $this->assertEquals('Updated Name', $schedule->fresh()->user->name);
    }
}
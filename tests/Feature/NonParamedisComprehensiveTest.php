<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\NonParamedisAttendance;
use App\Models\WorkLocation;
use App\Models\Schedule;
use App\Models\Shift;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NonParamedisComprehensiveTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $nonParamedisUser;
    private WorkLocation $workLocation;
    private array $validGpsCoordinates;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $role = Role::factory()->create(['name' => 'non_paramedis']);
        
        // Create work location with realistic coordinates
        $this->workLocation = WorkLocation::factory()->create([
            'name' => 'Kantor Pusat',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'is_active' => true,
            'location_type' => 'main_office',
            'address' => 'Jl. Test No. 123, Jakarta Pusat'
        ]);
        
        // Create non-paramedis user
        $this->nonParamedisUser = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
            'name' => 'Test NonParamedis User',
            'email' => 'nonparamedis@test.com',
            'username' => 'nonparamedis_test'
        ]);
        
        // Valid GPS coordinates within office radius
        $this->validGpsCoordinates = [
            'latitude' => -6.200010,  // 1.11 meters from center
            'longitude' => 106.816676,
            'accuracy' => 5.0
        ];
    }

    /** @test */
    public function test_api_health_check_returns_proper_structure()
    {
        $response = $this->getJson('/api/v2/system/health');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'status',
                         'version',
                         'timestamp',
                         'database'
                     ],
                     'meta' => [
                         'version',
                         'timestamp',
                         'request_id'
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'status' => 'ok',
                         'version' => '2.0',
                         'database' => 'connected'
                     ]
                 ]);
    }

    /** @test */
    public function test_work_locations_endpoint_returns_active_locations()
    {
        // Create additional locations
        WorkLocation::factory()->create(['is_active' => false]);
        WorkLocation::factory()->create(['is_active' => true]);

        $response = $this->getJson('/api/v2/locations/work-locations');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data',
                     'meta'
                 ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data)); // At least 2 active locations
        
        // Verify all returned locations are active
        foreach ($data as $location) {
            $dbLocation = WorkLocation::find($location['id']);
            $this->assertTrue($dbLocation->is_active);
        }
    }

    /** @test */
    public function test_unauthenticated_access_is_blocked()
    {
        $protectedEndpoints = [
            '/api/v2/dashboards/nonparamedis/',
            '/api/v2/dashboards/nonparamedis/attendance/status',
            '/api/v2/dashboards/nonparamedis/attendance/checkin',
            '/api/v2/dashboards/nonparamedis/attendance/checkout',
            '/api/v2/dashboards/nonparamedis/profile',
            '/api/v2/dashboards/nonparamedis/schedule',
            '/api/v2/dashboards/nonparamedis/reports'
        ];

        foreach ($protectedEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertStatus(401);
        }
    }

    /** @test */
    public function test_wrong_role_access_is_blocked()
    {
        // Create user with different role
        $wrongRole = Role::factory()->create(['name' => 'paramedis']);
        $wrongUser = User::factory()->create([
            'role_id' => $wrongRole->id,
            'is_active' => true
        ]);

        Sanctum::actingAs($wrongUser);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        $response->assertStatus(403);
    }

    /** @test */
    public function test_dashboard_endpoint_returns_complete_data()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'user' => [
                             'id',
                             'name',
                             'initials',
                             'role'
                         ],
                         'stats' => [
                             'hours_today',
                             'minutes_today',
                             'work_days_this_month',
                             'total_work_hours_this_month',
                             'attendance_rate',
                             'shifts_this_week',
                             'expected_work_days'
                         ],
                         'current_status',
                         'today_attendance',
                         'quick_actions'
                     ],
                     'meta'
                 ])
                 ->assertJson([
                     'status' => 'success',
                     'data' => [
                         'user' => [
                             'id' => $this->nonParamedisUser->id,
                             'name' => $this->nonParamedisUser->name,
                             'role' => 'Admin Non-Medis'
                         ],
                         'current_status' => 'not_checked_in'
                     ]
                 ]);
    }

    /** @test */
    public function test_attendance_status_endpoint_returns_correct_initial_state()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/attendance/status');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'status',
                         'check_in_time',
                         'check_out_time',
                         'work_duration',
                         'location',
                         'can_check_in',
                         'can_check_out'
                     ]
                 ])
                 ->assertJson([
                     'status' => 'success',
                     'data' => [
                         'status' => 'not_checked_in',
                         'check_in_time' => null,
                         'check_out_time' => null,
                         'work_duration' => null,
                         'can_check_in' => true,
                         'can_check_out' => false
                     ]
                 ]);
    }

    /** @test */
    public function test_checkin_with_valid_gps_succeeds()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        $checkInData = array_merge($this->validGpsCoordinates, [
            'work_location_id' => $this->workLocation->id
        ]);

        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', $checkInData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'attendance_id',
                         'check_in_time',
                         'status',
                         'location',
                         'distance'
                     ]
                 ])
                 ->assertJson([
                     'status' => 'success',
                     'data' => [
                         'status' => 'checked_in'
                     ]
                 ]);

        // Verify database record
        $this->assertDatabaseHas('non_paramedis_attendances', [
            'user_id' => $this->nonParamedisUser->id,
            'attendance_date' => Carbon::today(),
            'status' => 'checked_in',
            'approval_status' => 'pending',
            'check_in_valid_location' => true
        ]);
    }

    /** @test */
    public function test_checkin_with_invalid_gps_fails()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        $invalidGpsData = [
            'latitude' => -6.300000,  // Far from office location
            'longitude' => 106.900000,
            'accuracy' => 5.0
        ];

        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', $invalidGpsData);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'errors' => [
                         'gps_validation',
                         'distance',
                         'gps_quality'
                     ]
                 ]);

        // Verify no database record is created
        $this->assertDatabaseMissing('non_paramedis_attendances', [
            'user_id' => $this->nonParamedisUser->id,
            'attendance_date' => Carbon::today()
        ]);
    }

    /** @test */
    public function test_checkin_validation_rules()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        // Test missing required fields
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['latitude', 'longitude']);

        // Test invalid latitude
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', [
            'latitude' => 91, // Invalid latitude
            'longitude' => 106.816666
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['latitude']);

        // Test invalid longitude
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', [
            'latitude' => -6.200000,
            'longitude' => 181 // Invalid longitude
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['longitude']);
    }

    /** @test */
    public function test_duplicate_checkin_is_prevented()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        // First check-in
        $checkInData = $this->validGpsCoordinates;
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', $checkInData);
        $response->assertStatus(200);

        // Attempt second check-in
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', $checkInData);
        $response->assertStatus(422)
                 ->assertJson([
                     'status' => 'error',
                     'message' => 'Anda sudah melakukan check-in hari ini'
                 ]);
    }

    /** @test */
    public function test_checkout_after_checkin_succeeds()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        // Create check-in record
        $attendance = NonParamedisAttendance::create([
            'user_id' => $this->nonParamedisUser->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => Carbon::today(),
            'check_in_time' => Carbon::now()->subHours(8),
            'check_in_latitude' => $this->validGpsCoordinates['latitude'],
            'check_in_longitude' => $this->validGpsCoordinates['longitude'],
            'check_in_accuracy' => $this->validGpsCoordinates['accuracy'],
            'check_in_valid_location' => true,
            'status' => 'checked_in',
            'approval_status' => 'pending'
        ]);

        $checkOutData = $this->validGpsCoordinates;
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkout', $checkOutData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'attendance_id',
                         'check_out_time',
                         'work_duration_hours',
                         'work_duration_formatted',
                         'status',
                         'location_valid',
                         'distance'
                     ]
                 ])
                 ->assertJson([
                     'status' => 'success',
                     'data' => [
                         'status' => 'checked_out'
                     ]
                 ]);

        // Verify database update
        $attendance->refresh();
        $this->assertNotNull($attendance->check_out_time);
        $this->assertEquals('checked_out', $attendance->status);
        $this->assertGreaterThan(0, $attendance->total_work_minutes);
    }

    /** @test */
    public function test_checkout_without_checkin_fails()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        $checkOutData = $this->validGpsCoordinates;
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkout', $checkOutData);

        $response->assertStatus(422)
                 ->assertJson([
                     'status' => 'error',
                     'message' => 'Tidak ditemukan data check-in hari ini'
                 ]);
    }

    /** @test */
    public function test_today_history_returns_attendance_records()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        // Create attendance record with both check-in and check-out
        $attendance = NonParamedisAttendance::create([
            'user_id' => $this->nonParamedisUser->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => Carbon::today(),
            'check_in_time' => Carbon::today()->setTime(8, 0),
            'check_out_time' => Carbon::today()->setTime(17, 0),
            'total_work_minutes' => 540, // 9 hours
            'status' => 'checked_out',
            'approval_status' => 'pending',
            'check_in_valid_location' => true,
            'check_out_valid_location' => true
        ]);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/attendance/today-history');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'history',
                         'has_activity',
                         'attendance_summary'
                     ]
                 ])
                 ->assertJson([
                     'status' => 'success',
                     'data' => [
                         'has_activity' => true
                     ]
                 ]);

        $history = $response->json('data.history');
        $this->assertCount(2, $history); // Check-in and check-out
        $this->assertEquals('Check-in', $history[0]['action']);
        $this->assertEquals('Check-out', $history[1]['action']);
    }

    /** @test */
    public function test_schedule_endpoint_returns_monthly_calendar()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        // Create shift
        $shift = Shift::factory()->create([
            'name' => 'Regular Shift',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'description' => 'Regular work hours'
        ]);

        // Create schedule for current month
        $schedules = [];
        for ($day = 1; $day <= 5; $day++) {
            $schedules[] = Schedule::factory()->create([
                'user_id' => $this->nonParamedisUser->id,
                'shift_id' => $shift->id,
                'date' => Carbon::now()->startOfMonth()->addDays($day - 1),
                'is_day_off' => false
            ]);
        }

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/schedule');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'month' => [
                             'name',
                             'year',
                             'total_days',
                             'work_days_scheduled',
                             'days_off',
                             'calendar'
                         ],
                         'current_week' => [
                             'week_start',
                             'week_end',
                             'shifts'
                         ],
                         'upcoming_shifts',
                         'statistics' => [
                             'attendance_rate',
                             'total_scheduled_hours',
                             'total_worked_hours',
                             'average_daily_hours'
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function test_reports_endpoint_returns_attendance_analytics()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        // Create sample attendance data
        $attendances = collect();
        for ($day = 1; $day <= 10; $day++) {
            $attendances->push(NonParamedisAttendance::factory()->create([
                'user_id' => $this->nonParamedisUser->id,
                'attendance_date' => Carbon::now()->startOfMonth()->addDays($day - 1),
                'check_in_time' => Carbon::now()->startOfMonth()->addDays($day - 1)->setTime(8, 0),
                'check_out_time' => Carbon::now()->startOfMonth()->addDays($day - 1)->setTime(17, 0),
                'total_work_minutes' => 540,
                'status' => 'checked_out',
                'approval_status' => $day % 3 === 0 ? 'approved' : 'pending'
            ]));
        }

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/reports');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'period' => [
                             'type',
                             'start_date',
                             'end_date',
                             'display_name'
                         ],
                         'summary' => [
                             'total_scheduled_days',
                             'work_days_completed',
                             'expected_work_days',
                             'attendance_rate',
                             'total_work_hours',
                             'average_daily_hours',
                             'approval_summary'
                         ],
                         'recent_history',
                         'performance_indicators' => [
                             'punctuality_score',
                             'consistency_score',
                             'location_compliance'
                         ]
                     ]
                 ]);

        $data = $response->json('data');
        $this->assertEquals('month', $data['period']['type']);
        $this->assertEquals(10, $data['summary']['work_days_completed']);
        $this->assertIsFloat($data['performance_indicators']['punctuality_score']);
    }

    /** @test */
    public function test_profile_endpoint_returns_user_data()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/profile');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'user' => [
                             'id',
                             'name',
                             'initials',
                             'email',
                             'username',
                             'role',
                             'is_verified',
                             'status'
                         ],
                         'attendance_stats' => [
                             'total_this_month',
                             'approved_this_month',
                             'approval_rate'
                         ],
                         'settings',
                         'permissions'
                     ]
                 ])
                 ->assertJson([
                     'status' => 'success',
                     'data' => [
                         'user' => [
                             'id' => $this->nonParamedisUser->id,
                             'name' => $this->nonParamedisUser->name,
                             'email' => $this->nonParamedisUser->email,
                             'role' => 'Administrator Non-Medis'
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function test_api_rate_limiting_on_attendance_endpoints()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        // Make multiple rapid requests to test rate limiting
        $checkInData = $this->validGpsCoordinates;
        
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', $checkInData);
            
            if ($i === 0) {
                // First request should succeed
                $response->assertStatus(200);
            } else {
                // Subsequent requests should be rate limited or fail validation
                $this->assertContains($response->status(), [422, 429]); // 422 for duplicate, 429 for rate limit
            }
        }
    }

    /** @test */
    public function test_api_response_headers_are_present()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');

        $response->assertStatus(200);
        
        // Check for security headers
        $this->assertNotNull($response->headers->get('X-Frame-Options'));
        $this->assertNotNull($response->headers->get('X-Content-Type-Options'));
        $this->assertNotNull($response->headers->get('X-XSS-Protection'));
    }

    /** @test */
    public function test_api_responses_include_standardized_metadata()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        $endpoints = [
            '/api/v2/dashboards/nonparamedis/',
            '/api/v2/dashboards/nonparamedis/attendance/status',
            '/api/v2/dashboards/nonparamedis/profile'
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            
            $response->assertStatus(200)
                     ->assertJsonStructure([
                         'status',
                         'message',
                         'data',
                         'meta' => [
                             'version',
                             'timestamp',
                             'request_id'
                         ]
                     ]);

            $meta = $response->json('meta');
            $this->assertEquals('2.0', $meta['version']);
            $this->assertNotNull($meta['timestamp']);
            $this->assertNotNull($meta['request_id']);
        }
    }

    /** @test */
    public function test_database_transactions_are_properly_handled()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        DB::beginTransaction();
        
        try {
            // Create attendance record
            $checkInData = $this->validGpsCoordinates;
            $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', $checkInData);
            $response->assertStatus(200);

            // Verify record exists in transaction
            $this->assertDatabaseHas('non_paramedis_attendances', [
                'user_id' => $this->nonParamedisUser->id,
                'attendance_date' => Carbon::today()
            ]);

            DB::rollBack();

            // Verify record doesn't exist after rollback
            $this->assertDatabaseMissing('non_paramedis_attendances', [
                'user_id' => $this->nonParamedisUser->id,
                'attendance_date' => Carbon::today()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** @test */
    public function test_error_responses_include_proper_structure()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        // Test validation error
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', []);
        
        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'errors',
                     'meta' => [
                         'version',
                         'timestamp',
                         'request_id'
                     ]
                 ])
                 ->assertJson([
                     'status' => 'error'
                 ]);
    }
}
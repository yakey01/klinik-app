<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\NonParamedisAttendance;
use App\Models\WorkLocation;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class NonParamedisFrontendIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private WorkLocation $workLocation;

    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::factory()->create(['name' => 'non_paramedis']);
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
            'email' => 'nonparamedis@test.com',
            'password' => bcrypt('password123')
        ]);
        
        $this->workLocation = WorkLocation::factory()->create([
            'name' => 'Main Office',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'is_active' => true,
            'location_type' => 'main_office',
            'address' => 'Jl. Test No. 123, Jakarta'
        ]);
    }

    /** @test */
    public function test_dashboard_page_loads_with_proper_data_structure()
    {
        $this->actingAs($this->user);

        $response = $this->get('/paramedis');

        $response->assertStatus(200);
        
        // Check if the page contains expected elements
        $response->assertSee('Dashboard');
        $response->assertSee($this->user->name);
        
        // Check for JavaScript variables and data attributes
        $response->assertSee('window.dashboardData');
        $response->assertSee('data-user-id="' . $this->user->id . '"', false);
    }

    /** @test */
    public function test_authentication_flow_integration()
    {
        // Test login form
        $response = $this->get('/auth/unified-login');
        $response->assertStatus(200);
        $response->assertSee('form');
        $response->assertSee('email');
        $response->assertSee('password');

        // Test login submission
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password123'
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    /** @test */
    public function test_ajax_requests_work_with_csrf_protection()
    {
        $this->actingAs($this->user);

        // Get CSRF token from session
        Session::start();
        $csrfToken = Session::token();

        // Test AJAX request with CSRF token
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', [
            'latitude' => -6.200010,
            'longitude' => 106.816676,
            'accuracy' => 5.0,
            '_token' => $csrfToken
        ]);

        // Should work with proper CSRF token
        $this->assertContains($response->status(), [200, 422]); // 422 if GPS validation fails
    }

    /** @test */
    public function test_real_time_data_updates()
    {
        $this->actingAs($this->user);

        // Create initial attendance
        $attendance = NonParamedisAttendance::factory()->create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => Carbon::today(),
            'check_in_time' => Carbon::now()->subHours(2),
            'status' => 'checked_in'
        ]);

        // Test dashboard API endpoint for real-time data
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Verify real-time data accuracy
        $this->assertEquals('checked_in', $data['current_status']);
        $this->assertNotNull($data['today_attendance']);
        $this->assertEquals($attendance->check_in_time->format('H:i'), $data['today_attendance']['check_in_time']);
    }

    /** @test */
    public function test_geolocation_integration_flow()
    {
        $this->actingAs($this->user);

        // Test attendance status endpoint (used by frontend)
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/attendance/status');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Verify location data is included for frontend
        $this->assertNotNull($data['location']);
        $this->assertEquals($this->workLocation->name, $data['location']['name']);
        $this->assertEquals($this->workLocation->latitude, $data['location']['coordinates']['latitude']);
        $this->assertEquals($this->workLocation->longitude, $data['location']['coordinates']['longitude']);
        $this->assertEquals($this->workLocation->radius_meters, $data['location']['radius']);

        // Test check-in with GPS coordinates
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', [
            'latitude' => -6.200010,
            'longitude' => 106.816676,
            'accuracy' => 5.0
        ]);

        if ($response->status() === 200) {
            $data = $response->json('data');
            $this->assertArrayHasKey('location', $data);
            $this->assertArrayHasKey('distance', $data);
        }
    }

    /** @test */
    public function test_error_state_handling()
    {
        $this->actingAs($this->user);

        // Test invalid GPS coordinates
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', [
            'latitude' => -6.300000, // Far from office
            'longitude' => 106.900000,
            'accuracy' => 5.0
        ]);

        $response->assertStatus(422);
        $data = $response->json();
        
        // Verify error structure for frontend handling
        $this->assertEquals('error', $data['status']);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('meta', $data);
    }

    /** @test */
    public function test_offline_mode_data_storage()
    {
        $this->actingAs($this->user);

        // Test if attendance status includes offline-capable data
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/attendance/status');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Should include enough data for offline operation
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('location', $data);
        $this->assertArrayHasKey('can_check_in', $data);
        $this->assertArrayHasKey('can_check_out', $data);

        // Test work locations endpoint (for offline caching)
        $response = $this->getJson('/api/v2/locations/work-locations');
        
        $response->assertStatus(200);
        $locations = $response->json('data');
        
        $this->assertNotEmpty($locations);
        foreach ($locations as $location) {
            $this->assertArrayHasKey('id', $location);
            $this->assertArrayHasKey('name', $location);
            $this->assertArrayHasKey('latitude', $location);
            $this->assertArrayHasKey('longitude', $location);
            $this->assertArrayHasKey('radius_meters', $location);
        }
    }

    /** @test */
    public function test_responsive_ui_data_structure()
    {
        $this->actingAs($this->user);

        // Test dashboard data includes mobile-friendly structure
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Verify mobile-friendly data structure
        $this->assertArrayHasKey('quick_actions', $data);
        $this->assertIsArray($data['quick_actions']);
        
        foreach ($data['quick_actions'] as $action) {
            $this->assertArrayHasKey('id', $action);
            $this->assertArrayHasKey('title', $action);
            $this->assertArrayHasKey('subtitle', $action);
            $this->assertArrayHasKey('icon', $action);
            $this->assertArrayHasKey('action', $action);
            $this->assertArrayHasKey('enabled', $action);
        }

        // Verify user data includes initials for avatar display
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('initials', $data['user']);
        $this->assertNotEmpty($data['user']['initials']);
    }

    /** @test */
    public function test_progress_indicators_and_loading_states()
    {
        $this->actingAs($this->user);

        // Create attendance data for progress calculation
        for ($day = 1; $day <= 5; $day++) {
            NonParamedisAttendance::factory()->create([
                'user_id' => $this->user->id,
                'work_location_id' => $this->workLocation->id,
                'attendance_date' => Carbon::now()->startOfMonth()->addDays($day - 1),
                'check_in_time' => Carbon::now()->startOfMonth()->addDays($day - 1)->setTime(8, 0),
                'check_out_time' => Carbon::now()->startOfMonth()->addDays($day - 1)->setTime(17, 0),
                'total_work_minutes' => 540,
                'status' => 'checked_out',
                'approval_status' => 'approved'
            ]);
        }

        // Test dashboard stats for progress indicators
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertArrayHasKey('stats', $data);
        $stats = $data['stats'];
        
        // Verify progress data is available
        $this->assertArrayHasKey('attendance_rate', $stats);
        $this->assertArrayHasKey('work_days_this_month', $stats);
        $this->assertArrayHasKey('expected_work_days', $stats);
        
        $this->assertIsNumeric($stats['attendance_rate']);
        $this->assertGreaterThanOrEqual(0, $stats['attendance_rate']);
        $this->assertLessThanOrEqual(100, $stats['attendance_rate']);
    }

    /** @test */
    public function test_date_time_formatting_for_ui()
    {
        $this->actingAs($this->user);

        // Create attendance with specific time
        $checkInTime = Carbon::today()->setTime(8, 30, 0);
        $checkOutTime = Carbon::today()->setTime(17, 45, 0);
        
        $attendance = NonParamedisAttendance::factory()->create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id,
            'attendance_date' => Carbon::today(),
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'total_work_minutes' => 555, // 9 hours 15 minutes
            'status' => 'checked_out'
        ]);

        // Test today history endpoint
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/attendance/today-history');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertArrayHasKey('history', $data);
        $history = $data['history'];
        
        // Verify time formatting
        $this->assertCount(2, $history); // Check-in and check-out
        $this->assertEquals('08:30', $history[0]['time']);
        $this->assertEquals('17:45', $history[1]['time']);

        // Verify work duration formatting
        $this->assertArrayHasKey('attendance_summary', $data);
        $summary = $data['attendance_summary'];
        $this->assertEquals('9 jam 15 menit', $summary['total_work_time']);
    }

    /** @test */
    public function test_form_validation_feedback()
    {
        $this->actingAs($this->user);

        // Test check-in with missing required fields
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', []);
        
        $response->assertStatus(422);
        $data = $response->json();
        
        // Verify validation error structure for frontend
        $this->assertEquals('error', $data['status']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('latitude', $data['errors']);
        $this->assertArrayHasKey('longitude', $data['errors']);

        // Test check-in with invalid coordinates
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', [
            'latitude' => 'invalid',
            'longitude' => 'invalid',
            'accuracy' => 'invalid'
        ]);
        
        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        // Should have field-specific error messages
        $this->assertIsArray($errors['latitude']);
        $this->assertIsArray($errors['longitude']);
    }

    /** @test */
    public function test_pagination_and_infinite_scroll_data()
    {
        $this->actingAs($this->user);

        // Create multiple attendance records
        for ($day = 1; $day <= 30; $day++) {
            NonParamedisAttendance::factory()->create([
                'user_id' => $this->user->id,
                'work_location_id' => $this->workLocation->id,
                'attendance_date' => Carbon::now()->subDays($day),
                'status' => 'checked_out'
            ]);
        }

        // Test reports endpoint with pagination
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/reports');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Verify recent history is limited for performance
        $this->assertArrayHasKey('recent_history', $data);
        $this->assertLessThanOrEqual(15, count($data['recent_history']));
        
        // Should include pagination metadata if implemented
        foreach ($data['recent_history'] as $record) {
            $this->assertArrayHasKey('date', $record);
            $this->assertArrayHasKey('check_in', $record);
            $this->assertArrayHasKey('status', $record);
        }
    }

    /** @test */
    public function test_search_and_filter_functionality()
    {
        $this->actingAs($this->user);

        // Create test data with different periods
        NonParamedisAttendance::factory()->create([
            'user_id' => $this->user->id,
            'attendance_date' => Carbon::now()->subWeeks(1),
            'status' => 'checked_out'
        ]);

        NonParamedisAttendance::factory()->create([
            'user_id' => $this->user->id,
            'attendance_date' => Carbon::now()->subMonths(1),
            'status' => 'checked_out'
        ]);

        // Test period filtering
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/reports?period=week');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('week', $data['period']['type']);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/reports?period=month');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('month', $data['period']['type']);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/reports?period=year');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals('year', $data['period']['type']);
    }

    /** @test */
    public function test_notification_and_alert_system()
    {
        $this->actingAs($this->user);

        // Test dashboard includes notification/alert data
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Check if notification-related data is included
        $this->assertArrayHasKey('current_status', $data);
        
        // Test quick actions include notification states
        $this->assertArrayHasKey('quick_actions', $data);
        foreach ($data['quick_actions'] as $action) {
            $this->assertArrayHasKey('enabled', $action);
            $this->assertIsBool($action['enabled']);
        }
    }

    /** @test */
    public function test_accessibility_data_attributes()
    {
        $this->actingAs($this->user);

        // Test dashboard provides accessibility-friendly data
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Verify accessibility data is present
        foreach ($data['quick_actions'] as $action) {
            $this->assertArrayHasKey('title', $action);
            $this->assertArrayHasKey('subtitle', $action);
            $this->assertNotEmpty($action['title']);
        }

        // Test status messages are descriptive
        $this->assertArrayHasKey('current_status', $data);
        $this->assertIsString($data['current_status']);
    }

    /** @test */
    public function test_localization_support()
    {
        $this->actingAs($this->user);

        // Test that API responses include localized content
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/attendance/today-history');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        if (!empty($data['history'])) {
            // Check for Indonesian language content
            $this->assertStringContainsString('Hari ini', $data['history'][0]['subtitle']);
        }

        // Test schedule endpoint for localized day names
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/schedule');
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        if (!empty($data['current_week']['shifts'])) {
            // Should include localized day names
            $this->assertArrayHasKey('day_name', $data['current_week']['shifts'][0]);
        }
    }

    /** @test */
    public function test_state_management_consistency()
    {
        $this->actingAs($this->user);

        // Test initial state
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/attendance/status');
        $initialState = $response->json('data');
        
        $this->assertEquals('not_checked_in', $initialState['status']);
        $this->assertTrue($initialState['can_check_in']);
        $this->assertFalse($initialState['can_check_out']);

        // Perform check-in
        $checkInResponse = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', [
            'latitude' => -6.200010,
            'longitude' => 106.816676,
            'accuracy' => 5.0
        ]);

        if ($checkInResponse->status() === 200) {
            // Test state after check-in
            $response = $this->getJson('/api/v2/dashboards/nonparamedis/attendance/status');
            $newState = $response->json('data');
            
            $this->assertEquals('checked_in', $newState['status']);
            $this->assertFalse($newState['can_check_in']);
            $this->assertTrue($newState['can_check_out']);
            $this->assertNotNull($newState['check_in_time']);
        }
    }
}
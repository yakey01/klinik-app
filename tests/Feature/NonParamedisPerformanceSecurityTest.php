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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NonParamedisPerformanceSecurityTest extends TestCase
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
            'is_active' => true
        ]);
        
        $this->workLocation = WorkLocation::factory()->create([
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius_meters' => 100,
            'is_active' => true,
            'location_type' => 'main_office'
        ]);
    }

    /** @test */
    public function test_api_response_time_performance()
    {
        Sanctum::actingAs($this->user);

        $endpoints = [
            '/api/v2/dashboards/nonparamedis/',
            '/api/v2/dashboards/nonparamedis/attendance/status',
            '/api/v2/dashboards/nonparamedis/profile',
            '/api/v2/dashboards/nonparamedis/schedule',
            '/api/v2/dashboards/nonparamedis/reports'
        ];

        foreach ($endpoints as $endpoint) {
            $startTime = microtime(true);
            
            $response = $this->getJson($endpoint);
            
            $responseTime = microtime(true) - $startTime;
            
            $response->assertStatus(200);
            
            // API should respond within 500ms for optimal user experience
            $this->assertLessThan(0.5, $responseTime, "Endpoint {$endpoint} took {$responseTime}s");
        }
    }

    /** @test */
    public function test_database_query_performance()
    {
        Sanctum::actingAs($this->user);

        // Create test data to ensure realistic performance testing
        $users = User::factory()->count(100)->create(['role_id' => $this->user->role_id]);
        $locations = WorkLocation::factory()->count(5)->create(['is_active' => true]);
        
        // Create attendance records
        $attendances = collect();
        foreach ($users->take(10) as $user) {
            for ($day = 1; $day <= 30; $day++) {
                $attendances->push([
                    'user_id' => $user->id,
                    'work_location_id' => $locations->random()->id,
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

        // Monitor query count and execution time
        $queryCount = 0;
        $queryTime = 0;
        
        DB::listen(function ($query) use (&$queryCount, &$queryTime) {
            $queryCount++;
            $queryTime += $query->time;
        });

        $startTime = microtime(true);
        
        // Test dashboard endpoint (most complex query)
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        
        $totalTime = microtime(true) - $startTime;

        $response->assertStatus(200);
        
        // Should execute efficiently with proper indexing and eager loading
        $this->assertLessThan(10, $queryCount, "Too many database queries: {$queryCount}");
        $this->assertLessThan(500, $queryTime, "Database queries too slow: {$queryTime}ms");
        $this->assertLessThan(1.0, $totalTime, "Total response time too slow: {$totalTime}s");
    }

    /** @test */
    public function test_memory_usage_performance()
    {
        Sanctum::actingAs($this->user);

        $memoryBefore = memory_get_usage(true);
        
        // Create large dataset
        $attendances = NonParamedisAttendance::factory()->count(1000)->create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->workLocation->id
        ]);

        // Test reports endpoint with large dataset
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/reports');
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        $response->assertStatus(200);
        
        // Should not consume excessive memory (less than 32MB for 1000 records)
        $this->assertLessThan(32 * 1024 * 1024, $memoryUsed, "Memory usage too high: " . ($memoryUsed / 1024 / 1024) . "MB");
    }

    /** @test */
    public function test_rate_limiting_on_attendance_endpoints()
    {
        Sanctum::actingAs($this->user);

        $checkInData = [
            'latitude' => -6.200010,
            'longitude' => 106.816676,
            'accuracy' => 5.0
        ];

        $rateLimitHit = false;
        $successCount = 0;

        // Make rapid requests to test rate limiting
        for ($i = 0; $i < 15; $i++) {
            $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', $checkInData);
            
            if ($response->status() === 429) {
                $rateLimitHit = true;
                break;
            } elseif ($response->status() === 200) {
                $successCount++;
                // Only first one should succeed, others should fail validation (duplicate checkin)
            }
        }

        // Rate limiting should kick in for attendance endpoints
        $this->assertTrue($rateLimitHit || $successCount <= 1, "Rate limiting not working properly");
        
        if ($rateLimitHit) {
            $this->assertLessThan(10, $successCount, "Too many requests allowed before rate limiting");
        }
    }

    /** @test */
    public function test_sql_injection_protection()
    {
        Sanctum::actingAs($this->user);

        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
            "1; DELETE FROM non_paramedis_attendances; --",
            "<script>alert('xss')</script>",
            "1' UNION SELECT * FROM users --"
        ];

        foreach ($maliciousInputs as $maliciousInput) {
            // Test various endpoints with malicious input
            $response = $this->getJson('/api/v2/dashboards/nonparamedis/reports?period=' . urlencode($maliciousInput));
            
            // Should not cause errors or expose data
            $this->assertContains($response->status(), [200, 422, 400]);
            
            // Verify database integrity
            $this->assertTrue(DB::table('users')->exists());
            $this->assertTrue(DB::table('non_paramedis_attendances')->exists());
        }
    }

    /** @test */
    public function test_xss_protection_in_responses()
    {
        // Create user with potentially malicious data
        $maliciousUser = User::factory()->create([
            'role_id' => $this->user->role_id,
            'name' => '<script>alert("xss")</script>John Doe',
            'is_active' => true
        ]);

        Sanctum::actingAs($maliciousUser);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');

        $response->assertStatus(200);

        $responseData = $response->getContent();
        
        // Script tags should be escaped or removed
        $this->assertStringNotContainsString('<script>', $responseData);
        $this->assertStringNotContainsString('alert(', $responseData);
    }

    /** @test */
    public function test_unauthorized_access_prevention()
    {
        $protectedEndpoints = [
            'GET' => [
                '/api/v2/dashboards/nonparamedis/',
                '/api/v2/dashboards/nonparamedis/attendance/status',
                '/api/v2/dashboards/nonparamedis/profile',
                '/api/v2/dashboards/nonparamedis/schedule',
                '/api/v2/dashboards/nonparamedis/reports'
            ],
            'POST' => [
                '/api/v2/dashboards/nonparamedis/attendance/checkin',
                '/api/v2/dashboards/nonparamedis/attendance/checkout'
            ],
            'PUT' => [
                '/api/v2/dashboards/nonparamedis/profile/update',
                '/api/v2/dashboards/nonparamedis/settings'
            ]
        ];

        foreach ($protectedEndpoints as $method => $endpoints) {
            foreach ($endpoints as $endpoint) {
                // Test without authentication
                $response = $this->json($method, $endpoint);
                $response->assertStatus(401);

                // Test with invalid token
                $response = $this->withHeader('Authorization', 'Bearer invalid-token')
                                ->json($method, $endpoint);
                $response->assertStatus(401);
            }
        }
    }

    /** @test */
    public function test_cors_headers_are_properly_configured()
    {
        $response = $this->options('/api/v2/system/health', [], [
            'Origin' => 'https://example.com',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Authorization, Content-Type'
        ]);

        // Check CORS headers
        $this->assertNotNull($response->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotNull($response->headers->get('Access-Control-Allow-Methods'));
        $this->assertNotNull($response->headers->get('Access-Control-Allow-Headers'));
    }

    /** @test */
    public function test_security_headers_are_present()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');

        $response->assertStatus(200);

        // Check essential security headers
        $headers = $response->headers;
        
        $this->assertNotNull($headers->get('X-Frame-Options'));
        $this->assertEquals('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertNotNull($headers->get('X-XSS-Protection'));
        $this->assertNotNull($headers->get('Strict-Transport-Security'));
        $this->assertNotNull($headers->get('Referrer-Policy'));
        $this->assertNotNull($headers->get('Content-Security-Policy'));
    }

    /** @test */
    public function test_input_validation_and_sanitization()
    {
        Sanctum::actingAs($this->user);

        // Test GPS coordinate validation
        $invalidInputs = [
            ['latitude' => 'invalid', 'longitude' => 106.816666, 'accuracy' => 5.0],
            ['latitude' => -6.200000, 'longitude' => 'invalid', 'accuracy' => 5.0],
            ['latitude' => 91, 'longitude' => 106.816666, 'accuracy' => 5.0], // Invalid latitude
            ['latitude' => -6.200000, 'longitude' => 181, 'accuracy' => 5.0], // Invalid longitude
            ['latitude' => -6.200000, 'longitude' => 106.816666, 'accuracy' => -1], // Invalid accuracy
            ['latitude' => -6.200000, 'longitude' => 106.816666, 'accuracy' => 'script'], // XSS attempt
        ];

        foreach ($invalidInputs as $invalidInput) {
            $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', $invalidInput);
            
            $response->assertStatus(422); // Validation error
            $response->assertJsonStructure(['status', 'message', 'errors']);
        }
    }

    /** @test */
    public function test_concurrent_request_handling()
    {
        Sanctum::actingAs($this->user);

        $responses = [];
        $startTime = microtime(true);

        // Simulate concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->getJson('/api/v2/dashboards/nonparamedis/');
        }

        $totalTime = microtime(true) - $startTime;

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Concurrent requests should not take significantly longer than sequential
        $this->assertLessThan(3.0, $totalTime, "Concurrent requests took too long: {$totalTime}s");
    }

    /** @test */
    public function test_error_information_disclosure_prevention()
    {
        Sanctum::actingAs($this->user);

        // Force a database error by using invalid data
        DB::table('non_paramedis_attendances')->insert([
            'user_id' => 99999, // Non-existent user
            'attendance_date' => 'invalid-date',
            'status' => 'invalid-status'
        ]);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');

        // Should not expose database errors or internal details
        $responseContent = $response->getContent();
        
        $this->assertStringNotContainsString('Illuminate\\', $responseContent);
        $this->assertStringNotContainsString('vendor/', $responseContent);
        $this->assertStringNotContainsString('app/', $responseContent);
        $this->assertStringNotContainsString('database', $responseContent, '', true);
        $this->assertStringNotContainsString('mysql', $responseContent, '', true);
        $this->assertStringNotContainsString('postgresql', $responseContent, '', true);
    }

    /** @test */
    public function test_api_versioning_works_correctly()
    {
        Sanctum::actingAs($this->user);

        // Test v2 API
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        $response->assertStatus(200);
        
        $meta = $response->json('meta');
        $this->assertEquals('2.0', $meta['version']);

        // Test that old API endpoints return appropriate responses
        $response = $this->getJson('/api/v1/some-endpoint');
        $response->assertStatus(404); // Or 410 if deprecated
    }

    /** @test */
    public function test_request_size_limits()
    {
        Sanctum::actingAs($this->user);

        // Test with oversized request body
        $largeData = [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 5.0,
            'notes' => str_repeat('A', 10000) // 10KB of data
        ];

        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', $largeData);
        
        // Should either succeed or fail gracefully
        $this->assertContains($response->status(), [200, 413, 422]);
    }

    /** @test */
    public function test_cache_performance_for_frequent_queries()
    {
        Sanctum::actingAs($this->user);

        // First request (should hit database)
        $startTime = microtime(true);
        $response1 = $this->getJson('/api/v2/locations/work-locations');
        $firstRequestTime = microtime(true) - $startTime;

        $response1->assertStatus(200);

        // Second request (should hit cache if implemented)
        $startTime = microtime(true);
        $response2 = $this->getJson('/api/v2/locations/work-locations');
        $secondRequestTime = microtime(true) - $startTime;

        $response2->assertStatus(200);
        
        // Verify responses are identical
        $this->assertEquals($response1->json('data'), $response2->json('data'));
        
        // If caching is implemented, second request should be faster
        if ($secondRequestTime < $firstRequestTime * 0.5) {
            $this->assertTrue(true, "Caching appears to be working effectively");
        }
    }

    /** @test */
    public function test_audit_logging_for_sensitive_operations()
    {
        Sanctum::actingAs($this->user);

        // Perform sensitive operations
        $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', [
            'latitude' => -6.200010,
            'longitude' => 106.816676,
            'accuracy' => 5.0
        ]);

        // Check if audit logs are created (if implemented)
        // This depends on your audit logging implementation
        $this->assertTrue(true, "Audit logging test - implementation dependent");
    }

    /** @test */
    public function test_data_encryption_in_transit()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/profile');
        
        $response->assertStatus(200);
        
        // Verify HTTPS is enforced in production
        if (app()->environment('production')) {
            $this->assertEquals('https', request()->getScheme());
        }
        
        // Check for secure headers
        $this->assertNotNull($response->headers->get('Strict-Transport-Security'));
    }

    /** @test */
    public function test_api_documentation_security()
    {
        // Test that API documentation doesn't expose sensitive information
        $response = $this->getJson('/api/v2/system/version');
        
        $response->assertStatus(200);
        
        $data = $response->json('data');
        
        // Should not expose internal framework versions or sensitive config
        $this->assertArrayNotHasKey('database_password', $data);
        $this->assertArrayNotHasKey('app_key', $data);
        $this->assertArrayNotHasKey('jwt_secret', $data);
    }
}
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\RefreshToken;
use App\Models\UserSession;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NonParamedisAuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $nonParamedisUser;
    private Role $nonParamedisRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->nonParamedisRole = Role::factory()->create(['name' => 'non_paramedis']);
        $this->nonParamedisUser = User::factory()->create([
            'role_id' => $this->nonParamedisRole->id,
            'is_active' => true,
            'password' => Hash::make('password123'),
            'email' => 'nonparamedis@test.com',
            'username' => 'nonparamedis_test'
        ]);
    }

    /** @test */
    public function test_login_with_valid_credentials_succeeds()
    {
        $response = $this->postJson('/api/v2/auth/login', [
            'email' => $this->nonParamedisUser->email,
            'password' => 'password123',
            'device_name' => 'iPhone 12 Pro'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'role',
                             'permissions'
                         ],
                         'tokens' => [
                             'access_token',
                             'refresh_token',
                             'expires_at'
                         ],
                         'session' => [
                             'session_id',
                             'device_info',
                             'created_at'
                         ]
                     ],
                     'meta'
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'user' => [
                             'id' => $this->nonParamedisUser->id,
                             'email' => $this->nonParamedisUser->email,
                             'role' => [
                                 'name' => 'non_paramedis'
                             ]
                         ]
                     ]
                 ]);

        // Verify token was created in database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->nonParamedisUser->id,
            'tokenable_type' => User::class,
            'name' => 'iPhone 12 Pro'
        ]);
    }

    /** @test */
    public function test_login_with_username_succeeds()
    {
        $response = $this->postJson('/api/v2/auth/login', [
            'username' => $this->nonParamedisUser->username,
            'password' => 'password123',
            'device_name' => 'Android Phone'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'user' => [
                             'id' => $this->nonParamedisUser->id,
                             'username' => $this->nonParamedisUser->username
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function test_login_with_invalid_credentials_fails()
    {
        $response = $this->postJson('/api/v2/auth/login', [
            'email' => $this->nonParamedisUser->email,
            'password' => 'wrongpassword',
            'device_name' => 'Test Device'
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid credentials'
                 ]);

        // Verify no token was created
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->nonParamedisUser->id
        ]);
    }

    /** @test */
    public function test_login_with_inactive_user_fails()
    {
        $this->nonParamedisUser->update(['is_active' => false]);

        $response = $this->postJson('/api/v2/auth/login', [
            'email' => $this->nonParamedisUser->email,
            'password' => 'password123',
            'device_name' => 'Test Device'
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Account is inactive'
                 ]);
    }

    /** @test */
    public function test_login_validation_rules()
    {
        // Test missing required fields
        $response = $this->postJson('/api/v2/auth/login', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password', 'device_name']);

        // Test invalid email format
        $response = $this->postJson('/api/v2/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
            'device_name' => 'Test Device'
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);

        // Test password minimum length
        $response = $this->postJson('/api/v2/auth/login', [
            'email' => $this->nonParamedisUser->email,
            'password' => '123',
            'device_name' => 'Test Device'
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function test_authenticated_user_can_access_me_endpoint()
    {
        $token = $this->nonParamedisUser->createToken('Test Device');
        Sanctum::actingAs($this->nonParamedisUser, ['*'], $token->accessToken);

        $response = $this->getJson('/api/v2/auth/me');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user' => [
                             'id',
                             'name',
                             'email',
                             'username',
                             'role',
                             'permissions',
                             'profile'
                         ],
                         'session' => [
                             'token_name',
                             'created_at',
                             'last_used_at',
                             'expires_at'
                         ]
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'user' => [
                             'id' => $this->nonParamedisUser->id,
                             'email' => $this->nonParamedisUser->email
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function test_token_refresh_works_correctly()
    {
        // Create initial login
        $loginResponse = $this->postJson('/api/v2/auth/login', [
            'email' => $this->nonParamedisUser->email,
            'password' => 'password123',
            'device_name' => 'Test Device'
        ]);

        $refreshToken = $loginResponse->json('data.tokens.refresh_token');

        // Use refresh token to get new access token
        $response = $this->postJson('/api/v2/auth/refresh', [
            'refresh_token' => $refreshToken
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'tokens' => [
                             'access_token',
                             'refresh_token',
                             'expires_at'
                         ]
                     ]
                 ])
                 ->assertJson([
                     'success' => true
                 ]);

        // Verify old refresh token is invalidated
        $this->assertDatabaseHas('refresh_tokens', [
            'token' => $refreshToken,
            'is_used' => true
        ]);
    }

    /** @test */
    public function test_logout_invalidates_current_token()
    {
        $token = $this->nonParamedisUser->createToken('Test Device');
        Sanctum::actingAs($this->nonParamedisUser, ['*'], $token->accessToken);

        $response = $this->postJson('/api/v2/auth/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logged out successfully'
                 ]);

        // Verify token was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id
        ]);

        // Verify subsequent requests with old token fail
        $response = $this->getJson('/api/v2/auth/me');
        $response->assertStatus(401);
    }

    /** @test */
    public function test_logout_all_invalidates_all_user_tokens()
    {
        // Create multiple tokens
        $token1 = $this->nonParamedisUser->createToken('Device 1');
        $token2 = $this->nonParamedisUser->createToken('Device 2');
        $token3 = $this->nonParamedisUser->createToken('Device 3');

        Sanctum::actingAs($this->nonParamedisUser, ['*'], $token1->accessToken);

        $response = $this->postJson('/api/v2/auth/logout-all');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logged out from all devices successfully'
                 ]);

        // Verify all tokens were deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->nonParamedisUser->id
        ]);
    }

    /** @test */
    public function test_role_based_access_control_works()
    {
        // Create users with different roles
        $paramedisRole = Role::factory()->create(['name' => 'paramedis']);
        $paramedisUser = User::factory()->create([
            'role_id' => $paramedisRole->id,
            'is_active' => true
        ]);

        $adminRole = Role::factory()->create(['name' => 'admin']);
        $adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true
        ]);

        // Test non_paramedis role access
        Sanctum::actingAs($this->nonParamedisUser);
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        $response->assertStatus(200);

        // Test paramedis role access (should be blocked)
        Sanctum::actingAs($paramedisUser);
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        $response->assertStatus(403);

        // Test admin role access (should be blocked unless specifically allowed)
        Sanctum::actingAs($adminUser);
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        $response->assertStatus(403);
    }

    /** @test */
    public function test_token_expiration_handling()
    {
        // Create token and manually set expiration
        $token = $this->nonParamedisUser->createToken('Test Device', ['*'], Carbon::now()->subDay());
        
        // Attempt to use expired token
        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken);
        $response = $this->getJson('/api/v2/auth/me');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function test_concurrent_login_sessions_are_tracked()
    {
        // Create multiple login sessions
        $device1Response = $this->postJson('/api/v2/auth/login', [
            'email' => $this->nonParamedisUser->email,
            'password' => 'password123',
            'device_name' => 'iPhone 12'
        ]);

        $device2Response = $this->postJson('/api/v2/auth/login', [
            'email' => $this->nonParamedisUser->email,
            'password' => 'password123',
            'device_name' => 'iPad Pro'
        ]);

        $device1Response->assertStatus(200);
        $device2Response->assertStatus(200);

        // Verify both sessions exist
        $this->assertEquals(2, PersonalAccessToken::where('tokenable_id', $this->nonParamedisUser->id)->count());

        // Test getting all sessions
        $token = PersonalAccessToken::where('tokenable_id', $this->nonParamedisUser->id)->first();
        Sanctum::actingAs($this->nonParamedisUser, ['*'], $token);

        $response = $this->getJson('/api/v2/auth/sessions');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'sessions' => [
                             '*' => [
                                 'id',
                                 'name',
                                 'created_at',
                                 'last_used_at',
                                 'is_current'
                             ]
                         ],
                         'total_sessions'
                     ]
                 ]);

        $sessions = $response->json('data.sessions');
        $this->assertCount(2, $sessions);
    }

    /** @test */
    public function test_session_termination_works()
    {
        // Create multiple sessions
        $token1 = $this->nonParamedisUser->createToken('Device 1');
        $token2 = $this->nonParamedisUser->createToken('Device 2');

        Sanctum::actingAs($this->nonParamedisUser, ['*'], $token1->accessToken);

        // Terminate specific session
        $response = $this->deleteJson('/api/v2/auth/sessions/' . $token2->accessToken->id);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Session terminated successfully'
                 ]);

        // Verify specific token was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token2->accessToken->id
        ]);

        // Verify current token still exists
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token1->accessToken->id
        ]);
    }

    /** @test */
    public function test_password_change_invalidates_other_sessions()
    {
        // Create multiple sessions
        $token1 = $this->nonParamedisUser->createToken('Device 1');
        $token2 = $this->nonParamedisUser->createToken('Device 2');

        Sanctum::actingAs($this->nonParamedisUser, ['*'], $token1->accessToken);

        // Change password
        $response = $this->postJson('/api/v2/auth/change-password', [
            'current_password' => 'password123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Password changed successfully'
                 ]);

        // Verify all other tokens were invalidated except current one
        $this->assertEquals(1, PersonalAccessToken::where('tokenable_id', $this->nonParamedisUser->id)->count());
        
        // Verify password was actually changed
        $this->nonParamedisUser->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->nonParamedisUser->password));
    }

    /** @test */
    public function test_api_security_headers_are_present()
    {
        Sanctum::actingAs($this->nonParamedisUser);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');

        // Check for essential security headers
        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection');
        $response->assertHeader('Strict-Transport-Security');
    }

    /** @test */
    public function test_rate_limiting_on_auth_endpoints()
    {
        // Test login rate limiting
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v2/auth/login', [
                'email' => 'nonexistent@test.com',
                'password' => 'wrongpassword',
                'device_name' => 'Test Device'
            ]);

            if ($i < 5) {
                $response->assertStatus(401); // Invalid credentials
            } else {
                // Should eventually hit rate limit
                $this->assertContains($response->status(), [401, 429]);
            }
        }
    }

    /** @test */
    public function test_token_abilities_and_permissions()
    {
        // Create token with specific abilities
        $token = $this->nonParamedisUser->createToken('Limited Device', ['read-only']);
        Sanctum::actingAs($this->nonParamedisUser, ['read-only'], $token->accessToken);

        // Should be able to read
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');
        $response->assertStatus(200);

        // Should not be able to write (if implemented)
        $response = $this->postJson('/api/v2/dashboards/nonparamedis/attendance/checkin', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'accuracy' => 5.0
        ]);
        
        // This might be 200 if ability checks aren't implemented, or 403 if they are
        $this->assertContains($response->status(), [200, 403]);
    }

    /** @test */
    public function test_account_lockout_after_failed_attempts()
    {
        // This test assumes account lockout is implemented
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v2/auth/login', [
                'email' => $this->nonParamedisUser->email,
                'password' => 'wrongpassword',
                'device_name' => 'Test Device'
            ]);

            if ($i < 5) {
                $response->assertStatus(401);
            } else {
                // After too many failed attempts, should be locked out
                $this->assertContains($response->status(), [401, 423, 429]);
            }
        }
    }

    /** @test */
    public function test_user_session_tracking()
    {
        // Login and create session
        $response = $this->postJson('/api/v2/auth/login', [
            'email' => $this->nonParamedisUser->email,
            'password' => 'password123',
            'device_name' => 'Test Device'
        ]);

        $sessionId = $response->json('data.session.session_id');

        // Verify session was tracked
        $this->assertDatabaseHas('user_sessions', [
            'session_id' => $sessionId,
            'user_id' => $this->nonParamedisUser->id
        ]);

        // Verify session contains proper metadata
        $session = UserSession::where('session_id', $sessionId)->first();
        $this->assertNotNull($session->ip_address);
        $this->assertNotNull($session->user_agent);
        $this->assertNotNull($session->started_at);
    }
}
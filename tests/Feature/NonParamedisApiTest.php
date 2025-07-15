<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class NonParamedisApiTest extends TestCase
{
    use RefreshDatabase;

    private function createNonParamedisUser(): User
    {
        $role = Role::factory()->create(['name' => 'non_paramedis']);
        return User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_non_paramedis_can_access_dashboard()
    {
        $user = $this->createNonParamedisUser();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'user',
                         'stats',
                         'current_status',
                         'quick_actions'
                     ]
                 ]);
    }

    public function test_non_paramedis_can_access_test_endpoint()
    {
        $user = $this->createNonParamedisUser();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/test');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'timestamp',
                         'user',
                         'session'
                     ]
                 ]);
    }

    public function test_non_paramedis_can_get_attendance_status()
    {
        $user = $this->createNonParamedisUser();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/attendance/status');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data' => [
                         'status',
                         'can_check_in',
                         'can_check_out'
                     ]
                 ]);
    }

    public function test_unauthorized_user_cannot_access_dashboard()
    {
        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');

        $response->assertStatus(401);
    }

    public function test_wrong_role_cannot_access_dashboard()
    {
        $role = Role::factory()->create(['name' => 'paramedis']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v2/dashboards/nonparamedis/');

        $response->assertStatus(403);
    }

    public function test_system_health_endpoint_is_public()
    {
        $response = $this->getJson('/api/v2/system/health');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'status',
                         'version',
                         'timestamp'
                     ]
                 ]);
    }

    public function test_work_locations_endpoint_is_public()
    {
        $response = $this->getJson('/api/v2/locations/work-locations');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ]);
    }
}
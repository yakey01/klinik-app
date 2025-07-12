<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_can_be_assigned_role()
    {
        $user = User::factory()->create();
        $role = Role::where('name', 'admin')->first();
        
        $user->assignRole('admin');
        
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole($role));
    }

    public function test_user_inherits_permissions_from_role()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $this->assertTrue($user->can('view-users'));
        $this->assertTrue($user->can('create-users'));
        $this->assertTrue($user->can('manage-roles'));
    }

    public function test_role_specific_permissions()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $doctor = User::factory()->create();
        $doctor->assignRole('dokter');
        
        $treasurer = User::factory()->create();
        $treasurer->assignRole('bendahara');
        
        // Admin should have all permissions
        $this->assertTrue($admin->can('view-users'));
        $this->assertTrue($admin->can('manage-roles'));
        $this->assertTrue($admin->can('approve-finances'));
        
        // Doctor should have medical permissions but not admin permissions
        $this->assertTrue($doctor->can('view-patients'));
        $this->assertTrue($doctor->can('perform-procedures'));
        $this->assertFalse($doctor->can('manage-roles'));
        $this->assertFalse($doctor->can('approve-finances'));
        
        // Treasurer should have financial permissions
        $this->assertTrue($treasurer->can('view-finances'));
        $this->assertTrue($treasurer->can('create-finances'));
        $this->assertFalse($treasurer->can('manage-roles'));
        $this->assertFalse($treasurer->can('perform-procedures'));
    }

    public function test_middleware_blocks_unauthorized_access()
    {
        $user = User::factory()->create();
        $user->assignRole('dokter');
        
        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_middleware_allows_authorized_access()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertStatus(200);
    }

    public function test_dashboard_redirects_based_on_role()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $doctor = User::factory()->create();
        $doctor->assignRole('dokter');
        
        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect('/admin/dashboard');
            
        $this->actingAs($doctor)
            ->get('/dashboard')
            ->assertRedirect('/doctor/dashboard');
    }

    public function test_user_can_have_multiple_roles()
    {
        $user = User::factory()->create();
        $user->assignRole(['dokter', 'manajer']);
        
        $this->assertTrue($user->hasRole('dokter'));
        $this->assertTrue($user->hasRole('manajer'));
        $this->assertTrue($user->can('perform-procedures'));
        $this->assertTrue($user->can('approve-finances'));
    }

    public function test_permissions_are_cached()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        // First call should cache permissions
        $this->assertTrue($user->can('view-users'));
        
        // Second call should use cached permissions
        $this->assertTrue($user->can('view-users'));
    }
}
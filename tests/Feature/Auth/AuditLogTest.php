<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_user_creation_is_logged()
    {
        $user = User::factory()->create();
        
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }

    public function test_user_update_is_logged()
    {
        $user = User::factory()->create(['name' => 'Original Name']);
        
        // Clear existing logs
        AuditLog::truncate();
        
        $user->update(['name' => 'Updated Name']);
        
        $log = AuditLog::where('action', 'updated')
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->first();
            
        $this->assertNotNull($log);
        $this->assertEquals('Original Name', $log->old_values['name']);
        $this->assertEquals('Updated Name', $log->new_values['name']);
    }

    public function test_user_deletion_is_logged()
    {
        $user = User::factory()->create();
        $userId = $user->id;
        
        // Clear existing logs
        AuditLog::truncate();
        
        $user->delete();
        
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'model_type' => User::class,
            'model_id' => $userId,
        ]);
    }

    public function test_login_is_logged()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);
    }

    public function test_logout_is_logged()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $this->actingAs($user);
        $this->post('/logout');
        
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'logout',
        ]);
    }

    public function test_failed_login_is_logged()
    {
        $user = User::factory()->create();
        
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => null,
            'action' => 'login_failed',
        ]);
        
        $log = AuditLog::where('action', 'login_failed')->first();
        $this->assertEquals($user->email, $log->new_values['email']);
    }

    public function test_audit_log_captures_request_information()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        $this->actingAs($user)
            ->withHeaders([
                'User-Agent' => 'Test Browser',
                'HTTP_X_FORWARDED_FOR' => '127.0.0.1',
            ])
            ->post('/logout');
        
        $log = AuditLog::where('action', 'logout')->first();
        
        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->user_agent);
        $this->assertNotNull($log->url);
        $this->assertNotNull($log->method);
    }

    public function test_audit_log_manual_logging()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        AuditLog::log('custom_action', $user, ['old' => 'value'], ['new' => 'value']);
        
        $log = AuditLog::where('action', 'custom_action')->first();
        
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals(User::class, $log->model_type);
        $this->assertEquals($user->id, $log->model_id);
        $this->assertEquals(['old' => 'value'], $log->old_values);
        $this->assertEquals(['new' => 'value'], $log->new_values);
    }

    public function test_audit_log_relationship_with_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        AuditLog::log('test_action', $user);
        
        $log = AuditLog::where('action', 'test_action')->first();
        
        $this->assertEquals($user->id, $log->user->id);
        $this->assertEquals($user->name, $log->user->name);
    }

    public function test_audit_log_model_retrieval()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        AuditLog::log('test_action', $user);
        
        $log = AuditLog::where('action', 'test_action')->first();
        $retrievedModel = $log->model();
        
        $this->assertEquals($user->id, $retrievedModel->id);
        $this->assertEquals($user->name, $retrievedModel->name);
    }
}

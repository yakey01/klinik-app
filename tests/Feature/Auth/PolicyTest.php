<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_admin_can_manage_all_resources()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $patient = Pasien::factory()->create();
        $procedure = Tindakan::factory()->create();
        $income = Pendapatan::factory()->create();
        
        $this->assertTrue($admin->can('view', $patient));
        $this->assertTrue($admin->can('create', Pasien::class));
        $this->assertTrue($admin->can('update', $patient));
        $this->assertTrue($admin->can('delete', $patient));
        
        $this->assertTrue($admin->can('view', $procedure));
        $this->assertTrue($admin->can('create', Tindakan::class));
        $this->assertTrue($admin->can('update', $procedure));
        $this->assertTrue($admin->can('delete', $procedure));
        
        $this->assertTrue($admin->can('view', $income));
        $this->assertTrue($admin->can('create', Pendapatan::class));
        $this->assertTrue($admin->can('update', $income));
        $this->assertTrue($admin->can('delete', $income));
    }

    public function test_doctor_can_manage_patients_and_procedures()
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('dokter');
        
        $patient = Pasien::factory()->create();
        $procedure = Tindakan::factory()->create(['dokter_id' => $doctor->id]);
        
        $this->assertTrue($doctor->can('view', $patient));
        $this->assertTrue($doctor->can('create', Pasien::class));
        $this->assertTrue($doctor->can('update', $patient));
        $this->assertFalse($doctor->can('delete', $patient));
        
        $this->assertTrue($doctor->can('view', $procedure));
        $this->assertTrue($doctor->can('create', Tindakan::class));
        $this->assertTrue($doctor->can('update', $procedure));
        $this->assertTrue($doctor->can('perform', Tindakan::class));
    }

    public function test_treasurer_can_manage_finances()
    {
        $treasurer = User::factory()->create();
        $treasurer->assignRole('bendahara');
        
        $income = Pendapatan::factory()->create(['input_by' => $treasurer->id]);
        
        $this->assertTrue($treasurer->can('view', $income));
        $this->assertTrue($treasurer->can('create', Pendapatan::class));
        $this->assertTrue($treasurer->can('update', $income));
        $this->assertFalse($treasurer->can('delete', $income));
    }

    public function test_user_can_only_edit_own_created_records()
    {
        $user1 = User::factory()->create();
        $user1->assignRole('petugas');
        
        $user2 = User::factory()->create();
        $user2->assignRole('petugas');
        
        $income1 = Pendapatan::factory()->create(['input_by' => $user1->id]);
        $income2 = Pendapatan::factory()->create(['input_by' => $user2->id]);
        
        $this->assertTrue($user1->can('update', $income1));
        $this->assertFalse($user1->can('update', $income2));
        
        $this->assertTrue($user2->can('update', $income2));
        $this->assertFalse($user2->can('update', $income1));
    }

    public function test_procedure_policy_checks_involvement()
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('dokter');
        
        $paramedic = User::factory()->create();
        $paramedic->assignRole('paramedis');
        
        $other_user = User::factory()->create();
        $other_user->assignRole('petugas');
        
        $procedure = Tindakan::factory()->create([
            'dokter_id' => $doctor->id,
            'paramedis_id' => $paramedic->id,
            'input_by' => $other_user->id
        ]);
        
        // All involved users should be able to update
        $this->assertTrue($doctor->can('update', $procedure));
        $this->assertTrue($paramedic->can('update', $procedure));
        $this->assertTrue($other_user->can('update', $procedure));
        
        // Non-involved user should not be able to update
        $uninvolved_user = User::factory()->create();
        $uninvolved_user->assignRole('petugas');
        $this->assertFalse($uninvolved_user->can('update', $procedure));
    }

    public function test_manager_can_approve_transactions()
    {
        $manager = User::factory()->create();
        $manager->assignRole('manajer');
        
        $pending_income = Pendapatan::factory()->create(['status' => 'pending']);
        $approved_income = Pendapatan::factory()->create(['status' => 'approved']);
        
        $this->assertTrue($manager->can('approve', $pending_income));
        $this->assertTrue($manager->can('reject', $pending_income));
        $this->assertFalse($manager->can('approve', $approved_income));
        $this->assertFalse($manager->can('reject', $approved_income));
    }

    public function test_user_cannot_delete_themselves()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $other_user = User::factory()->create();
        $other_user->assignRole('petugas');
        
        $this->assertFalse($admin->can('delete', $admin));
        $this->assertTrue($admin->can('delete', $other_user));
    }

    public function test_force_delete_only_for_admin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $manager = User::factory()->create();
        $manager->assignRole('manajer');
        
        $patient = Pasien::factory()->create();
        
        $this->assertTrue($admin->can('forceDelete', $patient));
        $this->assertFalse($manager->can('forceDelete', $patient));
    }
}
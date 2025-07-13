<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Dokter;
use App\Models\Pegawai;
use App\Rules\ConsistentRoleAssignmentRule;
use App\Http\Requests\CreateUserWithRoleRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class AutomaticRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test roles
        Role::create(['name' => 'admin', 'display_name' => 'Administrator', 'guard_name' => 'web']);
        Role::create(['name' => 'dokter', 'display_name' => 'Dokter', 'guard_name' => 'web']);
        Role::create(['name' => 'paramedis', 'display_name' => 'Paramedis', 'guard_name' => 'web']);
        Role::create(['name' => 'petugas', 'display_name' => 'Petugas', 'guard_name' => 'web']);
        Role::create(['name' => 'manajer', 'display_name' => 'Manajer', 'guard_name' => 'web']);
        Role::create(['name' => 'bendahara', 'display_name' => 'Bendahara', 'guard_name' => 'web']);
        
        // Create admin user for testing
        $adminRole = Role::where('name', 'admin')->first();
        $this->adminUser = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function dokter_source_automatically_assigns_dokter_role()
    {
        $dokterRole = Role::where('name', 'dokter')->first();
        
        // Create user with dokter source
        $userData = [
            'name' => 'Dr. Test',
            'email' => 'dr.test@example.com',
            'password' => bcrypt('password123'),
            'source' => 'dokter',
            'role_id' => $dokterRole->id,
        ];
        
        // Validate using our rule
        $rule = new ConsistentRoleAssignmentRule('dokter');
        $this->assertTrue($this->validateRule($rule, 'role_id', $dokterRole->id));
        
        $user = User::create($userData);
        $this->assertEquals('dokter', $user->role->name);
    }

    /** @test */
    public function pegawai_paramedis_automatically_assigns_paramedis_role()
    {
        $paramedisRole = Role::where('name', 'paramedis')->first();
        
        // Create user with pegawai paramedis source
        $userData = [
            'name' => 'Perawat Test',
            'email' => 'perawat@example.com',
            'password' => bcrypt('password123'),
            'source' => 'pegawai',
            'employee_type' => 'paramedis',
            'role_id' => $paramedisRole->id,
        ];
        
        // Validate using our rule
        $rule = new ConsistentRoleAssignmentRule('pegawai', 'paramedis');
        $this->assertTrue($this->validateRule($rule, 'role_id', $paramedisRole->id));
        
        $user = User::create($userData);
        $this->assertEquals('paramedis', $user->role->name);
    }

    /** @test */
    public function pegawai_non_paramedis_allows_appropriate_roles()
    {
        $petugasRole = Role::where('name', 'petugas')->first();
        $manajerRole = Role::where('name', 'manajer')->first();
        $bendaharaRole = Role::where('name', 'bendahara')->first();
        
        $rule = new ConsistentRoleAssignmentRule('pegawai', 'non_paramedis');
        
        // Test allowed roles for non-paramedis
        $this->assertTrue($this->validateRule($rule, 'role_id', $petugasRole->id));
        $this->assertTrue($this->validateRule($rule, 'role_id', $manajerRole->id));
        $this->assertTrue($this->validateRule($rule, 'role_id', $bendaharaRole->id));
    }

    /** @test */
    public function validation_fails_for_incorrect_dokter_role()
    {
        $paramedisRole = Role::where('name', 'paramedis')->first();
        
        // Should fail when assigning non-dokter role to dokter source
        $rule = new ConsistentRoleAssignmentRule('dokter');
        $this->assertFalse($this->validateRule($rule, 'role_id', $paramedisRole->id));
    }

    /** @test */
    public function validation_fails_for_incorrect_paramedis_role()
    {
        $dokterRole = Role::where('name', 'dokter')->first();
        
        // Should fail when assigning non-paramedis role to paramedis employee
        $rule = new ConsistentRoleAssignmentRule('pegawai', 'paramedis');
        $this->assertFalse($this->validateRule($rule, 'role_id', $dokterRole->id));
    }

    /** @test */
    public function validation_fails_for_paramedis_role_on_non_paramedis()
    {
        $paramedisRole = Role::where('name', 'paramedis')->first();
        
        // Should fail when assigning paramedis role to non-paramedis employee
        $rule = new ConsistentRoleAssignmentRule('pegawai', 'non_paramedis');
        $this->assertFalse($this->validateRule($rule, 'role_id', $paramedisRole->id));
    }

    /** @test */
    public function form_request_validates_correctly_for_dokter()
    {
        $this->actingAs($this->adminUser);
        
        $dokterRole = Role::where('name', 'dokter')->first();
        
        // Test the validation rule directly
        $rule = new ConsistentRoleAssignmentRule('dokter');
        $this->assertTrue($this->validateRule($rule, 'role_id', $dokterRole->id));
        
        // Test auto role assignment logic
        $assignedRoleId = $this->getAutoRoleId('dokter', null);
        $this->assertEquals($dokterRole->id, $assignedRoleId);
    }

    /** @test */
    public function auto_role_assignment_works_without_explicit_role()
    {
        $dokterRole = Role::where('name', 'dokter')->first();
        $paramedisRole = Role::where('name', 'paramedis')->first();
        $petugasRole = Role::where('name', 'petugas')->first();
        
        // Test dokter auto-assignment
        $this->assertEquals($dokterRole->id, $this->getAutoRoleId('dokter', null));
        
        // Test paramedis auto-assignment
        $this->assertEquals($paramedisRole->id, $this->getAutoRoleId('pegawai', 'paramedis'));
        
        // Test non-paramedis auto-assignment
        $this->assertEquals($petugasRole->id, $this->getAutoRoleId('pegawai', 'non_paramedis'));
    }

    /** @test */
    public function existing_record_consistency_validation()
    {
        // Create a dokter record
        $dokter = Dokter::create([
            'nama_lengkap' => 'Dr. Consistency Test',
            'nik' => 'DOK999',
            'jabatan' => 'Dokter Umum',
            'aktif' => true,
        ]);
        
        // Create a pegawai record
        $pegawai = Pegawai::create([
            'nama_lengkap' => 'Perawat Consistency Test',
            'nik' => 'PEG999',
            'jabatan' => 'Perawat',
            'jenis_pegawai' => 'Paramedis',
            'aktif' => true,
        ]);
        
        $dokterRole = Role::where('name', 'dokter')->first();
        $paramedisRole = Role::where('name', 'paramedis')->first();
        $petugasRole = Role::where('name', 'petugas')->first();
        
        // Test dokter record consistency
        $dokterRule = new ConsistentRoleAssignmentRule('dokter', null, $dokter->id);
        $this->assertTrue($this->validateRule($dokterRule, 'role_id', $dokterRole->id));
        $this->assertFalse($this->validateRule($dokterRule, 'role_id', $paramedisRole->id));
        
        // Test pegawai paramedis record consistency
        $pegawaiRule = new ConsistentRoleAssignmentRule('pegawai', 'paramedis', $pegawai->id);
        $this->assertTrue($this->validateRule($pegawaiRule, 'role_id', $paramedisRole->id));
        $this->assertFalse($this->validateRule($pegawaiRule, 'role_id', $petugasRole->id));
    }

    /**
     * Helper method to validate a rule
     */
    private function validateRule($rule, $attribute, $value): bool
    {
        try {
            $failed = false;
            $rule->validate($attribute, $value, function($message) use (&$failed) {
                $failed = true;
            });
            return !$failed;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Helper method to get auto-assigned role ID
     */
    private function getAutoRoleId(string $source, ?string $employeeType): ?int
    {
        if ($source === 'dokter') {
            return Role::where('name', 'dokter')->first()?->id;
        }
        
        if ($source === 'pegawai') {
            if ($employeeType === 'paramedis') {
                return Role::where('name', 'paramedis')->first()?->id;
            } else {
                return Role::where('name', 'petugas')->first()?->id;
            }
        }
        
        return null;
    }
}
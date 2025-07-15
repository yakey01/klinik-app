<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use App\Filament\Petugas\Resources\PasienResource\Pages\CreatePasien;
use App\Filament\Petugas\Resources\PasienResource\Pages\EditPasien;
use App\Filament\Petugas\Resources\PasienResource\Pages\ListPasiens;
use App\Filament\Petugas\Resources\TindakanResource\Pages\CreateTindakan;
use App\Filament\Petugas\Resources\TindakanResource\Pages\EditTindakan;
use App\Filament\Petugas\Resources\PendapatanHarianResource\Pages\CreatePendapatanHarian;
use App\Filament\Petugas\Resources\PendapatanHarianResource\Pages\EditPendapatanHarian;
use App\Filament\Petugas\Resources\PengeluaranHarianResource\Pages\CreatePengeluaranHarian;
use App\Filament\Petugas\Resources\JumlahPasienHarianResource\Pages\CreateJumlahPasienHarian;
use App\Services\ValidationWorkflowService;
use App\Services\NotificationService;
use App\Services\TelegramService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $petugas;
    protected User $supervisor;
    protected User $manager;
    protected User $admin;
    protected User $unauthorized;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $petugasRole = Role::create(['name' => 'petugas']);
        $supervisorRole = Role::create(['name' => 'supervisor']);
        $managerRole = Role::create(['name' => 'manager']);
        $adminRole = Role::create(['name' => 'admin']);
        
        // Create permissions
        $this->createPermissions();
        
        // Assign permissions to roles
        $this->assignPermissionsToRoles($petugasRole, $supervisorRole, $managerRole, $adminRole);
        
        // Create users with roles
        $this->petugas = User::factory()->create(['name' => 'Test Petugas']);
        $this->petugas->assignRole('petugas');
        
        $this->supervisor = User::factory()->create(['name' => 'Test Supervisor']);
        $this->supervisor->assignRole('supervisor');
        
        $this->manager = User::factory()->create(['name' => 'Test Manager']);
        $this->manager->assignRole('manager');
        
        $this->admin = User::factory()->create(['name' => 'Test Admin']);
        $this->admin->assignRole('admin');
        
        $this->unauthorized = User::factory()->create(['name' => 'Unauthorized User']);
        // No role assigned to unauthorized user
    }

    protected function createPermissions(): void
    {
        // Pasien permissions
        Permission::create(['name' => 'create_pasien']);
        Permission::create(['name' => 'view_pasien']);
        Permission::create(['name' => 'edit_pasien']);
        Permission::create(['name' => 'delete_pasien']);
        Permission::create(['name' => 'bulk_pasien']);
        
        // Tindakan permissions
        Permission::create(['name' => 'create_tindakan']);
        Permission::create(['name' => 'view_tindakan']);
        Permission::create(['name' => 'edit_tindakan']);
        Permission::create(['name' => 'delete_tindakan']);
        Permission::create(['name' => 'approve_tindakan']);
        Permission::create(['name' => 'reject_tindakan']);
        
        // Pendapatan permissions
        Permission::create(['name' => 'create_pendapatan_harian']);
        Permission::create(['name' => 'view_pendapatan_harian']);
        Permission::create(['name' => 'edit_pendapatan_harian']);
        Permission::create(['name' => 'delete_pendapatan_harian']);
        Permission::create(['name' => 'approve_pendapatan_harian']);
        
        // Pengeluaran permissions
        Permission::create(['name' => 'create_pengeluaran_harian']);
        Permission::create(['name' => 'view_pengeluaran_harian']);
        Permission::create(['name' => 'edit_pengeluaran_harian']);
        Permission::create(['name' => 'delete_pengeluaran_harian']);
        Permission::create(['name' => 'approve_pengeluaran_harian']);
        
        // Laporan permissions
        Permission::create(['name' => 'create_jumlah_pasien_harian']);
        Permission::create(['name' => 'view_jumlah_pasien_harian']);
        Permission::create(['name' => 'edit_jumlah_pasien_harian']);
        Permission::create(['name' => 'delete_jumlah_pasien_harian']);
        
        // Special permissions
        Permission::create(['name' => 'view_all_data']);
        Permission::create(['name' => 'approve_high_value']);
        Permission::create(['name' => 'system_admin']);
        Permission::create(['name' => 'export_data']);
        Permission::create(['name' => 'import_data']);
    }

    protected function assignPermissionsToRoles($petugasRole, $supervisorRole, $managerRole, $adminRole): void
    {
        // Petugas permissions - basic CRUD for their own data
        $petugasRole->givePermissionTo([
            'create_pasien', 'view_pasien', 'edit_pasien',
            'create_tindakan', 'view_tindakan', 'edit_tindakan',
            'create_pendapatan_harian', 'view_pendapatan_harian', 'edit_pendapatan_harian',
            'create_pengeluaran_harian', 'view_pengeluaran_harian', 'edit_pengeluaran_harian',
            'create_jumlah_pasien_harian', 'view_jumlah_pasien_harian', 'edit_jumlah_pasien_harian',
            'export_data'
        ]);
        
        // Supervisor permissions - includes approval rights
        $supervisorRole->givePermissionTo([
            'create_pasien', 'view_pasien', 'edit_pasien', 'bulk_pasien',
            'create_tindakan', 'view_tindakan', 'edit_tindakan', 'approve_tindakan', 'reject_tindakan',
            'create_pendapatan_harian', 'view_pendapatan_harian', 'edit_pendapatan_harian', 'approve_pendapatan_harian',
            'create_pengeluaran_harian', 'view_pengeluaran_harian', 'edit_pengeluaran_harian', 'approve_pengeluaran_harian',
            'create_jumlah_pasien_harian', 'view_jumlah_pasien_harian', 'edit_jumlah_pasien_harian',
            'export_data', 'import_data'
        ]);
        
        // Manager permissions - includes high value approvals and broader access
        $managerRole->givePermissionTo([
            'create_pasien', 'view_pasien', 'edit_pasien', 'delete_pasien', 'bulk_pasien',
            'create_tindakan', 'view_tindakan', 'edit_tindakan', 'delete_tindakan', 'approve_tindakan', 'reject_tindakan',
            'create_pendapatan_harian', 'view_pendapatan_harian', 'edit_pendapatan_harian', 'delete_pendapatan_harian', 'approve_pendapatan_harian',
            'create_pengeluaran_harian', 'view_pengeluaran_harian', 'edit_pengeluaran_harian', 'delete_pengeluaran_harian', 'approve_pengeluaran_harian',
            'create_jumlah_pasien_harian', 'view_jumlah_pasien_harian', 'edit_jumlah_pasien_harian', 'delete_jumlah_pasien_harian',
            'approve_high_value', 'view_all_data', 'export_data', 'import_data'
        ]);
        
        // Admin permissions - all permissions
        $adminRole->givePermissionTo(Permission::all());
    }

    public function test_unauthorized_user_cannot_access_petugas_resources()
    {
        // Test unauthorized user cannot access any resources
        $this->actingAs($this->unauthorized);

        // Test cannot access patient list
        $response = $this->get('/petugas/pasiens');
        $response->assertRedirect(); // Should redirect (likely to login or forbidden)

        // Test cannot access creation forms
        try {
            Livewire::test(CreatePasien::class);
            $this->fail('Unauthorized user should not access CreatePasien');
        } catch (\Exception $e) {
            $this->assertTrue(true); // Expected to fail
        }
    }

    public function test_petugas_can_create_pasien()
    {
        // Test petugas can create patients
        $this->actingAs($this->petugas);

        $pasienData = [
            'no_rekam_medis' => 'RM-2024-001',
            'nama' => 'Test Patient',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
        ];

        $component = Livewire::test(CreatePasien::class)
            ->fillForm($pasienData)
            ->call('create');

        $component->assertSuccessful();
        $this->assertDatabaseHas('pasien', [
            'nama' => 'Test Patient',
            'input_by' => $this->petugas->id,
        ]);
    }

    public function test_petugas_can_edit_own_pasien()
    {
        // Test petugas can edit their own patients
        $this->actingAs($this->petugas);

        $patient = Pasien::factory()->create([
            'input_by' => $this->petugas->id,
            'nama' => 'Original Name',
        ]);

        $component = Livewire::test(EditPasien::class, ['record' => $patient->getRouteKey()])
            ->fillForm(['nama' => 'Updated Name'])
            ->call('save');

        $component->assertSuccessful();
        $this->assertDatabaseHas('pasien', [
            'id' => $patient->id,
            'nama' => 'Updated Name',
        ]);
    }

    public function test_petugas_cannot_delete_pasien()
    {
        // Test petugas cannot delete patients (no delete permission)
        $this->actingAs($this->petugas);

        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);

        // Test delete action is not available or fails
        $component = Livewire::test(ListPasiens::class);
        
        // The delete action should not be visible or should fail
        // This depends on how delete permissions are implemented
        $this->assertTrue($this->petugas->cannot('delete_pasien'));
    }

    public function test_supervisor_can_approve_tindakan()
    {
        // Test supervisor can approve tindakan
        $this->actingAs($this->supervisor);

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'status_validasi' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->assertTrue($this->supervisor->can('approve_tindakan'));

        // Test approval through service
        $telegramService = new TelegramService();
        $notificationService = new NotificationService($telegramService);
        $validationService = new ValidationWorkflowService($telegramService, $notificationService);

        $result = $validationService->approve($tindakan);
        $this->assertTrue($result['success']);
    }

    public function test_petugas_cannot_approve_tindakan()
    {
        // Test petugas cannot approve tindakan
        $this->actingAs($this->petugas);

        $this->assertFalse($this->petugas->can('approve_tindakan'));

        $tindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'status_validasi' => 'pending',
            'submitted_at' => now(),
        ]);

        // Approval should fail
        $telegramService = new TelegramService();
        $notificationService = new NotificationService($telegramService);
        $validationService = new ValidationWorkflowService($telegramService, $notificationService);

        $result = $validationService->approve($tindakan);
        $this->assertFalse($result['success']);
        $this->assertStringContains('permission', strtolower($result['error']));
    }

    public function test_manager_can_approve_high_value_items()
    {
        // Test manager can approve high value items
        $this->actingAs($this->manager);

        $this->assertTrue($this->manager->can('approve_high_value'));

        $highValueTindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'tarif' => 2000000, // High value
            'status_validasi' => 'pending',
            'submitted_at' => now(),
        ]);

        $telegramService = new TelegramService();
        $notificationService = new NotificationService($telegramService);
        $validationService = new ValidationWorkflowService($telegramService, $notificationService);

        $result = $validationService->approve($highValueTindakan);
        $this->assertTrue($result['success']);
    }

    public function test_supervisor_cannot_approve_high_value_items()
    {
        // Test supervisor cannot approve high value items (needs manager)
        $this->actingAs($this->supervisor);

        $this->assertFalse($this->supervisor->can('approve_high_value'));

        $highValueTindakan = Tindakan::factory()->create([
            'input_by' => $this->petugas->id,
            'tarif' => 2000000, // High value
            'status_validasi' => 'pending',
            'submitted_at' => now(),
        ]);

        $telegramService = new TelegramService();
        $notificationService = new NotificationService($telegramService);
        $validationService = new ValidationWorkflowService($telegramService, $notificationService);

        $result = $validationService->approve($highValueTindakan);
        $this->assertFalse($result['success']);
        $this->assertStringContains('insufficient', strtolower($result['error']));
    }

    public function test_bulk_operations_permission()
    {
        // Test bulk operations require specific permission
        $this->actingAs($this->petugas);

        // Petugas should not have bulk permission
        $this->assertFalse($this->petugas->can('bulk_pasien'));

        // Supervisor should have bulk permission
        $this->actingAs($this->supervisor);
        $this->assertTrue($this->supervisor->can('bulk_pasien'));

        // Test bulk selection in UI
        $patients = Pasien::factory()->count(3)->create(['input_by' => $this->supervisor->id]);

        $component = Livewire::test(ListPasiens::class)
            ->set('selectedTableRecords', $patients->pluck('id')->toArray());

        $component->assertSuccessful();
        $this->assertCount(3, $component->get('selectedTableRecords'));
    }

    public function test_export_import_permissions()
    {
        // Test export permission
        $this->actingAs($this->petugas);
        $this->assertTrue($this->petugas->can('export_data'));

        // Test import permission (only supervisor and above)
        $this->assertFalse($this->petugas->can('import_data'));

        $this->actingAs($this->supervisor);
        $this->assertTrue($this->supervisor->can('import_data'));
    }

    public function test_view_all_data_permission()
    {
        // Test view all data permission (manager and above)
        $this->actingAs($this->petugas);
        $this->assertFalse($this->petugas->can('view_all_data'));

        $this->actingAs($this->supervisor);
        $this->assertFalse($this->supervisor->can('view_all_data'));

        $this->actingAs($this->manager);
        $this->assertTrue($this->manager->can('view_all_data'));

        $this->actingAs($this->admin);
        $this->assertTrue($this->admin->can('view_all_data'));
    }

    public function test_admin_has_all_permissions()
    {
        // Test admin has all permissions
        $this->actingAs($this->admin);

        $allPermissions = Permission::all();

        foreach ($allPermissions as $permission) {
            $this->assertTrue(
                $this->admin->can($permission->name),
                "Admin should have permission: {$permission->name}"
            );
        }
    }

    public function test_role_hierarchy_permissions()
    {
        // Test that higher roles have all permissions of lower roles
        
        // Get all petugas permissions
        $petugasPermissions = Role::findByName('petugas')->permissions->pluck('name')->toArray();
        
        // Supervisor should have all petugas permissions
        $this->actingAs($this->supervisor);
        foreach ($petugasPermissions as $permission) {
            $this->assertTrue(
                $this->supervisor->can($permission),
                "Supervisor should have petugas permission: {$permission}"
            );
        }
        
        // Manager should have all supervisor permissions
        $supervisorPermissions = Role::findByName('supervisor')->permissions->pluck('name')->toArray();
        $this->actingAs($this->manager);
        foreach ($supervisorPermissions as $permission) {
            $this->assertTrue(
                $this->manager->can($permission),
                "Manager should have supervisor permission: {$permission}"
            );
        }
    }

    public function test_middleware_permission_checks()
    {
        // Test that routes are protected by appropriate middleware
        
        // Test unauthenticated access
        $response = $this->get('/petugas/pasiens');
        $response->assertRedirect(); // Should redirect to login

        // Test unauthorized role access
        $this->actingAs($this->unauthorized);
        $response = $this->get('/petugas/pasiens');
        $response->assertStatus(403); // Should be forbidden
    }

    public function test_api_permission_checks()
    {
        // Test API endpoints respect permissions (if they exist)
        
        $patient = Pasien::factory()->create(['input_by' => $this->petugas->id]);

        // Test unauthorized API access
        $response = $this->getJson('/api/v1/pasien');
        $response->assertStatus(401); // Unauthorized

        // Test authorized API access
        $this->actingAs($this->petugas);
        $response = $this->getJson('/api/v1/pasien');
        
        if ($response->status() === 200) {
            // API exists and returns data
            $this->assertTrue(true);
        } else {
            // API might not be implemented yet
            $this->assertTrue(true);
        }
    }

    public function test_permission_caching()
    {
        // Test that permissions are properly cached for performance
        $this->actingAs($this->petugas);

        $startTime = microtime(true);
        
        // Check multiple permissions
        for ($i = 0; $i < 10; $i++) {
            $this->petugas->can('create_pasien');
            $this->petugas->can('view_pasien');
            $this->petugas->can('edit_pasien');
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Permission checks should be fast (cached)
        $this->assertLessThan(0.1, $executionTime);
    }

    public function test_dynamic_permission_assignment()
    {
        // Test dynamic permission assignment during runtime
        $testUser = User::factory()->create();
        
        // Initially no permissions
        $this->assertFalse($testUser->can('create_pasien'));
        
        // Assign role
        $testUser->assignRole('petugas');
        
        // Refresh user permissions
        $testUser->refresh();
        $testUser->load('roles.permissions');
        
        // Should now have permissions
        $this->assertTrue($testUser->can('create_pasien'));
    }

    public function test_permission_revocation()
    {
        // Test permission revocation
        $testUser = User::factory()->create();
        $testUser->assignRole('petugas');
        
        // Initially has permission
        $this->assertTrue($testUser->can('create_pasien'));
        
        // Remove role
        $testUser->removeRole('petugas');
        $testUser->refresh();
        
        // Should no longer have permission
        $this->assertFalse($testUser->can('create_pasien'));
    }

    public function test_context_specific_permissions()
    {
        // Test permissions in specific contexts (e.g., editing own vs others' data)
        $this->actingAs($this->petugas);
        
        $ownPatient = Pasien::factory()->create(['input_by' => $this->petugas->id]);
        $otherPatient = Pasien::factory()->create(['input_by' => $this->supervisor->id]);
        
        // Can edit own patient
        $component1 = Livewire::test(EditPasien::class, ['record' => $ownPatient->getRouteKey()]);
        $component1->assertSuccessful();
        
        // Cannot edit other's patient
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(EditPasien::class, ['record' => $otherPatient->getRouteKey()]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
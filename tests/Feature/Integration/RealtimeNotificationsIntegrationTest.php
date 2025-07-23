<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\Pendapatan;
use App\Models\JenisTindakan;
use App\Models\Dokter;
use App\Models\Shift;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\UserNotification;
use App\Services\CacheService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Broadcast;

class RealtimeNotificationsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $petugasUser;
    private User $bendaharaUser;
    private User $adminUser;
    private User $dokterUser;
    private \App\Models\Dokter $dokter;
    private \App\Models\Shift $shift;
    private \App\Models\Pegawai $paramedis;
    private \App\Models\Pegawai $nonParamedis;
    private JenisTindakan $jenisTindakan;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Get roles that were created by base TestCase
        $petugasRole = Role::where('name', 'petugas')->first();
        $bendaharaRole = Role::where('name', 'bendahara')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $dokterRole = Role::where('name', 'dokter')->first();
        
        // Create test users
        $this->petugasUser = User::factory()->create([
            'role_id' => $petugasRole->id,
            'is_active' => true,
            'name' => 'Test Petugas',
            'email' => 'petugas@test.com',
        ]);
        
        $this->bendaharaUser = User::factory()->create([
            'role_id' => $bendaharaRole->id,
            'is_active' => true,
            'name' => 'Test Bendahara',
            'email' => 'bendahara@test.com',
        ]);
        
        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
        ]);
        
        $this->dokterUser = User::factory()->create([
            'role_id' => $dokterRole->id,
            'is_active' => true,
            'name' => 'Dr. Test',
            'email' => 'dokter@test.com',
        ]);
        
        // Create dokter record
        $this->dokter = Dokter::factory()->create([
            'user_id' => $this->dokterUser->id,
            'aktif' => true,
        ]);
        
        // Create shift
        $this->shift = Shift::factory()->create([
            'is_active' => true,
        ]);
        
        // Create paramedis and non-paramedis staff
        $this->paramedis = Pegawai::factory()->create([
            'jenis_pegawai' => 'Paramedis',
            'aktif' => true,
        ]);
        
        $this->nonParamedis = Pegawai::factory()->create([
            'jenis_pegawai' => 'Non-Paramedis',
            'aktif' => true,
        ]);
        
        // Create jenis tindakan
        $this->jenisTindakan = JenisTindakan::factory()->create([
            'nama' => 'Konsultasi Umum',
            'tarif' => 100000,
            'jasa_dokter' => 60000,
            'jasa_paramedis' => 20000,
            'jasa_non_paramedis' => 20000,
            'is_active' => true,
        ]);
    }

    public function test_new_patient_registration_notification()
    {
        // Fake events and notifications
        Event::fake();
        Notification::fake();
        
        $this->actingAs($this->petugasUser);
        
        // Create a new patient
        $patient = Pasien::create([
            'no_rekam_medis' => 'RM001',
            'nama' => 'John Doe',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jakarta',
            'no_telepon' => '08123456789',
            'email' => 'john@example.com',
        ]);
        
        // Create notification manually (simulating event listener)
        $notificationData = [
            'user_id' => $this->bendaharaUser->id,
            'type' => 'patient_registered',
            'title' => 'Pasien Baru Terdaftar',
            'message' => "Pasien baru '{$patient->nama}' telah terdaftar oleh {$this->petugasUser->name}",
            'data' => json_encode([
                'patient_id' => $patient->id,
                'patient_name' => $patient->nama,
                'registered_by' => $this->petugasUser->name,
                'action_url' => "/petugas/pasien/{$patient->id}",
            ]),
            'is_read' => false,
            'priority' => 'normal',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        UserNotification::create($notificationData);
        
        // Verify notification was created
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->bendaharaUser->id,
            'type' => 'patient_registered',
            'title' => 'Pasien Baru Terdaftar',
            'is_read' => false,
        ]);
        
        // Test notification retrieval
        $this->actingAs($this->bendaharaUser);
        $notifications = UserNotification::where('user_id', $this->bendaharaUser->id)
                                        ->where('is_read', false)
                                        ->orderBy('created_at', 'desc')
                                        ->get();
        
        $this->assertCount(1, $notifications);
        $this->assertEquals('patient_registered', $notifications->first()->type);
        
        // Test notification data structure
        $notificationData = json_decode($notifications->first()->data, true);
        $this->assertArrayHasKey('patient_id', $notificationData);
        $this->assertArrayHasKey('patient_name', $notificationData);
        $this->assertArrayHasKey('registered_by', $notificationData);
        $this->assertArrayHasKey('action_url', $notificationData);
        
        return $notifications->first();
    }

    public function test_tindakan_validation_pending_notification()
    {
        Event::fake();
        Notification::fake();
        
        $this->actingAs($this->petugasUser);
        
        // Create patient and tindakan
        $patient = Pasien::factory()->create();
        $tindakan = Tindakan::create([
            'pasien_id' => $patient->id,
            'jenis_tindakan_id' => $this->jenisTindakan->id,
            'dokter_id' => $this->dokter->id,
            'paramedis_id' => $this->paramedis->id,
            'non_paramedis_id' => $this->nonParamedis->id,
            'shift_id' => $this->shift->id,
            'tanggal_tindakan' => Carbon::now(),
            'tarif' => $this->jenisTindakan->tarif,
            'jasa_dokter' => $this->jenisTindakan->jasa_dokter,
            'jasa_paramedis' => $this->jenisTindakan->jasa_paramedis,
            'jasa_non_paramedis' => $this->jenisTindakan->jasa_non_paramedis,
            'catatan' => 'Konsultasi rutin',
            'status' => 'selesai',
            'status_validasi' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        // Create validation pending notification for bendahara
        $notificationData = [
            'user_id' => $this->bendaharaUser->id,
            'type' => 'validation_pending',
            'title' => 'Tindakan Perlu Validasi',
            'message' => "Tindakan '{$tindakan->jenisTindakan->nama}' untuk pasien '{$patient->nama}' memerlukan validasi",
            'data' => json_encode([
                'tindakan_id' => $tindakan->id,
                'patient_name' => $patient->nama,
                'jenis_tindakan' => $tindakan->jenisTindakan->nama,
                'tarif' => $tindakan->tarif,
                'input_by' => $this->petugasUser->name,
                'action_url' => "/bendahara/tindakan/{$tindakan->id}/validate",
            ]),
            'is_read' => false,
            'priority' => 'high',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        UserNotification::create($notificationData);
        
        // Verify notification
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->bendaharaUser->id,
            'type' => 'validation_pending',
            'priority' => 'high',
            'is_read' => false,
        ]);
        
        // Test notification count for bendahara
        $pendingCount = UserNotification::where('user_id', $this->bendaharaUser->id)
                                       ->where('type', 'validation_pending')
                                       ->where('is_read', false)
                                       ->count();
        
        $this->assertEquals(1, $pendingCount);
        
        return $tindakan;
    }

    public function test_validation_approved_notification()
    {
        // Create pending tindakan first
        $tindakan = $this->test_tindakan_validation_pending_notification();
        
        Event::fake();
        Notification::fake();
        
        $this->actingAs($this->bendaharaUser);
        
        // Approve the tindakan
        $tindakan->update([
            'status_validasi' => 'approved',
            'validated_by' => $this->bendaharaUser->id,
            'validated_at' => Carbon::now(),
            'komentar_validasi' => 'Tindakan disetujui',
        ]);
        
        // Create approval notification for petugas (original input user)
        $notificationData = [
            'user_id' => $this->petugasUser->id,
            'type' => 'validation_approved',
            'title' => 'Tindakan Disetujui',
            'message' => "Tindakan '{$tindakan->jenisTindakan->nama}' telah disetujui oleh {$this->bendaharaUser->name}",
            'data' => json_encode([
                'tindakan_id' => $tindakan->id,
                'patient_name' => $tindakan->pasien->nama,
                'jenis_tindakan' => $tindakan->jenisTindakan->nama,
                'validated_by' => $this->bendaharaUser->name,
                'validated_at' => $tindakan->validated_at->toISOString(),
                'comment' => $tindakan->komentar_validasi,
            ]),
            'is_read' => false,
            'priority' => 'normal',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        UserNotification::create($notificationData);
        
        // Also create notification for admin about the approval
        $adminNotificationData = [
            'user_id' => $this->adminUser->id,
            'type' => 'validation_approved',
            'title' => 'Tindakan Disetujui - Info',
            'message' => "Tindakan dengan tarif Rp " . number_format($tindakan->tarif) . " telah disetujui",
            'data' => json_encode([
                'tindakan_id' => $tindakan->id,
                'patient_name' => $tindakan->pasien->nama,
                'tarif' => $tindakan->tarif,
                'validated_by' => $this->bendaharaUser->name,
            ]),
            'is_read' => false,
            'priority' => 'low',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        UserNotification::create($adminNotificationData);
        
        // Verify notifications were created
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->petugasUser->id,
            'type' => 'validation_approved',
            'title' => 'Tindakan Disetujui',
        ]);
        
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->adminUser->id,
            'type' => 'validation_approved',
            'title' => 'Tindakan Disetujui - Info',
        ]);
        
        return $tindakan;
    }

    public function test_validation_rejected_notification()
    {
        Event::fake();
        Notification::fake();
        
        $this->actingAs($this->petugasUser);
        
        // Create patient and tindakan
        $patient = Pasien::factory()->create();
        $tindakan = Tindakan::create([
            'pasien_id' => $patient->id,
            'jenis_tindakan_id' => $this->jenisTindakan->id,
            'dokter_id' => $this->dokter->id,
            'paramedis_id' => $this->paramedis->id,
            'non_paramedis_id' => $this->nonParamedis->id,
            'shift_id' => $this->shift->id,
            'tanggal_tindakan' => Carbon::now(),
            'tarif' => $this->jenisTindakan->tarif,
            'status' => 'selesai',
            'status_validasi' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        $this->actingAs($this->bendaharaUser);
        
        // Reject the tindakan
        $tindakan->update([
            'status_validasi' => 'rejected',
            'validated_by' => $this->bendaharaUser->id,
            'validated_at' => Carbon::now(),
            'komentar_validasi' => 'Dokumen tidak lengkap, perlu dilengkapi',
        ]);
        
        // Create rejection notification
        $notificationData = [
            'user_id' => $this->petugasUser->id,
            'type' => 'validation_rejected',
            'title' => 'Tindakan Ditolak',
            'message' => "Tindakan '{$tindakan->jenisTindakan->nama}' ditolak: {$tindakan->komentar_validasi}",
            'data' => json_encode([
                'tindakan_id' => $tindakan->id,
                'patient_name' => $tindakan->pasien->nama,
                'jenis_tindakan' => $tindakan->jenisTindakan->nama,
                'rejection_reason' => $tindakan->komentar_validasi,
                'validated_by' => $this->bendaharaUser->name,
                'action_url' => "/petugas/tindakan/{$tindakan->id}/edit",
            ]),
            'is_read' => false,
            'priority' => 'high',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        UserNotification::create($notificationData);
        
        // Verify rejection notification
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->petugasUser->id,
            'type' => 'validation_rejected',
            'priority' => 'high',
            'is_read' => false,
        ]);
        
        // Test notification message contains rejection reason
        $notification = UserNotification::where('user_id', $this->petugasUser->id)
                                       ->where('type', 'validation_rejected')
                                       ->first();
        
        $this->assertStringContains('Dokumen tidak lengkap', $notification->message);
        
        return $notification;
    }

    public function test_bulk_operation_progress_notifications()
    {
        Event::fake();
        Notification::fake();
        
        $this->actingAs($this->adminUser);
        
        // Simulate bulk operation start notification
        $bulkStartNotification = [
            'user_id' => $this->adminUser->id,
            'type' => 'bulk_operation_started',
            'title' => 'Operasi Bulk Dimulai',
            'message' => 'Import data pasien dimulai - 100 records',
            'data' => json_encode([
                'operation_id' => 'bulk_123',
                'operation_type' => 'patient_import',
                'total_records' => 100,
                'estimated_duration' => '2 minutes',
            ]),
            'is_read' => false,
            'priority' => 'normal',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        UserNotification::create($bulkStartNotification);
        
        // Simulate progress notification (50% complete)
        $bulkProgressNotification = [
            'user_id' => $this->adminUser->id,
            'type' => 'bulk_operation_progress',
            'title' => 'Operasi Bulk - 50% Selesai',
            'message' => 'Import data pasien: 50/100 records berhasil diproses',
            'data' => json_encode([
                'operation_id' => 'bulk_123',
                'processed_records' => 50,
                'total_records' => 100,
                'progress_percentage' => 50,
                'estimated_remaining' => '1 minute',
            ]),
            'is_read' => false,
            'priority' => 'normal',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        UserNotification::create($bulkProgressNotification);
        
        // Simulate completion notification
        $bulkCompleteNotification = [
            'user_id' => $this->adminUser->id,
            'type' => 'bulk_operation_completed',
            'title' => 'Operasi Bulk Selesai',
            'message' => 'Import data pasien berhasil diselesaikan: 95/100 records berhasil, 5 gagal',
            'data' => json_encode([
                'operation_id' => 'bulk_123',
                'total_records' => 100,
                'processed_records' => 95,
                'failed_records' => 5,
                'duration' => '1 minute 45 seconds',
                'download_url' => '/downloads/bulk_import_results.csv',
            ]),
            'is_read' => false,
            'priority' => 'normal',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
        
        UserNotification::create($bulkCompleteNotification);
        
        // Verify all bulk operation notifications
        $bulkNotifications = UserNotification::where('user_id', $this->adminUser->id)
                                           ->where('type', 'LIKE', 'bulk_operation_%')
                                           ->orderBy('created_at')
                                           ->get();
        
        $this->assertCount(3, $bulkNotifications);
        $this->assertEquals('bulk_operation_started', $bulkNotifications->first()->type);
        $this->assertEquals('bulk_operation_completed', $bulkNotifications->last()->type);
        
        // Test progress data structure
        $progressData = json_decode($bulkNotifications[1]->data, true);
        $this->assertEquals(50, $progressData['progress_percentage']);
        $this->assertEquals(50, $progressData['processed_records']);
        
        return $bulkNotifications;
    }

    public function test_system_maintenance_broadcast_notification()
    {
        Event::fake();
        Notification::fake();
        Broadcast::fake();
        
        $this->actingAs($this->adminUser);
        
        // Create system maintenance notification for all users
        $maintenanceMessage = 'Sistem akan maintenance pada 23:00-01:00 WIB';
        $maintenanceNotifications = [];
        
        $allUsers = [$this->petugasUser, $this->bendaharaUser, $this->dokterUser, $this->adminUser];
        
        foreach ($allUsers as $user) {
            $notificationData = [
                'user_id' => $user->id,
                'type' => 'system_maintenance',
                'title' => 'Pemberitahuan Maintenance',
                'message' => $maintenanceMessage,
                'data' => json_encode([
                    'maintenance_start' => '2025-07-16 23:00:00',
                    'maintenance_end' => '2025-07-17 01:00:00',
                    'affected_services' => ['web_app', 'api'],
                    'broadcast_by' => $this->adminUser->name,
                ]),
                'is_read' => false,
                'priority' => 'urgent',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            $maintenanceNotifications[] = UserNotification::create($notificationData);
        }
        
        // Verify all users received the notification
        foreach ($allUsers as $user) {
            $this->assertDatabaseHas('user_notifications', [
                'user_id' => $user->id,
                'type' => 'system_maintenance',
                'priority' => 'urgent',
                'title' => 'Pemberitahuan Maintenance',
            ]);
        }
        
        // Test notification count by priority
        $urgentNotifications = UserNotification::where('priority', 'urgent')->count();
        $this->assertEquals(4, $urgentNotifications); // All 4 users
        
        return $maintenanceNotifications;
    }

    public function test_notification_read_status_management()
    {
        // Create some notifications first
        $notification = $this->test_new_patient_registration_notification();
        
        $this->actingAs($this->bendaharaUser);
        
        // Test marking notification as read
        $notification->update(['is_read' => true, 'read_at' => Carbon::now()]);
        
        $this->assertDatabaseHas('user_notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
        
        // Test unread notification count
        $unreadCount = UserNotification::where('user_id', $this->bendaharaUser->id)
                                      ->where('is_read', false)
                                      ->count();
        
        $this->assertEquals(1, $unreadCount); // One from validation pending test
        
        // Test marking all as read
        UserNotification::where('user_id', $this->bendaharaUser->id)
                       ->where('is_read', false)
                       ->update(['is_read' => true, 'read_at' => Carbon::now()]);
        
        $newUnreadCount = UserNotification::where('user_id', $this->bendaharaUser->id)
                                         ->where('is_read', false)
                                         ->count();
        
        $this->assertEquals(0, $newUnreadCount);
        
        return $notification;
    }

    public function test_notification_priority_filtering()
    {
        // Create notifications with different priorities
        $this->test_validation_rejected_notification(); // high priority
        $this->test_new_patient_registration_notification(); // normal priority
        $this->test_system_maintenance_broadcast_notification(); // urgent priority
        
        $this->actingAs($this->petugasUser);
        
        // Test filtering by priority
        $urgentNotifications = UserNotification::where('user_id', $this->petugasUser->id)
                                             ->where('priority', 'urgent')
                                             ->get();
        
        $highNotifications = UserNotification::where('user_id', $this->petugasUser->id)
                                            ->where('priority', 'high')
                                            ->get();
        
        $normalNotifications = UserNotification::where('user_id', $this->petugasUser->id)
                                             ->where('priority', 'normal')
                                             ->get();
        
        $this->assertCount(1, $urgentNotifications); // system maintenance
        $this->assertCount(1, $highNotifications); // validation rejected
        $this->assertEquals(0, $normalNotifications->count()); // petugas doesn't get patient registration notifications
        
        // Test notification ordering by priority and date
        $allNotifications = UserNotification::where('user_id', $this->petugasUser->id)
                                          ->orderByRaw("CASE 
                                              WHEN priority = 'urgent' THEN 1 
                                              WHEN priority = 'high' THEN 2 
                                              WHEN priority = 'normal' THEN 3 
                                              ELSE 4 END")
                                          ->orderBy('created_at', 'desc')
                                          ->get();
        
        // First notification should be urgent priority
        $this->assertEquals('urgent', $allNotifications->first()->priority);
        
        return $allNotifications;
    }

    public function test_notification_cache_integration()
    {
        $cacheService = app(CacheService::class);
        
        // Clear cache
        $cacheService->flushAll();
        
        // Create some notifications
        $this->test_new_patient_registration_notification();
        $this->test_tindakan_validation_pending_notification();
        
        $this->actingAs($this->bendaharaUser);
        
        // Test cached notification count
        $unreadCount = $cacheService->cacheQuery('user_notifications_unread_' . $this->bendaharaUser->id, function() {
            return UserNotification::where('user_id', $this->bendaharaUser->id)
                                  ->where('is_read', false)
                                  ->count();
        });
        
        $this->assertEquals(2, $unreadCount);
        
        // Test cached notification summary
        $notificationSummary = $cacheService->cacheQuery('user_notifications_summary_' . $this->bendaharaUser->id, function() {
            return [
                'total_unread' => UserNotification::where('user_id', $this->bendaharaUser->id)
                                                 ->where('is_read', false)
                                                 ->count(),
                'urgent_count' => UserNotification::where('user_id', $this->bendaharaUser->id)
                                                 ->where('is_read', false)
                                                 ->where('priority', 'urgent')
                                                 ->count(),
                'high_count' => UserNotification::where('user_id', $this->bendaharaUser->id)
                                               ->where('is_read', false)
                                               ->where('priority', 'high')
                                               ->count(),
                'last_notification' => UserNotification::where('user_id', $this->bendaharaUser->id)
                                                      ->orderBy('created_at', 'desc')
                                                      ->first(),
            ];
        });
        
        $this->assertEquals(2, $notificationSummary['total_unread']);
        $this->assertEquals(1, $notificationSummary['high_count']); // validation pending
        $this->assertNotNull($notificationSummary['last_notification']);
        
        // Test cache performance
        $startTime = microtime(true);
        
        // Multiple cache hits should be very fast
        for ($i = 0; $i < 10; $i++) {
            $cachedCount = $cacheService->cacheQuery('user_notifications_unread_' . $this->bendaharaUser->id, function() {
                return UserNotification::where('user_id', $this->bendaharaUser->id)
                                      ->where('is_read', false)
                                      ->count();
            });
        }
        
        $endTime = microtime(true);
        $cacheExecutionTime = $endTime - $startTime;
        
        // Cache hits should be very fast
        $this->assertLessThan(0.05, $cacheExecutionTime, 'Notification cache performance is poor');
        
        return $notificationSummary;
    }

    public function test_realtime_notification_delivery_simulation()
    {
        // Simulate real-time notification delivery workflow
        Event::fake();
        Notification::fake();
        Queue::fake();
        
        $this->actingAs($this->petugasUser);
        
        // Step 1: Create patient (triggers notification)
        $patient = Pasien::factory()->create();
        
        // Step 2: Create tindakan (triggers validation notification)
        $tindakan = Tindakan::create([
            'pasien_id' => $patient->id,
            'jenis_tindakan_id' => $this->jenisTindakan->id,
            'dokter_id' => $this->dokter->id,
            'paramedis_id' => $this->paramedis->id,
            'non_paramedis_id' => $this->nonParamedis->id,
            'shift_id' => $this->shift->id,
            'tanggal_tindakan' => Carbon::now(),
            'tarif' => $this->jenisTindakan->tarif,
            'status' => 'selesai',
            'status_validasi' => 'pending',
            'input_by' => $this->petugasUser->id,
        ]);
        
        // Step 3: Create real-time notifications for immediate delivery
        $notifications = [
            // Notification for bendahara about validation needed
            [
                'user_id' => $this->bendaharaUser->id,
                'type' => 'validation_pending',
                'title' => 'Validasi Diperlukan',
                'message' => "Tindakan baru memerlukan validasi segera",
                'data' => json_encode([
                    'tindakan_id' => $tindakan->id,
                    'urgency' => 'immediate',
                    'realtime' => true,
                ]),
                'is_read' => false,
                'priority' => 'high',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            // Notification for admin about system activity
            [
                'user_id' => $this->adminUser->id,
                'type' => 'system_activity',
                'title' => 'Aktivitas Sistem',
                'message' => "Tindakan baru senilai Rp " . number_format($tindakan->tarif) . " ditambahkan",
                'data' => json_encode([
                    'activity_type' => 'tindakan_created',
                    'amount' => $tindakan->tarif,
                    'realtime' => true,
                ]),
                'is_read' => false,
                'priority' => 'low',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ];
        
        foreach ($notifications as $notificationData) {
            UserNotification::create($notificationData);
        }
        
        // Step 4: Simulate WebSocket broadcast (would normally be done by event listener)
        $broadcastData = [
            'user_notifications' => UserNotification::whereIn('user_id', [$this->bendaharaUser->id, $this->adminUser->id])
                                                   ->where('is_read', false)
                                                   ->with(['user'])
                                                   ->latest()
                                                   ->get(),
            'timestamp' => Carbon::now()->toISOString(),
            'event_type' => 'notifications_updated',
        ];
        
        // Step 5: Verify real-time notification data structure
        $this->assertCount(2, $broadcastData['user_notifications']);
        
        // Step 6: Test notification delivery performance
        $startTime = microtime(true);
        
        // Simulate processing 100 real-time notifications
        for ($i = 0; $i < 100; $i++) {
            $testNotification = [
                'user_id' => $this->adminUser->id,
                'type' => 'performance_test',
                'title' => "Test Notification #{$i}",
                'message' => "Performance test notification {$i}",
                'data' => json_encode(['test_id' => $i]),
                'is_read' => false,
                'priority' => 'low',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            
            // In real implementation, this would be queued
            UserNotification::create($testNotification);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify performance (100 notifications should be processed quickly)
        $this->assertLessThan(1.0, $executionTime, 'Real-time notification processing too slow');
        
        // Verify total notification count
        $totalNotifications = UserNotification::count();
        $this->assertGreaterThanOrEqual(102, $totalNotifications); // 2 + 100 test notifications
        
        return $broadcastData;
    }
}
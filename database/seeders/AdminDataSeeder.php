<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SystemMetric;
use App\Models\AuditLog;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

class AdminDataSeeder extends Seeder
{
    /**
     * Run the database seeds for admin panel functionality.
     */
    public function run(): void
    {
        // Create admin permission if not exists
        $adminPermission = Permission::firstOrCreate([
            'name' => 'access_admin_panel',
            'guard_name' => 'web'
        ]);
        
        // Ensure admin role exists
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);
        
        // Create relation if it doesn't exist
        if (!$adminRole->permissions()->where('name', 'access_admin_panel')->exists()) {
            $adminRole->permissions()->attach($adminPermission->id);
        }
        
        // Create admin user if not exists
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@dokterku.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('admin123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }
        
        // Seed SystemMetric data for dashboard widgets
        $this->seedSystemMetrics();
        
        // Seed AuditLog data for security dashboard
        $this->seedAuditLogs();
        
        // Seed financial data for financial widgets
        $this->seedFinancialData();
        
        // Seed patient data for medical operations
        $this->seedPatientData();
        
        $this->command->info('Admin dashboard data seeded successfully!');
    }
    
    private function seedSystemMetrics(): void
    {
        // Create system metrics for the last 24 hours using the actual schema
        $metricTypes = ['memory', 'cpu', 'disk', 'response_time', 'database', 'cache', 'queue'];
        
        for ($i = 0; $i < 24; $i++) {
            $recordedAt = now()->subHours($i);
            
            foreach ($metricTypes as $type) {
                $value = match($type) {
                    'memory' => rand(45, 85),
                    'cpu' => rand(20, 70),
                    'disk' => rand(30, 60),
                    'response_time' => rand(100, 800) / 100,
                    'database' => rand(50, 200),
                    'cache' => rand(70, 95),
                    'queue' => rand(0, 50),
                };
                
                SystemMetric::create([
                    'metric_type' => $type,
                    'metric_name' => $type . '_usage',
                    'metric_value' => $value,
                    'metric_data' => json_encode(['source' => 'admin_seeder']),
                    'status' => collect(['healthy', 'warning', 'critical'])->random(),
                    'recorded_at' => $recordedAt,
                    'created_at' => $recordedAt,
                    'updated_at' => $recordedAt,
                ]);
            }
        }
    }
    
    private function seedAuditLogs(): void
    {
        $actions = [
            'login_success', 'login_failed', 'logout', 'user_created', 
            'user_updated', 'user_deleted', 'role_assigned', 'permission_granted',
            'account_locked', 'suspicious_activity'
        ];
        
        $riskLevels = ['low', 'medium', 'high'];
        
        // Create audit logs for the last 7 days using actual schema
        for ($i = 0; $i < 100; $i++) {
            AuditLog::create([
                'user_id' => 1, // Admin user
                'action' => collect($actions)->random(),
                'model_type' => collect(['App\\Models\\User', 'App\\Models\\Pasien', 'App\\Models\\Tindakan'])->random(),
                'model_id' => rand(1, 10),
                'old_values' => json_encode(['status' => 'old_value']),
                'new_values' => json_encode(['status' => 'new_value']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'url' => '/admin/dashboard',
                'created_at' => now()->subDays(rand(0, 7))->subHours(rand(0, 23)),
            ]);
        }
    }
    
    private function seedFinancialData(): void
    {
        // Seed current month financial data
        $currentMonth = now();
        $daysInMonth = $currentMonth->daysInMonth;
        
        for ($day = 1; $day <= min($daysInMonth, now()->day); $day++) {
            $date = $currentMonth->copy()->day($day);
            
            // Pendapatan Harian
            PendapatanHarian::firstOrCreate(
                ['tanggal_input' => $date->format('Y-m-d')],
                [
                    'nominal' => rand(500000, 2000000),
                    'keterangan' => 'Pendapatan harian ' . $date->format('d M Y'),
                    'status' => collect(['pending', 'validated', 'rejected'])->random(),
                    'validated_by' => 1,
                    'validated_at' => rand(0, 1) ? $date->addHours(rand(1, 8)) : null,
                ]
            );
            
            // Pengeluaran Harian
            PengeluaranHarian::firstOrCreate(
                ['tanggal_input' => $date->format('Y-m-d')],
                [
                    'nominal' => rand(200000, 800000),
                    'keterangan' => 'Pengeluaran operasional ' . $date->format('d M Y'),
                    'status' => collect(['pending', 'validated', 'rejected'])->random(),
                    'validated_by' => 1,
                    'validated_at' => rand(0, 1) ? $date->addHours(rand(1, 8)) : null,
                ]
            );
        }
        
        // Seed last month data for comparison
        $lastMonth = now()->subMonth();
        for ($day = 1; $day <= $lastMonth->daysInMonth; $day++) {
            $date = $lastMonth->copy()->day($day);
            
            PendapatanHarian::firstOrCreate(
                ['tanggal_input' => $date->format('Y-m-d')],
                [
                    'nominal' => rand(400000, 1800000),
                    'keterangan' => 'Pendapatan harian ' . $date->format('d M Y'),
                    'status' => 'validated',
                    'validated_by' => 1,
                    'validated_at' => $date->addHours(rand(1, 8)),
                ]
            );
            
            PengeluaranHarian::firstOrCreate(
                ['tanggal_input' => $date->format('Y-m-d')],
                [
                    'nominal' => rand(150000, 700000),
                    'keterangan' => 'Pengeluaran operasional ' . $date->format('d M Y'),
                    'status' => 'validated',
                    'validated_by' => 1,
                    'validated_at' => $date->addHours(rand(1, 8)),
                ]
            );
        }
    }
    
    private function seedPatientData(): void
    {
        // Seed current month patient data
        $currentMonth = now();
        $daysInMonth = $currentMonth->daysInMonth;
        
        for ($day = 1; $day <= min($daysInMonth, now()->day); $day++) {
            $date = $currentMonth->copy()->day($day);
            
            JumlahPasienHarian::firstOrCreate(
                ['tanggal' => $date->format('Y-m-d')],
                [
                    'jumlah_pasien_umum' => rand(10, 30),
                    'jumlah_pasien_bpjs' => rand(15, 25),
                    'status_validasi' => collect(['pending', 'validated', 'rejected'])->random(),
                    'validated_by' => rand(0, 1) ? 1 : null,
                    'validated_at' => rand(0, 1) ? $date->addHours(rand(1, 8)) : null,
                ]
            );
        }
        
        // Seed last month patient data for comparison
        $lastMonth = now()->subMonth();
        for ($day = 1; $day <= $lastMonth->daysInMonth; $day++) {
            $date = $lastMonth->copy()->day($day);
            
            JumlahPasienHarian::firstOrCreate(
                ['tanggal' => $date->format('Y-m-d')],
                [
                    'jumlah_pasien_umum' => rand(8, 25),
                    'jumlah_pasien_bpjs' => rand(12, 20),
                    'status_validasi' => 'validated',
                    'validated_by' => 1,
                    'validated_at' => $date->addHours(rand(1, 8)),
                ]
            );
        }
    }
}
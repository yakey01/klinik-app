<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Pegawai;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Tindakan;
use App\Models\JenisTindakan;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\Attendance;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use App\Models\Shift;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\JumlahPasienHarian;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use Faker\Factory as Faker;

class ParamedisRealDataSeeder extends Seeder
{
    private $faker;

    public function __construct()
    {
        $this->faker = Faker::create('id_ID');
    }

    /**
     * Seed comprehensive real data for Paramedis with cross-panel relationships
     */
    public function run(): void
    {
        $this->command->info('🏥 Starting Paramedis Real Data Seeding...');

        // 1. Create roles and permissions
        $this->seedRolesAndPermissions();
        
        // 2. Create staff (Pegawai) for all panels
        $this->seedStaffData();
        
        // 3. Create doctors
        $this->seedDoctors();
        
        // 4. Create patients
        $this->seedPatients();
        
        // 5. Create medical procedure types
        $this->seedJenisTindakan();
        
        // 6. Create shift templates and schedules
        $this->seedShiftsAndSchedules();
        
        // 7. Create medical procedures (core data)
        $this->seedTindakan();
        
        // 8. Create attendance records
        $this->seedAttendance();
        
        // 9. Create revenue/expense records
        $this->seedFinancialRecords();
        
        // 10. Create daily summaries for bendahara
        $this->seedDailySummaries();

        $this->command->info('✅ Paramedis Real Data Seeding Completed!');
    }

    private function seedRolesAndPermissions(): void
    {
        $this->command->info('👥 Creating roles and permissions...');

        $roles = [
            'admin' => 'Administrator',
            'manajer' => 'Manajer',
            'bendahara' => 'Bendahara',
            'petugas' => 'Petugas',
            'dokter' => 'Dokter',
            'paramedis' => 'Paramedis',
            'non_paramedis' => 'Non Paramedis'
        ];

        foreach ($roles as $name => $displayName) {
            Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web'
            ], [
                'display_name' => $displayName
            ]);
        }
    }

    private function seedStaffData(): void
    {
        $this->command->info('👨‍⚕️ Creating staff data...');

        // Admin user
        $adminPegawai = Pegawai::firstOrCreate([
            'nik' => '19850101001'
        ], [
            'nama_lengkap' => 'Dr. Ahmad Sudirman',
            'email' => 'admin@dokterku.com',
            'tanggal_lahir' => '1985-01-01',
            'jenis_kelamin' => 'L',
            'jabatan' => 'Direktur',
            'jenis_pegawai' => 'Administrasi',
            'aktif' => true,
        ]);

        $adminUser = User::firstOrCreate([
            'email' => 'admin@dokterku.com'
        ], [
            'name' => 'Dr. Ahmad Sudirman',
            'password' => bcrypt('admin123'),
            'pegawai_id' => $adminPegawai->id,
            'is_active' => true,
        ]);
        
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
        }

        // Manajer
        $manajerPegawai = Pegawai::firstOrCreate([
            'nik' => '19800215002'
        ], [
            'nama_lengkap' => 'Siti Nurhaliza, S.Kes',
            'email' => 'manajer@dokterku.com',
            'tanggal_lahir' => '1980-02-15',
            'jenis_kelamin' => 'P',
            'jabatan' => 'Manajer Operasional',
            'jenis_pegawai' => 'Administrasi',
            'aktif' => true,
        ]);

        $manajerUser = User::firstOrCreate([
            'email' => 'manajer@dokterku.com'
        ], [
            'name' => 'Siti Nurhaliza, S.Kes',
            'password' => bcrypt('manajer123'),
            'pegawai_id' => $manajerPegawai->id,
            'is_active' => true,
        ]);
        
        if (!$manajerUser->hasRole('manajer')) {
            $manajerUser->assignRole('manajer');
        }

        // Bendahara
        $bendaharaPegawai = Pegawai::firstOrCreate([
            'nik' => '19830720003'
        ], [
            'nama_lengkap' => 'Budi Santoso, S.E',
            'email' => 'bendahara@dokterku.com',
            'tanggal_lahir' => '1983-07-20',
            'jenis_kelamin' => 'L',
            'jabatan' => 'Bendahara',
            'jenis_pegawai' => 'Administrasi',
            'aktif' => true,
        ]);

        $bendaharaUser = User::firstOrCreate([
            'email' => 'bendahara@dokterku.com'
        ], [
            'name' => 'Budi Santoso, S.E',
            'password' => bcrypt('bendahara123'),
            'pegawai_id' => $bendaharaPegawai->id,
            'is_active' => true,
        ]);
        
        if (!$bendaharaUser->hasRole('bendahara')) {
            $bendaharaUser->assignRole('bendahara');
        }

        // Petugas
        $petugasPegawai = Pegawai::firstOrCreate([
            'nik' => '19870410004'
        ], [
            'nama_lengkap' => 'Rina Susanti, A.Md.Kes',
            'email' => 'petugas@dokterku.com',
            'tanggal_lahir' => '1987-04-10',
            'jenis_kelamin' => 'P',
            'jabatan' => 'Petugas Administrasi',
            'jenis_pegawai' => 'Administrasi',
            'aktif' => true,
        ]);

        $petugasUser = User::firstOrCreate([
            'email' => 'petugas@dokterku.com'
        ], [
            'name' => 'Rina Susanti, A.Md.Kes',
            'password' => bcrypt('petugas123'),
            'pegawai_id' => $petugasPegawai->id,
            'is_active' => true,
        ]);
        
        if (!$petugasUser->hasRole('petugas')) {
            $petugasUser->assignRole('petugas');
        }

        // Paramedis team
        $paramedisData = [
            [
                'nik' => '19900512005',
                'nama' => 'Dewi Kartika, A.Md.Kep',
                'email' => 'paramedis1@dokterku.com',
                'tanggal_lahir' => '1990-05-12',
                'jenis_kelamin' => 'P',
                'jabatan' => 'Perawat Umum',
            ],
            [
                'nik' => '19880925006',
                'nama' => 'Agus Priyanto, A.Md.Kep',
                'email' => 'paramedis2@dokterku.com',
                'tanggal_lahir' => '1988-09-25',
                'jenis_kelamin' => 'L',
                'jabatan' => 'Perawat IGD',
            ],
            [
                'nik' => '19920316007',
                'nama' => 'Maya Sari, A.Md.Kep',
                'email' => 'paramedis3@dokterku.com',
                'tanggal_lahir' => '1992-03-16',
                'jenis_kelamin' => 'P',
                'jabatan' => 'Perawat Rawat Inap',
            ],
            [
                'nik' => '19910808008',
                'nama' => 'Rudi Hartono, A.Md.Kep',
                'email' => 'paramedis4@dokterku.com',
                'tanggal_lahir' => '1991-08-08',
                'jenis_kelamin' => 'L',
                'jabatan' => 'Perawat ICU',
            ]
        ];

        foreach ($paramedisData as $data) {
            $pegawai = Pegawai::firstOrCreate([
                'nik' => $data['nik']
            ], [
                'nama_lengkap' => $data['nama'],
                'email' => $data['email'],
                'tanggal_lahir' => $data['tanggal_lahir'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'jabatan' => $data['jabatan'],
                'jenis_pegawai' => 'Paramedis',
                'aktif' => true,
            ]);

            $paramedisUser = User::firstOrCreate([
                'email' => $data['email']
            ], [
                'name' => $data['nama'],
                'password' => bcrypt('paramedis123'),
                'pegawai_id' => $pegawai->id,
                'is_active' => true,
            ]);
            
            if (!$paramedisUser->hasRole('paramedis')) {
                $paramedisUser->assignRole('paramedis');
            }
        }

        // Non-Paramedis team
        $nonParamedisData = [
            [
                'nik' => '19850630009',
                'nama' => 'Lisa Andriani, A.Md.AK',
                'email' => 'radiologi@dokterku.com',
                'jabatan' => 'Radiografer',
            ],
            [
                'nik' => '19890415010',
                'nama' => 'Tono Sugiarto, A.Md.AK',
                'email' => 'laboratorium@dokterku.com',
                'jabatan' => 'Analis Kesehatan',
            ],
            [
                'nik' => '19870722011',
                'nama' => 'Fitri Handayani, A.Md.Farm',
                'email' => 'farmasi@dokterku.com',
                'jabatan' => 'Asisten Apoteker',
            ]
        ];

        foreach ($nonParamedisData as $data) {
            $pegawai = Pegawai::firstOrCreate([
                'nik' => $data['nik']
            ], [
                'nama_lengkap' => $data['nama'],
                'email' => $data['email'],
                'tanggal_lahir' => $this->faker->dateTimeBetween('-40 years', '-25 years')->format('Y-m-d'),
                'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
                'jabatan' => $data['jabatan'],
                'jenis_pegawai' => 'Non-Paramedis',
                'aktif' => true,
            ]);

            $nonParamedisUser = User::firstOrCreate([
                'email' => $data['email']
            ], [
                'name' => $data['nama'],
                'password' => bcrypt('nonparamedis123'),
                'pegawai_id' => $pegawai->id,
                'is_active' => true,
            ]);
            
            if (!$nonParamedisUser->hasRole('non_paramedis')) {
                $nonParamedisUser->assignRole('non_paramedis');
            }
        }
    }

    private function seedDoctors(): void
    {
        $this->command->info('👨‍⚕️ Creating doctor data...');

        $doctorData = [
            [
                'nama' => 'Dr. Bambang Wirawan, Sp.PD',
                'email' => 'dokter.penyakitdalam@dokterku.com',
                'jabatan' => 'dokter_spesialis',
                'spesialisasi' => 'Penyakit Dalam',
            ],
            [
                'nama' => 'Dr. Sarah Fitria, Sp.A',
                'email' => 'dokter.anak@dokterku.com',
                'jabatan' => 'dokter_spesialis',
                'spesialisasi' => 'Anak',
            ],
            [
                'nama' => 'Dr. Hendra Kusuma',
                'email' => 'dokter.umum1@dokterku.com',
                'jabatan' => 'dokter_umum',
                'spesialisasi' => 'Umum',
            ],
            [
                'nama' => 'Dr. Wulan Sari',
                'email' => 'dokter.umum2@dokterku.com',
                'jabatan' => 'dokter_umum',
                'spesialisasi' => 'Umum',
            ],
            [
                'nama' => 'drg. Imam Santoso',
                'email' => 'dokter.gigi@dokterku.com',
                'jabatan' => 'dokter_gigi',
                'spesialisasi' => 'Gigi dan Mulut',
            ]
        ];

        foreach ($doctorData as $data) {
            $pegawai = Pegawai::firstOrCreate([
                'nama_lengkap' => $data['nama']
            ], [
                'nik' => $this->faker->unique()->numerify('198#########'),
                'email' => $data['email'],
                'tanggal_lahir' => $this->faker->dateTimeBetween('-50 years', '-30 years')->format('Y-m-d'),
                'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
                'jabatan' => ucfirst(str_replace('_', ' ', $data['jabatan'])),
                'jenis_pegawai' => 'Dokter',
                'aktif' => true,
            ]);

            $dokter = Dokter::firstOrCreate([
                'nama_dokter' => $data['nama']
            ], [
                'email' => $data['email'],
                'spesialisasi' => $data['spesialisasi'],
                'jabatan' => $data['jabatan'],
                'aktif' => true,
                'user_id' => null, // Will be set when user is created
            ]);

            $user = User::firstOrCreate([
                'email' => $data['email']
            ], [
                'name' => $data['nama'],
                'password' => bcrypt('dokter123'),
                'pegawai_id' => $pegawai->id,
                'is_active' => true,
            ]);

            // Update dokter with user_id
            $dokter->update(['user_id' => $user->id]);

            // Assign appropriate role
            $role = $data['jabatan'] === 'dokter_gigi' ? 'dokter_gigi' : 'dokter';
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }

    private function seedPatients(): void
    {
        $this->command->info('🏥 Creating patient data...');

        for ($i = 1; $i <= 50; $i++) {
            Pasien::firstOrCreate([
                'nomor_pasien' => sprintf('PSN%05d', $i)
            ], [
                'nama_pasien' => $this->faker->name,
                'tanggal_lahir' => $this->faker->dateTimeBetween('-80 years', '-1 years'),
                'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
                'alamat' => $this->faker->address,
                'no_telepon' => $this->faker->phoneNumber,
                'jenis_pasien' => $this->faker->randomElement(['umum', 'bpjs', 'asuransi']),
                'nomor_bpjs' => $this->faker->optional(0.6)->numerify('000########'),
                'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ]);
        }
    }

    private function seedJenisTindakan(): void
    {
        $this->command->info('💉 Creating medical procedure types...');

        $tindakanTypes = [
            ['nama' => 'Konsultasi Dokter Umum', 'tarif' => 150000, 'kategori' => 'konsultasi'],
            ['nama' => 'Konsultasi Dokter Spesialis', 'tarif' => 300000, 'kategori' => 'konsultasi'],
            ['nama' => 'Pemeriksaan Laboratorium Darah Lengkap', 'tarif' => 85000, 'kategori' => 'laboratorium'],
            ['nama' => 'Rontgen Thorax', 'tarif' => 120000, 'kategori' => 'radiologi'],
            ['nama' => 'USG Abdomen', 'tarif' => 200000, 'kategori' => 'radiologi'],
            ['nama' => 'Injeksi Intramuskular', 'tarif' => 35000, 'kategori' => 'tindakan'],
            ['nama' => 'Infus', 'tarif' => 75000, 'kategori' => 'tindakan'],
            ['nama' => 'Perawatan Luka', 'tarif' => 50000, 'kategori' => 'tindakan'],
            ['nama' => 'Pemberian Obat', 'tarif' => 25000, 'kategori' => 'farmasi'],
            ['nama' => 'Scaling Gigi', 'tarif' => 250000, 'kategori' => 'gigi'],
            ['nama' => 'Tambal Gigi', 'tarif' => 200000, 'kategori' => 'gigi'],
            ['nama' => 'EKG', 'tarif' => 100000, 'kategori' => 'pemeriksaan'],
        ];

        foreach ($tindakanTypes as $type) {
            JenisTindakan::firstOrCreate([
                'nama_tindakan' => $type['nama']
            ], [
                'tarif_dasar' => $type['tarif'],
                'kategori' => $type['kategori'],
                'aktif' => true,
            ]);
        }
    }

    private function seedShiftsAndSchedules(): void
    {
        $this->command->info('⏰ Creating shifts and schedules...');

        // Create shift templates
        $shifts = [
            ['nama' => 'Pagi', 'jam_masuk' => '08:00', 'jam_keluar' => '16:00'],
            ['nama' => 'Siang', 'jam_masuk' => '14:00', 'jam_keluar' => '22:00'],
            ['nama' => 'Malam', 'jam_masuk' => '22:00', 'jam_keluar' => '06:00'],
        ];

        foreach ($shifts as $shift) {
            ShiftTemplate::firstOrCreate([
                'nama_shift' => $shift['nama']
            ], [
                'jam_masuk' => $shift['jam_masuk'],
                'jam_keluar' => $shift['jam_keluar'],
                'aktif' => true,
            ]);

            // Also create legacy Shift records
            Shift::firstOrCreate([
                'nama_shift' => $shift['nama']
            ], [
                'jam_masuk' => $shift['jam_masuk'],
                'jam_keluar' => $shift['jam_keluar'],
                'aktif' => true,
            ]);
        }

        // Create schedules for paramedis for the next month
        $paramedisUsers = User::whereHas('roles', function($q) {
            $q->where('name', 'paramedis');
        })->get();

        $shiftTemplates = ShiftTemplate::all();

        foreach ($paramedisUsers as $user) {
            for ($i = 0; $i < 30; $i++) {
                $tanggal = now()->addDays($i);
                
                // Skip some days randomly (rest days)
                if (rand(1, 10) <= 2) continue;

                JadwalJaga::firstOrCreate([
                    'pegawai_id' => $user->id,
                    'tanggal_jaga' => $tanggal->format('Y-m-d'),
                ], [
                    'shift_template_id' => $shiftTemplates->random()->id,
                    'unit_kerja' => $this->faker->randomElement(['IGD', 'Rawat Inap', 'ICU', 'Ruang Tindakan']),
                    'status_jaga' => 'scheduled',
                ]);
            }
        }
    }

    private function seedTindakan(): void
    {
        $this->command->info('💊 Creating medical procedures data...');

        $paramedisIds = Pegawai::where('jenis_pegawai', 'Paramedis')->pluck('id')->toArray();
        $nonParamedisIds = Pegawai::where('jenis_pegawai', 'Non-Paramedis')->pluck('id')->toArray();
        $dokterIds = Dokter::pluck('id')->toArray();
        $pasienIds = Pasien::pluck('id')->toArray();
        $jenisTindakanIds = JenisTindakan::pluck('id')->toArray();
        $petugasUser = User::whereHas('roles', function($q) {
            $q->where('name', 'petugas');
        })->first();

        // Create tindakan for the last 3 months
        for ($i = 0; $i < 300; $i++) {
            $tanggal = $this->faker->dateTimeBetween('-3 months', 'now');
            $jenisTindakan = JenisTindakan::find($this->faker->randomElement($jenisTindakanIds));
            
            // Determine which staff involved based on procedure type
            $dokter_id = null;
            $paramedis_id = null;
            $non_paramedis_id = null;

            switch ($jenisTindakan->kategori) {
                case 'konsultasi':
                    $dokter_id = $this->faker->randomElement($dokterIds);
                    $paramedis_id = $this->faker->randomElement($paramedisIds);
                    break;
                case 'laboratorium':
                case 'radiologi':
                    $non_paramedis_id = $this->faker->randomElement($nonParamedisIds);
                    $paramedis_id = $this->faker->randomElement($paramedisIds);
                    break;
                case 'tindakan':
                case 'farmasi':
                    $paramedis_id = $this->faker->randomElement($paramedisIds);
                    break;
                case 'gigi':
                    $dokter_id = Dokter::where('jabatan', 'dokter_gigi')->first()?->id;
                    $paramedis_id = $this->faker->randomElement($paramedisIds);
                    break;
            }

            // Calculate fees based on tarif
            $tarif = $jenisTindakan->tarif_dasar;
            $jasa_dokter = $dokter_id ? $tarif * 0.4 : 0;
            $jasa_paramedis = $paramedis_id ? $tarif * 0.3 : 0;
            $jasa_non_paramedis = $non_paramedis_id ? $tarif * 0.2 : 0;

            Tindakan::create([
                'pasien_id' => $this->faker->randomElement($pasienIds),
                'jenis_tindakan_id' => $jenisTindakan->id,
                'dokter_id' => $dokter_id,
                'paramedis_id' => $paramedis_id,
                'non_paramedis_id' => $non_paramedis_id,
                'tanggal_tindakan' => $tanggal,
                'tarif' => $tarif,
                'jasa_dokter' => $jasa_dokter,
                'jasa_paramedis' => $jasa_paramedis,
                'jasa_non_paramedis' => $jasa_non_paramedis,
                'catatan' => $this->faker->optional(0.3)->sentence,
                'status' => 'completed',
                'status_validasi' => $this->faker->randomElement(['pending', 'disetujui', 'ditolak']),
                'input_by' => $petugasUser?->id,
                'created_at' => $tanggal,
                'updated_at' => $tanggal,
            ]);
        }
    }

    private function seedAttendance(): void
    {
        $this->command->info('📅 Creating attendance data...');

        $allUsers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['paramedis', 'petugas', 'bendahara', 'manajer']);
        })->get();

        // Create attendance for last 30 days
        for ($i = 0; $i < 30; $i++) {
            $date = now()->subDays($i);
            
            // Skip weekends randomly
            if ($date->isWeekend() && rand(1, 10) <= 7) continue;

            foreach ($allUsers as $user) {
                // Random absence (10% chance)
                if (rand(1, 10) <= 1) continue;

                $checkIn = $date->copy()->hour(8)->minute(rand(0, 30));
                $checkOut = $checkIn->copy()->addHours(8)->addMinutes(rand(0, 60));

                Attendance::firstOrCreate([
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                ], [
                    'time_in' => $checkIn,
                    'time_out' => $checkOut,
                    'location_in' => 'Klinik Dokterku',
                    'location_out' => 'Klinik Dokterku',
                    'latitude_in' => -7.250445,
                    'longitude_in' => 112.768845,
                    'latitude_out' => -7.250445,
                    'longitude_out' => 112.768845,
                    'status' => rand(1, 10) <= 8 ? 'on_time' : 'late',
                ]);
            }
        }
    }

    private function seedFinancialRecords(): void
    {
        $this->command->info('💰 Creating financial records...');

        $bendaharaUser = User::whereHas('roles', function($q) {
            $q->where('name', 'bendahara');
        })->first();

        // Create pendapatan records based on tindakan
        $tindakanData = Tindakan::where('status_validasi', 'disetujui')
            ->selectRaw('DATE(tanggal_tindakan) as tanggal, SUM(tarif) as total_tarif, COUNT(*) as jumlah')
            ->groupBy('tanggal')
            ->get();

        foreach ($tindakanData as $data) {
            Pendapatan::firstOrCreate([
                'tanggal' => $data->tanggal,
                'sumber_pendapatan' => 'Tindakan Medis',
            ], [
                'kode_pendapatan' => 'PEN-' . date('Ymd', strtotime($data->tanggal)) . '-001',
                'nama_pendapatan' => 'Pendapatan Tindakan Medis',
                'nominal' => $data->total_tarif,
                'kategori' => 'medis',
                'keterangan' => "Pendapatan dari {$data->jumlah} tindakan medis",
                'is_aktif' => true,
                'status_validasi' => 'disetujui',
                'input_by' => $bendaharaUser?->id,
                'validasi_by' => $bendaharaUser?->id,
                'validasi_at' => now(),
            ]);
        }

        // Create pengeluaran records
        $pengeluaranTypes = [
            'Gaji Pegawai' => 15000000,
            'Listrik' => 2500000,
            'Air' => 800000,
            'Obat-obatan' => 5000000,
            'Alat Kesehatan' => 3000000,
            'Maintenance' => 1500000,
        ];

        for ($i = 0; $i < 30; $i++) {
            $date = now()->subDays($i);
            
            if (rand(1, 10) <= 3) { // 30% chance of expense per day
                $type = array_rand($pengeluaranTypes);
                $nominal = $pengeluaranTypes[$type] + rand(-500000, 500000);

                Pengeluaran::firstOrCreate([
                    'tanggal' => $date->format('Y-m-d'),
                    'nama_pengeluaran' => $type,
                ], [
                    'kode_pengeluaran' => 'PGL-' . $date->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'nominal' => $nominal,
                    'kategori' => 'operasional',
                    'keterangan' => "Pengeluaran untuk {$type} bulan " . $date->format('M Y'),
                    'status_validasi' => 'disetujui',
                    'input_by' => $bendaharaUser?->id,
                    'validasi_by' => $bendaharaUser?->id,
                    'validasi_at' => $date,
                ]);
            }
        }
    }

    private function seedDailySummaries(): void
    {
        $this->command->info('📊 Creating daily summaries...');

        // Create daily summaries for bendahara dashboard
        for ($i = 0; $i < 60; $i++) {
            $date = now()->subDays($i);
            
            // Skip weekends
            if ($date->isWeekend()) continue;

            // Pendapatan Harian
            $dailyRevenue = Pendapatan::whereDate('tanggal', $date->format('Y-m-d'))
                ->sum('nominal');

            if ($dailyRevenue > 0) {
                PendapatanHarian::firstOrCreate([
                    'tanggal_input' => $date->format('Y-m-d'),
                ], [
                    'nominal' => $dailyRevenue,
                    'keterangan' => 'Pendapatan harian dari tindakan medis',
                    'status' => 'validated',
                    'validated_by' => 1,
                    'validated_at' => $date->addHours(rand(1, 8)),
                ]);
            }

            // Pengeluaran Harian
            $dailyExpense = Pengeluaran::whereDate('tanggal', $date->format('Y-m-d'))
                ->sum('nominal');

            if ($dailyExpense > 0) {
                PengeluaranHarian::firstOrCreate([
                    'tanggal_input' => $date->format('Y-m-d'),
                ], [
                    'nominal' => $dailyExpense,
                    'keterangan' => 'Pengeluaran operasional harian',
                    'status' => 'validated',
                    'validated_by' => 1,
                    'validated_at' => $date->addHours(rand(1, 8)),
                ]);
            }

            // Jumlah Pasien Harian
            $dailyPatients = Tindakan::whereDate('tanggal_tindakan', $date->format('Y-m-d'))
                ->distinct('pasien_id')
                ->count();

            if ($dailyPatients > 0) {
                JumlahPasienHarian::firstOrCreate([
                    'tanggal' => $date->format('Y-m-d'),
                ], [
                    'jumlah_pasien_umum' => rand(floor($dailyPatients * 0.6), $dailyPatients),
                    'jumlah_pasien_bpjs' => rand(0, floor($dailyPatients * 0.4)),
                    'status_validasi' => 'validated',
                    'validated_by' => 1,
                    'validated_at' => $date->addHours(rand(1, 8)),
                ]);
            }
        }
    }
}
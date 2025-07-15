<?php

namespace Database\Seeders;

use App\Models\WorkLocation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Only run in development environment
        if (!app()->environment(['local', 'development'])) {
            $this->command->info('WorkLocation seeder skipped in production environment');
            return;
        }
        // Create main office (Klinik Dokterku headquarters)
        WorkLocation::create([
            'name' => 'Kantor Pusat Klinik Dokterku',
            'description' => 'Kantor pusat dan klinik utama Klinik Dokterku yang menyediakan layanan kesehatan komprehensif.',
            'address' => 'Jl. Veteran No. 123, Ketawanggede, Kec. Lowokwaru, Kota Malang, Jawa Timur 65145',
            'latitude' => -7.9666,
            'longitude' => 112.6326,
            'radius_meters' => 100,
            'is_active' => true,
            'location_type' => 'main_office',
            'allowed_shifts' => ['pagi', 'siang', 'malam'],
            'working_hours' => [
                'monday' => ['start' => '08:00', 'end' => '17:00'],
                'tuesday' => ['start' => '08:00', 'end' => '17:00'],
                'wednesday' => ['start' => '08:00', 'end' => '17:00'],
                'thursday' => ['start' => '08:00', 'end' => '17:00'],
                'friday' => ['start' => '08:00', 'end' => '17:00'],
                'saturday' => ['start' => '08:00', 'end' => '14:00'],
            ],
            'tolerance_settings' => [
                'late_tolerance_minutes' => 15,
                'early_departure_tolerance_minutes' => 15,
                'break_time_minutes' => 60,
                'overtime_threshold_minutes' => 480,
            ],
            'contact_person' => 'Dr. Budi Santoso',
            'contact_phone' => '+62 341 123456',
            'require_photo' => true,
            'strict_geofence' => true,
            'gps_accuracy_required' => 20,
        ]);

        // Create Malang Kota branch
        WorkLocation::create([
            'name' => 'Klinik Dokterku Cabang Malang Kota',
            'description' => 'Cabang klinik di pusat kota Malang yang melayani masyarakat urban.',
            'address' => 'Jl. Ijen No. 45, Oro-oro Dowo, Kec. Klojen, Kota Malang, Jawa Timur 65119',
            'latitude' => -7.9755,
            'longitude' => 112.6276,
            'radius_meters' => 75,
            'is_active' => true,
            'location_type' => 'branch_office',
            'allowed_shifts' => ['pagi', 'siang'],
            'working_hours' => [
                'monday' => ['start' => '08:00', 'end' => '16:00'],
                'tuesday' => ['start' => '08:00', 'end' => '16:00'],
                'wednesday' => ['start' => '08:00', 'end' => '16:00'],
                'thursday' => ['start' => '08:00', 'end' => '16:00'],
                'friday' => ['start' => '08:00', 'end' => '16:00'],
                'saturday' => ['start' => '08:00', 'end' => '12:00'],
            ],
            'tolerance_settings' => [
                'late_tolerance_minutes' => 10,
                'early_departure_tolerance_minutes' => 10,
                'break_time_minutes' => 45,
                'overtime_threshold_minutes' => 480,
            ],
            'contact_person' => 'Dr. Sari Wijaya',
            'contact_phone' => '+62 341 234567',
            'require_photo' => true,
            'strict_geofence' => true,
            'gps_accuracy_required' => 15,
        ]);

        // Create Batu branch
        WorkLocation::create([
            'name' => 'Klinik Dokterku Cabang Batu',
            'description' => 'Cabang klinik di Kota Batu yang melayani wisatawan dan masyarakat setempat.',
            'address' => 'Jl. Panglima Sudirman No. 67, Songgokerto, Kec. Batu, Kota Batu, Jawa Timur 65314',
            'latitude' => -7.8706,
            'longitude' => 112.5276,
            'radius_meters' => 100,
            'is_active' => true,
            'location_type' => 'branch_office',
            'allowed_shifts' => ['pagi', 'siang'],
            'working_hours' => [
                'monday' => ['start' => '07:00', 'end' => '15:00'],
                'tuesday' => ['start' => '07:00', 'end' => '15:00'],
                'wednesday' => ['start' => '07:00', 'end' => '15:00'],
                'thursday' => ['start' => '07:00', 'end' => '15:00'],
                'friday' => ['start' => '07:00', 'end' => '15:00'],
                'saturday' => ['start' => '07:00', 'end' => '12:00'],
            ],
            'tolerance_settings' => [
                'late_tolerance_minutes' => 20,
                'early_departure_tolerance_minutes' => 20,
                'break_time_minutes' => 60,
                'overtime_threshold_minutes' => 480,
            ],
            'contact_person' => 'Dr. Ahmad Fauzi',
            'contact_phone' => '+62 341 345678',
            'require_photo' => true,
            'strict_geofence' => false, // More flexible for tourist area
            'gps_accuracy_required' => 25,
        ]);

        // Create Blimbing branch
        WorkLocation::create([
            'name' => 'Klinik Dokterku Cabang Blimbing',
            'description' => 'Cabang klinik di Kecamatan Blimbing yang melayani masyarakat suburban.',
            'address' => 'Jl. Letjen Sutoyo No. 89, Blimbing, Kec. Blimbing, Kota Malang, Jawa Timur 65126',
            'latitude' => -7.9344,
            'longitude' => 112.6576,
            'radius_meters' => 80,
            'is_active' => true,
            'location_type' => 'branch_office',
            'allowed_shifts' => ['pagi', 'siang'],
            'working_hours' => [
                'monday' => ['start' => '08:00', 'end' => '16:00'],
                'tuesday' => ['start' => '08:00', 'end' => '16:00'],
                'wednesday' => ['start' => '08:00', 'end' => '16:00'],
                'thursday' => ['start' => '08:00', 'end' => '16:00'],
                'friday' => ['start' => '08:00', 'end' => '16:00'],
                'saturday' => ['start' => '08:00', 'end' => '13:00'],
            ],
            'tolerance_settings' => [
                'late_tolerance_minutes' => 15,
                'early_departure_tolerance_minutes' => 15,
                'break_time_minutes' => 60,
                'overtime_threshold_minutes' => 480,
            ],
            'contact_person' => 'Dr. Lisa Permata',
            'contact_phone' => '+62 341 456789',
            'require_photo' => true,
            'strict_geofence' => true,
            'gps_accuracy_required' => 20,
        ]);

        // Create Apotek Dokterku (Pharmacy)
        WorkLocation::create([
            'name' => 'Apotek Dokterku',
            'description' => 'Apotek resmi Klinik Dokterku yang menyediakan obat-obatan dan produk kesehatan.',
            'address' => 'Jl. Soekarno Hatta No. 12, Mojolangu, Kec. Lowokwaru, Kota Malang, Jawa Timur 65142',
            'latitude' => -7.9444,
            'longitude' => 112.6126,
            'radius_meters' => 50,
            'is_active' => true,
            'location_type' => 'branch_office',
            'allowed_shifts' => ['pagi', 'siang'],
            'working_hours' => [
                'monday' => ['start' => '08:00', 'end' => '20:00'],
                'tuesday' => ['start' => '08:00', 'end' => '20:00'],
                'wednesday' => ['start' => '08:00', 'end' => '20:00'],
                'thursday' => ['start' => '08:00', 'end' => '20:00'],
                'friday' => ['start' => '08:00', 'end' => '20:00'],
                'saturday' => ['start' => '08:00', 'end' => '18:00'],
                'sunday' => ['start' => '10:00', 'end' => '16:00'],
            ],
            'tolerance_settings' => [
                'late_tolerance_minutes' => 10,
                'early_departure_tolerance_minutes' => 10,
                'break_time_minutes' => 30,
                'overtime_threshold_minutes' => 480,
            ],
            'contact_person' => 'Apt. Maria Kusuma',
            'contact_phone' => '+62 341 567890',
            'require_photo' => true,
            'strict_geofence' => true,
            'gps_accuracy_required' => 15,
        ]);

        // Create Laboratory
        WorkLocation::create([
            'name' => 'Laboratorium Klinik Dokterku',
            'description' => 'Laboratorium klinik untuk pemeriksaan diagnostik dan analisis medis.',
            'address' => 'Jl. Mayjen Haryono No. 34, Dinoyo, Kec. Lowokwaru, Kota Malang, Jawa Timur 65144',
            'latitude' => -7.9566,
            'longitude' => 112.6226,
            'radius_meters' => 60,
            'is_active' => true,
            'location_type' => 'project_site',
            'allowed_shifts' => ['pagi', 'siang'],
            'working_hours' => [
                'monday' => ['start' => '07:00', 'end' => '15:00'],
                'tuesday' => ['start' => '07:00', 'end' => '15:00'],
                'wednesday' => ['start' => '07:00', 'end' => '15:00'],
                'thursday' => ['start' => '07:00', 'end' => '15:00'],
                'friday' => ['start' => '07:00', 'end' => '15:00'],
                'saturday' => ['start' => '07:00', 'end' => '12:00'],
            ],
            'tolerance_settings' => [
                'late_tolerance_minutes' => 5,
                'early_departure_tolerance_minutes' => 5,
                'break_time_minutes' => 45,
                'overtime_threshold_minutes' => 480,
            ],
            'contact_person' => 'Dr. Analis Bambang',
            'contact_phone' => '+62 341 678901',
            'require_photo' => true,
            'strict_geofence' => true,
            'gps_accuracy_required' => 10, // High accuracy for lab work
        ]);

        // Create Mobile Health Unit
        WorkLocation::create([
            'name' => 'Unit Kesehatan Mobile Dokterku',
            'description' => 'Unit kesehatan mobile untuk layanan kesehatan di daerah terpencil dan acara khusus.',
            'address' => 'Lokasi berubah sesuai jadwal pelayanan (Mobile Unit)',
            'latitude' => -7.9833,
            'longitude' => 112.6426,
            'radius_meters' => 200,
            'is_active' => true,
            'location_type' => 'mobile_location',
            'allowed_shifts' => ['pagi', 'siang'],
            'working_hours' => null, // Flexible working hours
            'tolerance_settings' => [
                'late_tolerance_minutes' => 30,
                'early_departure_tolerance_minutes' => 30,
                'break_time_minutes' => 60,
                'overtime_threshold_minutes' => 600,
            ],
            'contact_person' => 'Dr. Mobile Team',
            'contact_phone' => '+62 341 789012',
            'require_photo' => false, // More flexible for mobile unit
            'strict_geofence' => false,
            'gps_accuracy_required' => 50, // More tolerant due to mobility
        ]);

        // Create Partner Hospital Location
        WorkLocation::create([
            'name' => 'RS Mitra Dokterku',
            'description' => 'Rumah sakit mitra untuk rujukan dan kerjasama pelayanan kesehatan.',
            'address' => 'Jl. Raya Tlogomas No. 56, Tlogomas, Kec. Lowokwaru, Kota Malang, Jawa Timur 65144',
            'latitude' => -7.9444,
            'longitude' => 112.6476,
            'radius_meters' => 150,
            'is_active' => true,
            'location_type' => 'client_office',
            'allowed_shifts' => ['pagi', 'siang', 'malam'],
            'working_hours' => [
                'monday' => ['start' => '06:00', 'end' => '18:00'],
                'tuesday' => ['start' => '06:00', 'end' => '18:00'],
                'wednesday' => ['start' => '06:00', 'end' => '18:00'],
                'thursday' => ['start' => '06:00', 'end' => '18:00'],
                'friday' => ['start' => '06:00', 'end' => '18:00'],
                'saturday' => ['start' => '06:00', 'end' => '18:00'],
                'sunday' => ['start' => '08:00', 'end' => '16:00'],
            ],
            'tolerance_settings' => [
                'late_tolerance_minutes' => 10,
                'early_departure_tolerance_minutes' => 10,
                'break_time_minutes' => 45,
                'overtime_threshold_minutes' => 480,
            ],
            'contact_person' => 'Dr. Mitra Liaison',
            'contact_phone' => '+62 341 890123',
            'require_photo' => true,
            'strict_geofence' => true,
            'gps_accuracy_required' => 20,
        ]);

        // Create inactive location for testing
        WorkLocation::create([
            'name' => 'Klinik Dokterku Cabang Lawang (Tutup Sementara)',
            'description' => 'Cabang yang ditutup sementara untuk renovasi.',
            'address' => 'Jl. Raya Lawang No. 78, Lawang, Kabupaten Malang, Jawa Timur 65215',
            'latitude' => -7.8355,
            'longitude' => 112.6944,
            'radius_meters' => 100,
            'is_active' => false, // Inactive for testing
            'location_type' => 'branch_office',
            'allowed_shifts' => ['pagi', 'siang'],
            'working_hours' => null,
            'tolerance_settings' => [
                'late_tolerance_minutes' => 15,
                'early_departure_tolerance_minutes' => 15,
                'break_time_minutes' => 60,
                'overtime_threshold_minutes' => 480,
            ],
            'contact_person' => 'Maintenance Team',
            'contact_phone' => '+62 341 901234',
            'require_photo' => true,
            'strict_geofence' => true,
            'gps_accuracy_required' => 20,
        ]);

        $this->command->info('WorkLocationSeeder completed! Created 9 work locations in Malang area.');
    }
}
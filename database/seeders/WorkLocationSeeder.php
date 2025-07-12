<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorkLocation;

class WorkLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Kantor Pusat Jakarta',
                'description' => 'Kantor pusat utama di Jakarta Pusat dengan fasilitas lengkap dan akses mudah.',
                'address' => 'Jl. Sudirman No. 123, Jakarta Pusat, DKI Jakarta 10270',
                'latitude' => -6.207840,
                'longitude' => 106.823141,
                'radius_meters' => 150,
                'location_type' => 'main_office',
                'allowed_shifts' => ['Pagi', 'Siang'],
                'working_hours' => [
                    'monday' => ['start' => '08:00', 'end' => '17:00'],
                    'tuesday' => ['start' => '08:00', 'end' => '17:00'],
                    'wednesday' => ['start' => '08:00', 'end' => '17:00'],
                    'thursday' => ['start' => '08:00', 'end' => '17:00'],
                    'friday' => ['start' => '08:00', 'end' => '17:00'],
                    'saturday' => ['start' => '08:00', 'end' => '12:00'],
                ],
                'tolerance_settings' => [
                    'late_tolerance_minutes' => 15,
                    'early_departure_tolerance_minutes' => 15,
                    'break_time_minutes' => 60,
                    'overtime_threshold_minutes' => 480,
                ],
                'contact_person' => 'Budi Santoso',
                'contact_phone' => '021-12345678',
                'require_photo' => true,
                'strict_geofence' => true,
                'gps_accuracy_required' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Kantor Cabang Surabaya',
                'description' => 'Kantor cabang di Surabaya untuk melayani wilayah Jawa Timur.',
                'address' => 'Jl. Pemuda No. 45, Surabaya, Jawa Timur 60271',
                'latitude' => -7.257472,
                'longitude' => 112.752090,
                'radius_meters' => 100,
                'location_type' => 'branch_office',
                'allowed_shifts' => ['Pagi', 'Siang'],
                'working_hours' => [
                    'monday' => ['start' => '08:00', 'end' => '17:00'],
                    'tuesday' => ['start' => '08:00', 'end' => '17:00'],
                    'wednesday' => ['start' => '08:00', 'end' => '17:00'],
                    'thursday' => ['start' => '08:00', 'end' => '17:00'],
                    'friday' => ['start' => '08:00', 'end' => '17:00'],
                    'saturday' => ['start' => '08:00', 'end' => '12:00'],
                ],
                'tolerance_settings' => [
                    'late_tolerance_minutes' => 10,
                    'early_departure_tolerance_minutes' => 10,
                    'break_time_minutes' => 60,
                    'overtime_threshold_minutes' => 480,
                ],
                'contact_person' => 'Siti Aminah',
                'contact_phone' => '031-87654321',
                'require_photo' => true,
                'strict_geofence' => true,
                'gps_accuracy_required' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Proyek Mall Grand Indonesia',
                'description' => 'Lokasi proyek pembangunan sistem di Mall Grand Indonesia.',
                'address' => 'Jl. M.H. Thamrin No. 1, Jakarta Pusat, DKI Jakarta 10310',
                'latitude' => -6.195740,
                'longitude' => 106.823050,
                'radius_meters' => 200,
                'location_type' => 'project_site',
                'allowed_shifts' => ['Pagi', 'Siang', 'Malam'],
                'working_hours' => [
                    'monday' => ['start' => '07:00', 'end' => '21:00'],
                    'tuesday' => ['start' => '07:00', 'end' => '21:00'],
                    'wednesday' => ['start' => '07:00', 'end' => '21:00'],
                    'thursday' => ['start' => '07:00', 'end' => '21:00'],
                    'friday' => ['start' => '07:00', 'end' => '21:00'],
                    'saturday' => ['start' => '07:00', 'end' => '21:00'],
                    'sunday' => ['start' => '07:00', 'end' => '21:00'],
                ],
                'tolerance_settings' => [
                    'late_tolerance_minutes' => 20,
                    'early_departure_tolerance_minutes' => 5,
                    'break_time_minutes' => 45,
                    'overtime_threshold_minutes' => 600,
                ],
                'contact_person' => 'Ahmad Wijaya',
                'contact_phone' => '0812-3456-7890',
                'require_photo' => true,
                'strict_geofence' => false,
                'gps_accuracy_required' => 25,
                'is_active' => true,
            ],
            [
                'name' => 'Lokasi Mobile Tim Support',
                'description' => 'Lokasi fleksibel untuk tim support yang bekerja mobile di area Jakarta.',
                'address' => 'Area Jakarta Raya (Mobile Location)',
                'latitude' => -6.175110,
                'longitude' => 106.827050,
                'radius_meters' => 500,
                'location_type' => 'mobile_location',
                'allowed_shifts' => ['Pagi', 'Siang', 'Malam'],
                'working_hours' => [
                    'monday' => ['start' => '24/7', 'end' => '24/7'],
                    'tuesday' => ['start' => '24/7', 'end' => '24/7'],
                    'wednesday' => ['start' => '24/7', 'end' => '24/7'],
                    'thursday' => ['start' => '24/7', 'end' => '24/7'],
                    'friday' => ['start' => '24/7', 'end' => '24/7'],
                    'saturday' => ['start' => '24/7', 'end' => '24/7'],
                    'sunday' => ['start' => '24/7', 'end' => '24/7'],
                ],
                'tolerance_settings' => [
                    'late_tolerance_minutes' => 30,
                    'early_departure_tolerance_minutes' => 30,
                    'break_time_minutes' => 30,
                    'overtime_threshold_minutes' => 480,
                ],
                'contact_person' => 'Dedy Kurniawan',
                'contact_phone' => '0856-1234-5678',
                'require_photo' => true,
                'strict_geofence' => false,
                'gps_accuracy_required' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Kantor Klien Bank Mandiri',
                'description' => 'Lokasi kerja di kantor klien Bank Mandiri untuk tim konsultasi.',
                'address' => 'Jl. Gatot Subroto Kav. 36-38, Jakarta Selatan, DKI Jakarta 12190',
                'latitude' => -6.218480,
                'longitude' => 106.816666,
                'radius_meters' => 75,
                'location_type' => 'client_office',
                'allowed_shifts' => ['Pagi'],
                'working_hours' => [
                    'monday' => ['start' => '09:00', 'end' => '16:00'],
                    'tuesday' => ['start' => '09:00', 'end' => '16:00'],
                    'wednesday' => ['start' => '09:00', 'end' => '16:00'],
                    'thursday' => ['start' => '09:00', 'end' => '16:00'],
                    'friday' => ['start' => '09:00', 'end' => '16:00'],
                ],
                'tolerance_settings' => [
                    'late_tolerance_minutes' => 5,
                    'early_departure_tolerance_minutes' => 5,
                    'break_time_minutes' => 60,
                    'overtime_threshold_minutes' => 420,
                ],
                'contact_person' => 'Lisa Permata',
                'contact_phone' => '0821-9876-5432',
                'require_photo' => true,
                'strict_geofence' => true,
                'gps_accuracy_required' => 10,
                'is_active' => false, // Tidak aktif karena proyek selesai
            ],
        ];

        foreach ($locations as $location) {
            WorkLocation::create($location);
        }
    }
}
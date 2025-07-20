<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nama lokasi kerja');
            $table->text('description')->nullable()->comment('Deskripsi lokasi');
            $table->string('address')->comment('Alamat lengkap');
            $table->decimal('latitude', 10, 8)->comment('Koordinat latitude');
            $table->decimal('longitude', 11, 8)->comment('Koordinat longitude');
            $table->integer('radius_meters')->default(100)->comment('Radius geofence dalam meter');
            $table->boolean('is_active')->default(true)->comment('Status aktif lokasi');
            $table->enum('location_type', ['main_office', 'branch_office', 'project_site', 'mobile_location', 'client_office'])
                ->default('main_office')
                ->comment('Jenis lokasi kerja');
            $table->json('allowed_shifts')->nullable()->comment('Shift yang diizinkan di lokasi ini');
            $table->json('working_hours')->nullable()->comment('Jam kerja untuk lokasi ini');
            $table->json('tolerance_settings')->nullable()->comment('Pengaturan toleransi khusus');
            $table->string('contact_person')->nullable()->comment('Penanggung jawab lokasi');
            $table->string('contact_phone')->nullable()->comment('Nomor telepon kontak');
            $table->boolean('require_photo')->default(true)->comment('Wajib foto saat absen');
            $table->boolean('strict_geofence')->default(true)->comment('Geofence ketat atau fleksibel');
            $table->integer('gps_accuracy_required')->default(20)->comment('Akurasi GPS minimum (meter)');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['is_active']);
            $table->index(['location_type']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_locations');
    }
};
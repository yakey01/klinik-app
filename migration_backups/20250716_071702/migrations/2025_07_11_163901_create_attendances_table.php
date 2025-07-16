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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->time('time_in');
            $table->time('time_out')->nullable();
            $table->string('latlon_in'); // "latitude,longitude" format
            $table->string('latlon_out')->nullable(); // "latitude,longitude" format
            $table->string('location_name_in')->nullable(); // Nama lokasi readable
            $table->string('location_name_out')->nullable(); // Nama lokasi readable
            $table->string('device_info')->nullable(); // Info device untuk tracking
            $table->string('photo_in')->nullable(); // Selfie saat check-in
            $table->string('photo_out')->nullable(); // Selfie saat check-out
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->enum('status', ['present', 'late', 'incomplete'])->default('present');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'date']);
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
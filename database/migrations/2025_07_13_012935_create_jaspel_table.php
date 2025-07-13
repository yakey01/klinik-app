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
        Schema::create('jaspel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tindakan_id')->nullable()->constrained('tindakan')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('jenis_jaspel')->default('tindakan'); // tindakan, shift, dll
            $table->decimal('nominal', 15, 2)->default(0);
            $table->decimal('total_jaspel', 15, 2)->default(0); // Total field for widget
            $table->date('tanggal');
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
            $table->foreignId('input_by')->constrained('users')->onDelete('cascade');
            $table->enum('status_validasi', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->foreignId('validasi_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('validasi_at')->nullable();
            $table->text('catatan_validasi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['user_id', 'tanggal']);
            $table->index(['status_validasi']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jaspel');
    }
};

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
        Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
            $table->enum('status_validasi', ['pending', 'approved', 'rejected'])->default('pending')->after('input_by');
            $table->foreignId('validasi_by')->nullable()->constrained('users')->onDelete('set null')->after('status_validasi');
            $table->timestamp('validasi_at')->nullable()->after('validasi_by');
            $table->text('catatan_validasi')->nullable()->after('validasi_at');
            
            // Index untuk performa query validasi
            $table->index('status_validasi');
            $table->index('validasi_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jumlah_pasien_harians', function (Blueprint $table) {
            $table->dropIndex(['status_validasi']);
            $table->dropIndex(['validasi_by']);
            $table->dropForeign(['validasi_by']);
            $table->dropColumn(['status_validasi', 'validasi_by', 'validasi_at', 'catatan_validasi']);
        });
    }
};

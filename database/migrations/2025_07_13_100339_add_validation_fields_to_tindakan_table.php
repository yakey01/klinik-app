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
        Schema::table('tindakan', function (Blueprint $table) {
            $table->enum('status_validasi', ['pending', 'disetujui', 'ditolak'])
                  ->default('pending')
                  ->after('status');
            $table->foreignId('validated_by')
                  ->nullable()
                  ->after('status_validasi')
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('validated_at')
                  ->nullable()
                  ->after('validated_by');
            $table->text('komentar_validasi')
                  ->nullable()
                  ->after('validated_at');
            
            // Add index for validation status
            $table->index('status_validasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tindakan', function (Blueprint $table) {
            $table->dropIndex(['status_validasi']);
            $table->dropForeign(['validated_by']);
            $table->dropColumn(['status_validasi', 'validated_by', 'validated_at', 'komentar_validasi']);
        });
    }
};

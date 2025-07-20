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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('pegawai_id')->nullable()->after('role_id');
            // Removed foreign key constraint to avoid circular dependency
            // The relationship is maintained at the application level
            $table->index('pegawai_id', 'users_pegawai_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if index exists before dropping
            if (Schema::hasColumn('users', 'pegawai_id')) {
                try {
                    $table->dropIndex('users_pegawai_id_index');
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }
                $table->dropColumn('pegawai_id');
            }
        });
    }
};

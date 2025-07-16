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
            $table->foreignId('role_id')->after('id')->constrained('roles')->onDelete('cascade');
            $table->string('nip')->unique()->nullable()->after('email');
            $table->string('no_telepon')->nullable()->after('nip');
            $table->date('tanggal_bergabung')->nullable()->after('no_telepon');
            $table->boolean('is_active')->default(true)->after('tanggal_bergabung');
            $table->softDeletes()->after('updated_at');
            
            $table->index('role_id');
            $table->index('nip');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id']);
            $table->dropIndex(['nip']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['role_id', 'nip', 'no_telepon', 'tanggal_bergabung', 'is_active']);
            $table->dropSoftDeletes();
        });
    }
};

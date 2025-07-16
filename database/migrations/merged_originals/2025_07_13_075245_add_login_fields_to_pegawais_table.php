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
        Schema::table('pegawais', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('aktif');
            $table->string('password')->nullable()->after('username');
            $table->enum('status_akun', ['Aktif', 'Suspend'])->default('Aktif')->after('password');
            $table->timestamp('password_changed_at')->nullable()->after('status_akun');
            $table->unsignedBigInteger('password_reset_by')->nullable()->after('password_changed_at');
            
            $table->index(['username']);
            $table->index(['status_akun']);
            
            $table->foreign('password_reset_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropForeign(['password_reset_by']);
            $table->dropIndex(['username']);
            $table->dropIndex(['status_akun']);
            $table->dropColumn(['username', 'password', 'status_akun', 'password_changed_at', 'password_reset_by']);
        });
    }
};

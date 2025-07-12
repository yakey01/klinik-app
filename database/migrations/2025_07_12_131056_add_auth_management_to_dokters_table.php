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
        Schema::table('dokters', function (Blueprint $table) {
            // Auth management fields
            $table->string('username')->unique()->nullable()->after('email');
            $table->string('password')->nullable()->after('username');
            $table->enum('status_akun', ['Aktif', 'Suspend'])->default('Aktif')->after('password');
            $table->timestamp('password_changed_at')->nullable()->after('status_akun');
            $table->timestamp('last_login_at')->nullable()->after('password_changed_at');
            $table->unsignedBigInteger('password_reset_by')->nullable()->after('last_login_at');
            
            // Foreign key for password reset tracking
            $table->foreign('password_reset_by')->references('id')->on('users');
            
            // Indexes for performance
            $table->index(['username', 'status_akun']);
            $table->index('status_akun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dokters', function (Blueprint $table) {
            $table->dropForeign(['password_reset_by']);
            $table->dropIndex(['username', 'status_akun']);
            $table->dropIndex(['status_akun']);
            $table->dropColumn([
                'username',
                'password', 
                'status_akun',
                'password_changed_at',
                'last_login_at',
                'password_reset_by'
            ]);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MERGED MIGRATION: Users Table Enhancements
 * 
 * This migration consolidates the following original migrations:
 * - 2025_07_11_092700_add_role_id_to_users_table.php
 * - 2025_07_12_225550_add_username_to_users_table.php
 * - 2025_07_15_070054_add_profile_settings_to_users_table.php
 * - 2025_07_15_095251_make_role_id_nullable_in_users_table.php
 * - 2025_07_15_231720_add_pegawai_id_to_users_table.php
 * 
 * Risk Level: LOW
 * Dependencies: roles table must exist
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // From: add_role_id_to_users_table.php & make_role_id_nullable_in_users_table.php
            $table->unsignedBigInteger('role_id')->nullable()->after('email');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            $table->index('role_id', 'users_role_id_index');
            
            // From: add_username_to_users_table.php
            $table->string('username')->unique()->nullable()->after('name');
            $table->index('username', 'users_username_index');
            
            // From: add_profile_settings_to_users_table.php
            $table->json('preferences')->nullable()->after('remember_token');
            $table->string('phone', 20)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('address');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->index('is_active', 'users_is_active_index');
            $table->index('phone', 'users_phone_index');
            
            // From: add_pegawai_id_to_users_table.php
            // NOTE: Removed foreign key constraint to avoid circular dependency
            $table->unsignedBigInteger('pegawai_id')->nullable()->after('role_id');
            $table->index('pegawai_id', 'users_pegawai_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove indexes first
            $table->dropIndex('users_pegawai_id_index');
            $table->dropIndex('users_phone_index');
            $table->dropIndex('users_is_active_index');
            $table->dropIndex('users_username_index');
            $table->dropIndex('users_role_id_index');
            
            // Remove foreign keys
            $table->dropForeign(['role_id']);
            
            // Remove columns
            $table->dropColumn([
                'pegawai_id',
                'last_login_at',
                'is_active',
                'address',
                'phone',
                'preferences',
                'username',
                'role_id'
            ]);
        });
    }
};
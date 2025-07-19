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
            $table->string('nip')->unique()->nullable()->after('username');
            $table->index('username', 'users_username_index');
            $table->index('nip', 'users_nip_index');
            
            // From: add_profile_settings_to_users_table.php
            $table->string('no_telepon', 20)->nullable()->after('nip');
            $table->date('tanggal_bergabung')->nullable()->after('no_telepon');
            $table->json('preferences')->nullable()->after('remember_token');
            $table->string('phone', 20)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('address');
            $table->date('date_of_birth')->nullable()->after('bio');
            $table->enum('gender', ['male', 'female'])->nullable()->after('date_of_birth');
            $table->string('emergency_contact_name')->nullable()->after('gender');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            $table->string('profile_photo_path')->nullable()->after('emergency_contact_phone');
            $table->boolean('is_active')->default(true)->after('profile_photo_path');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->index('is_active', 'users_is_active_index');
            $table->index('phone', 'users_phone_index');
            $table->index('no_telepon', 'users_no_telepon_index');
            $table->index('tanggal_bergabung', 'users_tanggal_bergabung_index');
            
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
            $table->dropIndex('users_tanggal_bergabung_index');
            $table->dropIndex('users_no_telepon_index');
            $table->dropIndex('users_phone_index');
            $table->dropIndex('users_is_active_index');
            $table->dropIndex('users_nip_index');
            $table->dropIndex('users_username_index');
            $table->dropIndex('users_role_id_index');
            
            // Remove foreign keys
            $table->dropForeign(['role_id']);
            
            // Remove columns
            $table->dropColumn([
                'pegawai_id',
                'last_login_at',
                'is_active',
                'profile_photo_path',
                'emergency_contact_phone',
                'emergency_contact_name',
                'gender',
                'date_of_birth',
                'bio',
                'address',
                'phone',
                'preferences',
                'tanggal_bergabung',
                'no_telepon',
                'nip',
                'username',
                'role_id'
            ]);
        });
    }
};
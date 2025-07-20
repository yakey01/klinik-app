<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Indexes for pasien table
        Schema::table('pasien', function (Blueprint $table) {
            $table->index('created_at', 'idx_pasien_created_at');
            $table->index('jenis_kelamin', 'idx_pasien_jenis_kelamin');
            $table->index('tanggal_lahir', 'idx_pasien_tanggal_lahir');
            $table->index(['jenis_kelamin', 'created_at'], 'idx_pasien_gender_created');
            $table->index('no_rekam_medis', 'idx_pasien_no_rekam_medis');
            $table->index('nama', 'idx_pasien_nama');
            $table->index('deleted_at', 'idx_pasien_deleted_at');
        });

        // Indexes for tindakan table
        Schema::table('tindakan', function (Blueprint $table) {
            $table->index('pasien_id', 'idx_tindakan_pasien_id');
            $table->index('dokter_id', 'idx_tindakan_dokter_id');
            $table->index('jenis_tindakan_id', 'idx_tindakan_jenis_tindakan_id');
            $table->index('tanggal_tindakan', 'idx_tindakan_tanggal');
            $table->index('status', 'idx_tindakan_status');
            $table->index('status_validasi', 'idx_tindakan_status_validasi');
            $table->index('created_at', 'idx_tindakan_created_at');
            $table->index('validated_at', 'idx_tindakan_validated_at');
            $table->index('input_by', 'idx_tindakan_input_by');
            $table->index('validated_by', 'idx_tindakan_validated_by');
            $table->index('deleted_at', 'idx_tindakan_deleted_at');
            
            // Composite indexes for common query patterns
            $table->index(['pasien_id', 'tanggal_tindakan'], 'idx_tindakan_pasien_tanggal');
            $table->index(['dokter_id', 'tanggal_tindakan'], 'idx_tindakan_dokter_tanggal');
            $table->index(['status', 'tanggal_tindakan'], 'idx_tindakan_status_tanggal');
            $table->index(['status_validasi', 'created_at'], 'idx_tindakan_validasi_created');
            $table->index(['jenis_tindakan_id', 'status'], 'idx_tindakan_jenis_status');
        });

        // Indexes for pendapatan table
        Schema::table('pendapatan', function (Blueprint $table) {
            $table->index('tindakan_id', 'idx_pendapatan_tindakan_id');
            $table->index('kategori', 'idx_pendapatan_kategori');
            $table->index('status', 'idx_pendapatan_status');
            $table->index('created_at', 'idx_pendapatan_created_at');
            $table->index('input_by', 'idx_pendapatan_input_by');
            $table->index('validasi_by', 'idx_pendapatan_validasi_by');
            $table->index('validated_at', 'idx_pendapatan_validated_at');
            $table->index('deleted_at', 'idx_pendapatan_deleted_at');
            
            // Composite indexes for financial queries
            $table->index(['status', 'created_at'], 'idx_pendapatan_status_created');
            $table->index(['kategori', 'status'], 'idx_pendapatan_kategori_status');
            $table->index(['created_at', 'jumlah'], 'idx_pendapatan_created_jumlah');
            $table->index(['tindakan_id', 'status'], 'idx_pendapatan_tindakan_status');
        });

        // Indexes for pengeluaran table
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->index('kategori', 'idx_pengeluaran_kategori');
            $table->index('status', 'idx_pengeluaran_status');
            $table->index('created_at', 'idx_pengeluaran_created_at');
            $table->index('input_by', 'idx_pengeluaran_input_by');
            $table->index('validasi_by', 'idx_pengeluaran_validasi_by');
            $table->index('validated_at', 'idx_pengeluaran_validated_at');
            $table->index('deleted_at', 'idx_pengeluaran_deleted_at');
            
            // Composite indexes for financial queries
            $table->index(['status', 'created_at'], 'idx_pengeluaran_status_created');
            $table->index(['kategori', 'status'], 'idx_pengeluaran_kategori_status');
            $table->index(['created_at', 'jumlah'], 'idx_pengeluaran_created_jumlah');
        });

        // Indexes for dokters table
        Schema::table('dokters', function (Blueprint $table) {
            $table->index('user_id', 'idx_dokters_user_id');
            $table->index('spesialisasi', 'idx_dokters_spesialisasi');
            $table->index('aktif', 'idx_dokters_aktif');
            $table->index('status_akun', 'idx_dokters_status_akun');
            $table->index('created_at', 'idx_dokters_created_at');
            $table->index('input_by', 'idx_dokters_input_by');
            $table->index('deleted_at', 'idx_dokters_deleted_at');
            $table->index('nomor_sip', 'idx_dokters_nomor_sip');
            $table->index('nik', 'idx_dokters_nik');
            $table->index('nama_lengkap', 'idx_dokters_nama_lengkap');
            
            // Composite indexes
            $table->index(['aktif', 'spesialisasi'], 'idx_dokters_aktif_spesialisasi');
            $table->index(['user_id', 'aktif'], 'idx_dokters_user_aktif');
        });

        // Indexes for users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'idx_users_role');
            $table->index('role_id', 'idx_users_role_id');
            $table->index('is_active', 'idx_users_is_active');
            $table->index('created_at', 'idx_users_created_at');
            $table->index('last_login_at', 'idx_users_last_login_at');
            $table->index('deleted_at', 'idx_users_deleted_at');
            $table->index('nip', 'idx_users_nip');
            
            // Composite indexes
            $table->index(['role', 'is_active'], 'idx_users_role_active');
            $table->index(['is_active', 'created_at'], 'idx_users_active_created');
            $table->index(['role_id', 'is_active'], 'idx_users_role_id_active');
        });

        // Indexes for jenis_tindakan table
        Schema::table('jenis_tindakan', function (Blueprint $table) {
            $table->index('nama', 'idx_jenis_tindakan_nama');
            $table->index('kategori', 'idx_jenis_tindakan_kategori');
            $table->index('is_active', 'idx_jenis_tindakan_is_active');
            $table->index('created_at', 'idx_jenis_tindakan_created_at');
            $table->index('deleted_at', 'idx_jenis_tindakan_deleted_at');
            
            // Composite indexes
            $table->index(['kategori', 'is_active'], 'idx_jenis_tindakan_kategori_active');
            $table->index(['is_active', 'tarif'], 'idx_jenis_tindakan_active_tarif');
        });

        // Indexes for jaspel table
        Schema::table('jaspel', function (Blueprint $table) {
            $table->index('tindakan_id', 'idx_jaspel_tindakan_id');
            $table->index('user_id', 'idx_jaspel_user_id');
            $table->index('jenis_jaspel', 'idx_jaspel_jenis_jaspel');
            $table->index('periode', 'idx_jaspel_periode');
            $table->index('status', 'idx_jaspel_status');
            $table->index('created_at', 'idx_jaspel_created_at');
            $table->index('deleted_at', 'idx_jaspel_deleted_at');
            
            // Composite indexes
            $table->index(['user_id', 'periode'], 'idx_jaspel_user_periode');
            $table->index(['tindakan_id', 'jenis_jaspel'], 'idx_jaspel_tindakan_jenis');
            $table->index(['status', 'periode'], 'idx_jaspel_status_periode');
            $table->index(['user_id', 'status'], 'idx_jaspel_user_status');
        });

        // Indexes for audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('user_id', 'idx_audit_logs_user_id');
            $table->index('action', 'idx_audit_logs_action');
            $table->index('model_type', 'idx_audit_logs_model_type');
            $table->index('model_id', 'idx_audit_logs_model_id');
            $table->index('created_at', 'idx_audit_logs_created_at');
            $table->index('user_role', 'idx_audit_logs_user_role');
            $table->index('ip_address', 'idx_audit_logs_ip_address');
            
            // Composite indexes
            $table->index(['model_type', 'model_id'], 'idx_audit_logs_model');
            $table->index(['user_id', 'action'], 'idx_audit_logs_user_action');
            $table->index(['action', 'created_at'], 'idx_audit_logs_action_created');
            $table->index(['user_role', 'created_at'], 'idx_audit_logs_role_created');
        });

        // Indexes for error_logs table (commented out - table doesn't exist)
        // Schema::table('error_logs', function (Blueprint $table) {
        //     $table->index('level', 'idx_error_logs_level');
        //     $table->index('created_at', 'idx_error_logs_created_at');
        //     $table->index('user_id', 'idx_error_logs_user_id');
        //     $table->index(['level', 'created_at'], 'idx_error_logs_level_created');
        // });

        // Indexes for security_logs table (commented out - table doesn't exist)
        // Schema::table('security_logs', function (Blueprint $table) {
        //     $table->index('event_type', 'idx_security_logs_event_type');
        //     $table->index('user_id', 'idx_security_logs_user_id');
        //     $table->index('ip_address', 'idx_security_logs_ip_address');
        //     $table->index('created_at', 'idx_security_logs_created_at');
        //     $table->index(['event_type', 'created_at'], 'idx_security_logs_event_created');
        //     $table->index(['user_id', 'event_type'], 'idx_security_logs_user_event');
        // });

        // Indexes for performance_logs table (commented out - table doesn't exist)
        // Schema::table('performance_logs', function (Blueprint $table) {
        //     $table->index('operation', 'idx_performance_logs_operation');
        //     $table->index('level', 'idx_performance_logs_level');
        //     $table->index('created_at', 'idx_performance_logs_created_at');
        //     $table->index('duration', 'idx_performance_logs_duration');
        //     $table->index(['operation', 'created_at'], 'idx_performance_logs_operation_created');
        //     $table->index(['level', 'duration'], 'idx_performance_logs_level_duration');
        // });

        // Full-text indexes for search functionality
        if (config('database.default') === 'mysql') {
            // Full-text search on pasien
            DB::statement('ALTER TABLE pasien ADD FULLTEXT idx_pasien_fulltext (nama, no_rekam_medis, alamat)');
            
            // Full-text search on dokter
            DB::statement('ALTER TABLE dokter ADD FULLTEXT idx_dokter_fulltext (nama, spesialisasi)');
            
            // Full-text search on jenis_tindakan
            DB::statement('ALTER TABLE jenis_tindakan ADD FULLTEXT idx_jenis_tindakan_fulltext (nama, deskripsi)');
            
            // Full-text search on users
            DB::statement('ALTER TABLE users ADD FULLTEXT idx_users_fulltext (name, email)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop full-text indexes first
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE pasien DROP INDEX idx_pasien_fulltext');
            DB::statement('ALTER TABLE dokter DROP INDEX idx_dokter_fulltext');
            DB::statement('ALTER TABLE jenis_tindakan DROP INDEX idx_jenis_tindakan_fulltext');
            DB::statement('ALTER TABLE users DROP INDEX idx_users_fulltext');
        }

        // Drop indexes from performance_logs table
        Schema::table('performance_logs', function (Blueprint $table) {
            $table->dropIndex('idx_performance_logs_operation');
            $table->dropIndex('idx_performance_logs_level');
            $table->dropIndex('idx_performance_logs_created_at');
            $table->dropIndex('idx_performance_logs_duration');
            $table->dropIndex('idx_performance_logs_operation_created');
            $table->dropIndex('idx_performance_logs_level_duration');
        });

        // Drop indexes from security_logs table
        Schema::table('security_logs', function (Blueprint $table) {
            $table->dropIndex('idx_security_logs_event_type');
            $table->dropIndex('idx_security_logs_user_id');
            $table->dropIndex('idx_security_logs_ip_address');
            $table->dropIndex('idx_security_logs_created_at');
            $table->dropIndex('idx_security_logs_event_created');
            $table->dropIndex('idx_security_logs_user_event');
        });

        // Drop indexes from error_logs table
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropIndex('idx_error_logs_level');
            $table->dropIndex('idx_error_logs_created_at');
            $table->dropIndex('idx_error_logs_user_id');
            $table->dropIndex('idx_error_logs_level_created');
        });

        // Drop indexes from audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_user_id');
            $table->dropIndex('idx_audit_logs_action');
            $table->dropIndex('idx_audit_logs_model_type');
            $table->dropIndex('idx_audit_logs_model_id');
            $table->dropIndex('idx_audit_logs_created_at');
            $table->dropIndex('idx_audit_logs_user_role');
            $table->dropIndex('idx_audit_logs_ip_address');
            $table->dropIndex('idx_audit_logs_model');
            $table->dropIndex('idx_audit_logs_user_action');
            $table->dropIndex('idx_audit_logs_action_created');
            $table->dropIndex('idx_audit_logs_role_created');
        });

        // Drop indexes from jaspel table
        Schema::table('jaspel', function (Blueprint $table) {
            $table->dropIndex('idx_jaspel_tindakan_id');
            $table->dropIndex('idx_jaspel_user_id');
            $table->dropIndex('idx_jaspel_jenis_jaspel');
            $table->dropIndex('idx_jaspel_periode');
            $table->dropIndex('idx_jaspel_status');
            $table->dropIndex('idx_jaspel_created_at');
            $table->dropIndex('idx_jaspel_deleted_at');
            $table->dropIndex('idx_jaspel_user_periode');
            $table->dropIndex('idx_jaspel_tindakan_jenis');
            $table->dropIndex('idx_jaspel_status_periode');
            $table->dropIndex('idx_jaspel_user_status');
        });

        // Drop indexes from jenis_tindakan table
        Schema::table('jenis_tindakan', function (Blueprint $table) {
            $table->dropIndex('idx_jenis_tindakan_nama');
            $table->dropIndex('idx_jenis_tindakan_kategori');
            $table->dropIndex('idx_jenis_tindakan_is_active');
            $table->dropIndex('idx_jenis_tindakan_created_at');
            $table->dropIndex('idx_jenis_tindakan_deleted_at');
            $table->dropIndex('idx_jenis_tindakan_kategori_active');
            $table->dropIndex('idx_jenis_tindakan_active_tarif');
        });

        // Drop indexes from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_role_id');
            $table->dropIndex('idx_users_is_active');
            $table->dropIndex('idx_users_created_at');
            $table->dropIndex('idx_users_last_login_at');
            $table->dropIndex('idx_users_deleted_at');
            $table->dropIndex('idx_users_nip');
            $table->dropIndex('idx_users_role_active');
            $table->dropIndex('idx_users_active_created');
            $table->dropIndex('idx_users_role_id_active');
        });

        // Drop indexes from dokter table
        Schema::table('dokter', function (Blueprint $table) {
            $table->dropIndex('idx_dokter_user_id');
            $table->dropIndex('idx_dokter_spesialisasi');
            $table->dropIndex('idx_dokter_status');
            $table->dropIndex('idx_dokter_created_at');
            $table->dropIndex('idx_dokter_input_by');
            $table->dropIndex('idx_dokter_deleted_at');
            $table->dropIndex('idx_dokter_no_izin_praktek');
            $table->dropIndex('idx_dokter_status_spesialisasi');
            $table->dropIndex('idx_dokter_user_status');
        });

        // Drop indexes from pengeluaran table
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropIndex('idx_pengeluaran_kategori');
            $table->dropIndex('idx_pengeluaran_status');
            $table->dropIndex('idx_pengeluaran_created_at');
            $table->dropIndex('idx_pengeluaran_input_by');
            $table->dropIndex('idx_pengeluaran_validasi_by');
            $table->dropIndex('idx_pengeluaran_validated_at');
            $table->dropIndex('idx_pengeluaran_deleted_at');
            $table->dropIndex('idx_pengeluaran_status_created');
            $table->dropIndex('idx_pengeluaran_kategori_status');
            $table->dropIndex('idx_pengeluaran_created_jumlah');
        });

        // Drop indexes from pendapatan table
        Schema::table('pendapatan', function (Blueprint $table) {
            $table->dropIndex('idx_pendapatan_tindakan_id');
            $table->dropIndex('idx_pendapatan_kategori');
            $table->dropIndex('idx_pendapatan_status');
            $table->dropIndex('idx_pendapatan_created_at');
            $table->dropIndex('idx_pendapatan_input_by');
            $table->dropIndex('idx_pendapatan_validasi_by');
            $table->dropIndex('idx_pendapatan_validated_at');
            $table->dropIndex('idx_pendapatan_deleted_at');
            $table->dropIndex('idx_pendapatan_status_created');
            $table->dropIndex('idx_pendapatan_kategori_status');
            $table->dropIndex('idx_pendapatan_created_jumlah');
            $table->dropIndex('idx_pendapatan_tindakan_status');
        });

        // Drop indexes from tindakan table
        Schema::table('tindakan', function (Blueprint $table) {
            $table->dropIndex('idx_tindakan_pasien_id');
            $table->dropIndex('idx_tindakan_dokter_id');
            $table->dropIndex('idx_tindakan_jenis_tindakan_id');
            $table->dropIndex('idx_tindakan_tanggal');
            $table->dropIndex('idx_tindakan_status');
            $table->dropIndex('idx_tindakan_status_validasi');
            $table->dropIndex('idx_tindakan_created_at');
            $table->dropIndex('idx_tindakan_validated_at');
            $table->dropIndex('idx_tindakan_input_by');
            $table->dropIndex('idx_tindakan_validated_by');
            $table->dropIndex('idx_tindakan_deleted_at');
            $table->dropIndex('idx_tindakan_pasien_tanggal');
            $table->dropIndex('idx_tindakan_dokter_tanggal');
            $table->dropIndex('idx_tindakan_status_tanggal');
            $table->dropIndex('idx_tindakan_validasi_created');
            $table->dropIndex('idx_tindakan_jenis_status');
        });

        // Drop indexes from pasien table
        Schema::table('pasien', function (Blueprint $table) {
            $table->dropIndex('idx_pasien_created_at');
            $table->dropIndex('idx_pasien_jenis_kelamin');
            $table->dropIndex('idx_pasien_tanggal_lahir');
            $table->dropIndex('idx_pasien_gender_created');
            $table->dropIndex('idx_pasien_no_rekam_medis');
            $table->dropIndex('idx_pasien_nama');
            $table->dropIndex('idx_pasien_deleted_at');
        });
    }
};
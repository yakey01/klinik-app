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
        // For SQLite, we need to recreate the table to remove unique constraint
        // For MySQL/PostgreSQL, we can drop the unique constraint directly
        
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite approach: Create new table without unique constraint, migrate data, drop old table
            
            // 1. Create temporary table with proper constraints for soft deletes
            Schema::create('pegawais_temp', function (Blueprint $table) {
                $table->id();
                $table->string('nik')->unique();
                $table->string('nama_lengkap');
                $table->string('email')->unique()->nullable();
                $table->date('tanggal_lahir')->nullable();
                $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
                $table->string('jabatan');
                $table->enum('jenis_pegawai', ['Paramedis', 'Non-Paramedis'])->default('Non-Paramedis');
                $table->boolean('aktif')->default(true);
                $table->string('foto')->nullable();
                $table->unsignedBigInteger('input_by')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                
                // Username fields - NO UNIQUE constraint for soft deletes
                $table->string('username')->nullable();
                $table->string('password')->nullable();
                $table->enum('status_akun', ['Aktif', 'Suspend'])->default('Aktif');
                $table->timestamp('password_changed_at')->nullable();
                $table->unsignedBigInteger('password_reset_by')->nullable();
                
                $table->timestamps();
                $table->softDeletes(); // This is crucial for soft delete functionality
                
                // Indexes
                $table->index(['nama_lengkap']);
                $table->index(['jabatan']);
                $table->index(['jenis_pegawai', 'aktif']);
                $table->index(['email']);
                $table->index(['user_id']);
                $table->index(['username']); // Index but NOT unique
                $table->index(['status_akun']);
                
                // Foreign keys
                $table->foreign('input_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('password_reset_by')->references('id')->on('users')->onDelete('set null');
            });
            
            // 2. Copy all data from old table to new table
            DB::statement('INSERT INTO pegawais_temp SELECT * FROM pegawais');
            
            // 3. Drop old table
            Schema::drop('pegawais');
            
            // 4. Rename temp table to original name
            Schema::rename('pegawais_temp', 'pegawais');
            
        } else {
            // MySQL/PostgreSQL approach: Drop unique constraint directly
            Schema::table('pegawais', function (Blueprint $table) {
                $table->dropUnique(['username']); // Remove unique constraint
                // Keep the index for performance but not unique
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // WARNING: This rollback will fail if there are duplicate usernames from soft deletes
        // You should clean up duplicate usernames before rolling back
        
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // For SQLite, we need to recreate the table again with unique constraint
            Schema::create('pegawais_temp', function (Blueprint $table) {
                $table->id();
                $table->string('nik')->unique();
                $table->string('nama_lengkap');
                $table->string('email')->unique()->nullable();
                $table->date('tanggal_lahir')->nullable();
                $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
                $table->string('jabatan');
                $table->enum('jenis_pegawai', ['Paramedis', 'Non-Paramedis'])->default('Non-Paramedis');
                $table->boolean('aktif')->default(true);
                $table->string('foto')->nullable();
                $table->unsignedBigInteger('input_by')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                
                // Username fields - WITH UNIQUE constraint (old behavior)
                $table->string('username')->nullable()->unique();
                $table->string('password')->nullable();
                $table->enum('status_akun', ['Aktif', 'Suspend'])->default('Aktif');
                $table->timestamp('password_changed_at')->nullable();
                $table->unsignedBigInteger('password_reset_by')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                // Indexes
                $table->index(['nama_lengkap']);
                $table->index(['jabatan']);
                $table->index(['jenis_pegawai', 'aktif']);
                $table->index(['email']);
                $table->index(['user_id']);
                $table->index(['username']);
                $table->index(['status_akun']);
                
                // Foreign keys
                $table->foreign('input_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('password_reset_by')->references('id')->on('users')->onDelete('set null');
            });
            
            // Copy data (this might fail if there are duplicates)
            DB::statement('INSERT INTO pegawais_temp SELECT * FROM pegawais WHERE deleted_at IS NULL');
            
            Schema::drop('pegawais');
            Schema::rename('pegawais_temp', 'pegawais');
            
        } else {
            // MySQL/PostgreSQL: Add unique constraint back
            Schema::table('pegawais', function (Blueprint $table) {
                $table->unique(['username']);
            });
        }
    }
};
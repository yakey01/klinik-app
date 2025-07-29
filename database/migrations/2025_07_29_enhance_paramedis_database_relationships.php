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
        // Enhanced pegawais table relationships
        if (Schema::hasTable('pegawais') && !Schema::hasColumn('pegawais', 'user_id')) {
            Schema::table('pegawais', function (Blueprint $table) {
                $table->foreignId('user_id')->after('id')->constrained('users')->onDelete('cascade');
                $table->string('unit_kerja')->after('jabatan')->nullable();
                
                // Enhanced indexes for performance
                $table->index(['user_id', 'jenis_pegawai', 'aktif'], 'idx_pegawai_user_type_active');
                $table->index(['unit_kerja', 'aktif'], 'idx_pegawai_unit_active');
            });
        }

        // Enhanced jadwal_jagas table structure
        if (Schema::hasTable('jadwal_jagas')) {
            Schema::table('jadwal_jagas', function (Blueprint $table) {
                // Add unit_kerja if it doesn't exist, rename unit_instalasi if it does
                if (Schema::hasColumn('jadwal_jagas', 'unit_instalasi') && !Schema::hasColumn('jadwal_jagas', 'unit_kerja')) {
                    $table->renameColumn('unit_instalasi', 'unit_kerja');
                } elseif (!Schema::hasColumn('jadwal_jagas', 'unit_kerja')) {
                    $table->string('unit_kerja')->after('pegawai_id')->nullable();
                }

                // Add work location relationship
                if (!Schema::hasColumn('jadwal_jagas', 'work_location_id')) {
                    $table->foreignId('work_location_id')->after('unit_kerja')->nullable()
                          ->constrained('work_locations')->onDelete('set null');
                }

                // Enhanced performance indexes
                if (!$this->indexExists('jadwal_jagas', 'idx_jadwal_date_unit_status')) {
                    $table->index(['tanggal_jaga', 'unit_kerja', 'status_jaga'], 'idx_jadwal_date_unit_status');
                }
                
                if (!$this->indexExists('jadwal_jagas', 'idx_jadwal_pegawai_date_range')) {
                    $table->index(['pegawai_id', 'tanggal_jaga', 'status_jaga'], 'idx_jadwal_pegawai_date_range');
                }
            });
        }

        // Enhanced attendances table
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                // Add work location reference
                if (!Schema::hasColumn('attendances', 'work_location_id')) {
                    $table->foreignId('work_location_id')->after('user_id')->nullable()
                          ->constrained('work_locations')->onDelete('set null');
                }

                // Add enhanced location tracking
                if (!Schema::hasColumn('attendances', 'latitude')) {
                    $table->decimal('latitude', 10, 8)->after('latlon_in')->nullable();
                    $table->decimal('longitude', 11, 8)->after('latitude')->nullable();
                    $table->decimal('checkout_latitude', 10, 8)->after('latlon_out')->nullable();
                    $table->decimal('checkout_longitude', 11, 8)->after('checkout_latitude')->nullable();
                }

                // Add work duration tracking
                if (!Schema::hasColumn('attendances', 'work_duration')) {
                    $table->integer('work_duration')->after('time_out')->nullable()->comment('Work duration in minutes');
                }

                // Enhanced status enum
                $table->enum('status', ['on_time', 'late', 'early_leave', 'absent', 'present', 'incomplete'])->default('present')->change();

                // Performance indexes
                if (!$this->indexExists('attendances', 'idx_attendance_location_date')) {
                    $table->index(['work_location_id', 'date'], 'idx_attendance_location_date');
                }
                
                if (!$this->indexExists('attendances', 'idx_attendance_user_month')) {
                    $table->index(['user_id', 'date', 'status'], 'idx_attendance_user_month');
                }
            });
        }

        // Enhanced jaspel table relationships
        if (Schema::hasTable('jaspel')) {
            Schema::table('jaspel', function (Blueprint $table) {
                // Add shift relationship if missing
                if (!Schema::hasColumn('jaspel', 'jadwal_jaga_id')) {
                    $table->foreignId('jadwal_jaga_id')->after('shift_id')->nullable()
                          ->constrained('jadwal_jagas')->onDelete('set null');
                }

                // Enhanced jenis_jaspel enum
                if (!$this->columnHasValue('jaspel', 'jenis_jaspel', 'shift_allowance')) {
                    DB::statement("ALTER TABLE jaspel MODIFY COLUMN jenis_jaspel ENUM('tindakan', 'shift', 'shift_allowance', 'overtime', 'emergency_call', 'consultation') DEFAULT 'tindakan'");
                }

                // Performance indexes for dashboard queries
                if (!$this->indexExists('jaspel', 'idx_jaspel_user_month_status')) {
                    $table->index(['user_id', 'tanggal', 'status_validasi'], 'idx_jaspel_user_month_status');
                }
                
                if (!$this->indexExists('jaspel', 'idx_jaspel_validasi_date')) {
                    $table->index(['status_validasi', 'tanggal', 'nominal'], 'idx_jaspel_validasi_date');
                }
            });
        }

        // Create attendance_recap table for performance metrics
        if (!Schema::hasTable('attendance_recaps')) {
            Schema::create('attendance_recaps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->year('year');
                $table->tinyInteger('month');
                $table->integer('total_working_days');
                $table->integer('present_days');
                $table->integer('late_days')->default(0);
                $table->integer('absent_days')->default(0);
                $table->decimal('attendance_percentage', 5, 2)->default(0);
                $table->integer('total_work_minutes')->default(0);
                $table->integer('ranking')->nullable();
                $table->timestamps();

                // Unique constraint and indexes
                $table->unique(['user_id', 'year', 'month'], 'unique_user_period');
                $table->index(['year', 'month', 'attendance_percentage'], 'idx_period_ranking');
                $table->index(['user_id', 'year'], 'idx_user_yearly_recap');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_recaps');

        // Remove added columns and indexes (in reverse order)
        if (Schema::hasTable('jaspel')) {
            Schema::table('jaspel', function (Blueprint $table) {
                $table->dropIndex('idx_jaspel_user_month_status');
                $table->dropIndex('idx_jaspel_validasi_date');
                $table->dropForeign(['jadwal_jaga_id']);
                $table->dropColumn('jadwal_jaga_id');
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_location_date');
                $table->dropIndex('idx_attendance_user_month');
                $table->dropForeign(['work_location_id']);
                $table->dropColumn(['work_location_id', 'latitude', 'longitude', 'checkout_latitude', 'checkout_longitude', 'work_duration']);
            });
        }

        if (Schema::hasTable('jadwal_jagas')) {
            Schema::table('jadwal_jagas', function (Blueprint $table) {
                $table->dropIndex('idx_jadwal_date_unit_status');
                $table->dropIndex('idx_jadwal_pegawai_date_range');
                $table->dropForeign(['work_location_id']);
                $table->dropColumn('work_location_id');
            });
        }

        if (Schema::hasTable('pegawais')) {
            Schema::table('pegawais', function (Blueprint $table) {
                $table->dropIndex('idx_pegawai_user_type_active');
                $table->dropIndex('idx_pegawai_unit_active');
                $table->dropForeign(['user_id']);
                $table->dropColumn(['user_id', 'unit_kerja']);
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);
        return array_key_exists($index, $indexes);
    }

    /**
     * Check if a column has a specific value in its enum
     */
    private function columnHasValue(string $table, string $column, string $value): bool
    {
        $result = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = '{$column}'");
        if (empty($result)) {
            return false;
        }
        $type = $result[0]->Type;
        return strpos($type, $value) !== false;
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create comprehensive validation system for tindakan-bendahara-admin relationship
     */
    public function up(): void
    {
        // 1. Add validation fields to tindakan table
        Schema::table('tindakan', function (Blueprint $table) {
            // Validation workflow fields (if they don't exist)
            if (!Schema::hasColumn('tindakan', 'status_validasi')) {
                $table->enum('status_validasi', ['pending', 'disetujui', 'ditolak', 'revisi'])
                      ->default('pending')
                      ->after('status');
            }
            
            if (!Schema::hasColumn('tindakan', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()
                      ->constrained('users')
                      ->onDelete('set null')
                      ->after('status_validasi');
            }
            
            if (!Schema::hasColumn('tindakan', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('validated_by');
            }
            
            if (!Schema::hasColumn('tindakan', 'komentar_validasi')) {
                $table->text('komentar_validasi')->nullable()->after('validated_at');
            }
            
            // Submission workflow
            $table->timestamp('submitted_at')->nullable()->after('komentar_validasi');
            $table->foreignId('submitted_by')->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->after('submitted_at');
            
            // Master data relationship tracking
            $table->decimal('tarif_master', 15, 2)->nullable()->after('tarif')
                  ->comment('Tarif from master at time of creation');
            $table->decimal('jaspel_percentage_used', 5, 2)->nullable()->after('tarif_master')
                  ->comment('Jaspel percentage from master at time of creation');
            
            // Indexes for performance
            $table->index(['status_validasi', 'submitted_at']);
            $table->index(['validated_by', 'validated_at']);
        });

        // 2. Create tindakan validation history table
        Schema::create('tindakan_validation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tindakan_id')->constrained('tindakan')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('action', ['submitted', 'approved', 'rejected', 'revised', 'commented']);
            $table->enum('old_status', ['pending', 'disetujui', 'ditolak', 'revisi'])->nullable();
            $table->enum('new_status', ['pending', 'disetujui', 'ditolak', 'revisi']);
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable()->comment('Additional validation data');
            $table->timestamps();
            
            $table->index(['tindakan_id', 'created_at']);
            $table->index(['user_id', 'action']);
        });

        // 3. Create master data usage tracking
        Schema::create('jenis_tindakan_usage_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_tindakan_id')->constrained('jenis_tindakan')->onDelete('cascade');
            $table->foreignId('tindakan_id')->constrained('tindakan')->onDelete('cascade');
            $table->foreignId('used_by')->constrained('users')->onDelete('cascade');
            $table->decimal('tarif_at_usage', 15, 2);
            $table->decimal('jaspel_percentage_at_usage', 5, 2);
            $table->json('master_data_snapshot')->comment('Full master data at time of usage');
            $table->timestamps();
            
            $table->index(['jenis_tindakan_id', 'created_at']);
            $table->index(['used_by', 'created_at']);
        });

        // 4. Create bendahara validation queue
        Schema::create('bendahara_validation_queue', function (Blueprint $table) {
            $table->id();
            $table->enum('validation_type', ['tindakan', 'pendapatan_harian']);
            $table->morphs('validatable'); // polymorphic relation to tindakan or pendapatan_harians
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'in_review', 'completed', 'rejected'])->default('pending');
            $table->timestamp('submitted_at');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->json('validation_rules')->nullable()->comment('Auto-validation rules applied');
            $table->timestamps();
            
            $table->index(['status', 'priority', 'submitted_at']);
            $table->index(['assigned_to', 'status']);
            $table->index(['validation_type', 'status']);
        });

        // 5. Create admin-bendahara-petugas notification system
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'master_data_updated', 
                'validation_required', 
                'validation_approved', 
                'validation_rejected',
                'system_alert'
            ]);
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable();
            
            // Sender
            $table->foreignId('from_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('from_role', ['admin', 'bendahara', 'petugas', 'system']);
            
            // Recipient targeting
            $table->foreignId('to_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->enum('to_role', ['admin', 'bendahara', 'petugas', 'all'])->nullable();
            
            // Status tracking
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            
            // Related objects
            $table->nullableMorphs('related'); // tindakan, pendapatan_harian, etc.
            
            $table->timestamps();
            
            $table->index(['to_user_id', 'is_read', 'created_at']);
            $table->index(['to_role', 'is_read', 'priority']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
        Schema::dropIfExists('bendahara_validation_queue');
        Schema::dropIfExists('jenis_tindakan_usage_log');
        Schema::dropIfExists('tindakan_validation_history');
        
        Schema::table('tindakan', function (Blueprint $table) {
            $table->dropColumn([
                'submitted_at', 'submitted_by', 
                'tarif_master', 'jaspel_percentage_used'
            ]);
        });
    }
};
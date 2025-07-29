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
        Schema::table('pendapatan_harians', function (Blueprint $table) {
            // Add relationship columns
            $table->unsignedBigInteger('tindakan_id')->nullable()->after('pendapatan_id');
            $table->string('source_type')->default('manual')->after('tindakan_id'); // 'manual', 'tindakan', 'mixed'
            
            // Add tindakan-related fields for when income is generated from tindakan
            $table->string('tindakan_code')->nullable()->after('source_type');
            $table->decimal('tindakan_tarif', 15, 2)->nullable()->after('tindakan_code');
            $table->decimal('jaspel_amount', 15, 2)->nullable()->after('tindakan_tarif');
            $table->string('performer_type')->nullable()->after('jaspel_amount'); // 'dokter', 'paramedis', 'non_paramedis'
            $table->unsignedBigInteger('performer_id')->nullable()->after('performer_type');
            
            // Reference to which tindakan this income is derived from
            $table->foreign('tindakan_id')
                  ->references('id')
                  ->on('tindakan')
                  ->onDelete('set null');
                  
            // Add indexes for performance
            $table->index(['source_type', 'tanggal_input']);
            $table->index(['tindakan_id', 'status_validasi']);
            $table->index(['performer_type', 'performer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendapatan_harians', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['tindakan_id']);
            
            // Drop indexes
            $table->dropIndex(['source_type', 'tanggal_input']);
            $table->dropIndex(['tindakan_id', 'status_validasi']);
            $table->dropIndex(['performer_type', 'performer_id']);
            
            // Drop columns
            $table->dropColumn([
                'tindakan_id',
                'source_type',
                'tindakan_code',
                'tindakan_tarif',
                'jaspel_amount',
                'performer_type',
                'performer_id'
            ]);
        });
    }
};
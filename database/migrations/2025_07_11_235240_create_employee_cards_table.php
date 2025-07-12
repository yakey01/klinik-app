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
        Schema::create('employee_cards', function (Blueprint $table) {
            $table->id();
            
            // Employee reference
            $table->foreignId('pegawai_id')->constrained('pegawais')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Card information
            $table->string('card_number')->unique(); // e.g., CARD-2025-001
            $table->string('card_type')->default('standard'); // standard, visitor, temporary
            $table->string('design_template')->default('default'); // Card design template
            
            // Employee details snapshot (for card generation)
            $table->string('employee_name');
            $table->string('employee_id'); // NIK or NIP
            $table->string('position'); // Jabatan
            $table->string('department'); // Jenis Pegawai
            $table->string('role_name')->nullable(); // Role display name
            $table->date('join_date')->nullable();
            $table->string('photo_path')->nullable(); // Path to employee photo
            
            // Card validity
            $table->date('issued_date');
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Card files
            $table->string('pdf_path')->nullable(); // Generated PDF path
            $table->string('image_path')->nullable(); // Generated image path
            $table->json('card_data')->nullable(); // Additional card data
            
            // Metadata
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->integer('print_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['pegawai_id', 'is_active']);
            $table->index(['card_type', 'is_active']);
            $table->index('issued_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_cards');
    }
};

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
        Schema::create('absence_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('absence_date');
            $table->enum('absence_type', ['sick', 'personal', 'vacation', 'emergency', 'medical', 'family', 'other']);
            $table->text('reason');
            $table->string('evidence_file')->nullable(); // Medical certificate, documents
            $table->json('evidence_metadata')->nullable(); // File info, upload details
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->boolean('requires_medical_cert')->default(false);
            $table->boolean('is_half_day')->default(false);
            $table->time('half_day_start')->nullable();
            $table->time('half_day_end')->nullable();
            $table->decimal('deduction_amount', 15, 2)->default(0.00); // Salary deduction if any
            $table->json('replacement_staff')->nullable(); // Staff covering duties
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('absence_date');
            $table->index('status');
            $table->index(['user_id', 'absence_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_requests');
    }
};

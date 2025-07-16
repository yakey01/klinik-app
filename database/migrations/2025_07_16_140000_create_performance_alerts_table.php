<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('pegawais')->onDelete('cascade');
            $table->string('alert_type')->default('low_performance');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->json('performance_data');
            $table->text('custom_message')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('sent_at')->default(now());
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('response_message')->nullable();
            $table->enum('status', ['sent', 'acknowledged', 'resolved'])->default('sent');
            $table->timestamps();
            
            $table->index(['staff_id', 'severity']);
            $table->index(['sent_at']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_alerts');
    }
};
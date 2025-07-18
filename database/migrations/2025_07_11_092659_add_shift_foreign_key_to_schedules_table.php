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
        Schema::table('schedules', function (Blueprint $table) {
            // Add foreign key constraint for shift_id if it doesn't exist
            if (!Schema::hasColumn('schedules', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('user_id')->constrained()->onDelete('set null');
            } else {
                // If column exists, just add the foreign key constraint
                $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            if (Schema::hasColumn('schedules', 'shift_id')) {
                $table->dropColumn('shift_id');
            }
        });
    }
}; 
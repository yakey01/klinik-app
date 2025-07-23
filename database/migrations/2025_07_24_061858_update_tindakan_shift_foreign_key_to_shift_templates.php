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
        Schema::table('tindakan', function (Blueprint $table) {
            // Drop the old foreign key constraint
            $table->dropForeign(['shift_id']);
            
            // Add new foreign key constraint pointing to shift_templates
            $table->foreign('shift_id')->references('id')->on('shift_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tindakan', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['shift_id']);
            
            // Restore the old foreign key constraint pointing to shifts
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
        });
    }
};

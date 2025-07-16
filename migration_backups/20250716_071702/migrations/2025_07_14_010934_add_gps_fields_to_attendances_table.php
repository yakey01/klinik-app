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
        Schema::table('attendances', function (Blueprint $table) {
            // Enhanced GPS fields for modern attendance tracking
            $table->decimal('latitude', 10, 8)->nullable()->after('status');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->float('accuracy')->nullable()->after('longitude');
            $table->decimal('checkout_latitude', 10, 8)->nullable()->after('accuracy');
            $table->decimal('checkout_longitude', 11, 8)->nullable()->after('checkout_latitude');
            $table->float('checkout_accuracy')->nullable()->after('checkout_longitude');
            $table->boolean('location_validated')->default(false)->after('checkout_accuracy');
            
            // Index for location-based queries
            $table->index(['latitude', 'longitude'], 'idx_checkin_location');
            $table->index(['checkout_latitude', 'checkout_longitude'], 'idx_checkout_location');
            $table->index('location_validated', 'idx_location_validated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_checkin_location');
            $table->dropIndex('idx_checkout_location');
            $table->dropIndex('idx_location_validated');
            
            $table->dropColumn([
                'latitude',
                'longitude', 
                'accuracy',
                'checkout_latitude',
                'checkout_longitude',
                'checkout_accuracy',
                'location_validated'
            ]);
        });
    }
};

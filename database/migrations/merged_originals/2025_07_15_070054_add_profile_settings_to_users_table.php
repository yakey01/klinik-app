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
        Schema::table('users', function (Blueprint $table) {
            // Profile fields
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->text('bio')->nullable()->after('address');
            $table->date('date_of_birth')->nullable()->after('bio');
            $table->enum('gender', ['male', 'female'])->nullable()->after('date_of_birth');
            $table->string('emergency_contact_name')->nullable()->after('gender');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('profile_photo_path')->nullable()->after('emergency_contact_phone');
            
            // Work settings
            $table->unsignedBigInteger('default_work_location_id')->nullable()->after('profile_photo_path');
            $table->boolean('auto_check_out')->default(false)->after('default_work_location_id');
            $table->boolean('overtime_alerts')->default(true)->after('auto_check_out');
            
            // Notification settings
            $table->boolean('email_notifications')->default(true)->after('overtime_alerts');
            $table->boolean('push_notifications')->default(true)->after('email_notifications');
            $table->boolean('attendance_reminders')->default(true)->after('push_notifications');
            $table->boolean('schedule_updates')->default(true)->after('attendance_reminders');
            
            // Privacy settings
            $table->enum('profile_visibility', ['public', 'colleagues', 'private'])->default('colleagues')->after('schedule_updates');
            $table->boolean('location_sharing')->default(true)->after('profile_visibility');
            $table->boolean('activity_status')->default(true)->after('location_sharing');
            
            // App settings
            $table->string('language', 5)->default('id')->after('activity_status');
            $table->string('timezone')->default('Asia/Jakarta')->after('language');
            $table->enum('theme', ['light', 'dark', 'auto'])->default('light')->after('timezone');
            
            // Add foreign key for work location
            $table->foreign('default_work_location_id')->references('id')->on('work_locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_work_location_id']);
            $table->dropColumn([
                'phone', 'address', 'bio', 'date_of_birth', 'gender',
                'emergency_contact_name', 'emergency_contact_phone', 'profile_photo_path',
                'default_work_location_id', 'auto_check_out', 'overtime_alerts',
                'email_notifications', 'push_notifications', 'attendance_reminders', 'schedule_updates',
                'profile_visibility', 'location_sharing', 'activity_status',
                'language', 'timezone', 'theme'
            ]);
        });
    }
};
<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Attendance;

echo "=== CHECKING ATTENDANCE STATUS FOR BITA ===\n";

$user = User::find(20);
echo "User: " . $user->name . "\n";
echo "Date: " . today()->format('Y-m-d') . "\n";

// Check if user already has attendance today
$attendance = Attendance::where('user_id', $user->id)
    ->whereDate('date', today())
    ->first();

if ($attendance) {
    echo "\n❗ PROBLEM FOUND: User already has attendance record for today!\n";
    echo "Attendance ID: " . $attendance->id . "\n";
    echo "Check-in time: " . ($attendance->time_in ? $attendance->time_in->format('H:i:s') : 'NULL') . "\n";
    echo "Check-out time: " . ($attendance->time_out ? $attendance->time_out->format('H:i:s') : 'NULL') . "\n";
    echo "Status: " . $attendance->status . "\n";
    echo "Date: " . $attendance->date . "\n";
    
    if ($attendance->time_in && !$attendance->time_out) {
        echo "\n🔍 User is currently checked in and needs to check-out first!\n";
    } elseif ($attendance->time_in && $attendance->time_out) {
        echo "\n🔍 User has completed attendance for today (already checked in and out)!\n";
    }
} else {
    echo "\n✅ No attendance record found for today - user can check-in\n";
}

// Check attendance validation
$attendanceStatus = Attendance::getTodayStatus($user->id);
echo "\nAttendance Status from Model:\n";
echo "Status: " . $attendanceStatus['status'] . "\n";
echo "Message: " . $attendanceStatus['message'] . "\n";
echo "Can check-in: " . ($attendanceStatus['can_check_in'] ? 'YES' : 'NO') . "\n";
echo "Can check-out: " . ($attendanceStatus['can_check_out'] ? 'YES' : 'NO') . "\n";

// If there's an attendance record, let's see what happens if we delete it for testing
if ($attendance) {
    echo "\nDo you want to delete this attendance record to allow fresh check-in? (This is for testing)\n";
    echo "The record will be deleted in 3 seconds... Press Ctrl+C to cancel\n";
    sleep(3);
    
    $attendance->delete();
    echo "✅ Attendance record deleted! User can now check-in fresh.\n";
}
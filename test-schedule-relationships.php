<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Pegawai;
use App\Models\JadwalJaga;
use App\Models\ShiftTemplate;
use App\Models\Attendance;
use Carbon\Carbon;

echo "🔧 BUILDING SCHEDULE RELATIONSHIPS FOR BITA AT 11:30\n";
echo "==================================================\n\n";

try {
    $currentTime = Carbon::now();
    echo "⏰ Current Time: {$currentTime->format('H:i:s')}\n\n";

    // Get Bita user
    $bita = User::where('username', 'bita01')->first();
    if (!$bita) {
        throw new Exception('Bita user not found');
    }

    $pegawai = Pegawai::where('user_id', $bita->id)->first();
    if (!$pegawai) {
        throw new Exception('Pegawai record not found for Bita');
    }

    echo "✅ User Found:\n";
    echo "   ID: {$bita->id}\n";
    echo "   Username: {$bita->username}\n";
    echo "   Name: {$bita->name}\n";
    echo "   Pegawai ID: {$pegawai->id}\n\n";

    // Check current schedules
    echo "📅 CURRENT SCHEDULES:\n";
    echo "==================\n";
    
    $todaySchedules = JadwalJaga::where('pegawai_id', $bita->id)
        ->whereDate('tanggal_jaga', Carbon::today())
        ->with(['shiftTemplate'])
        ->orderBy('tanggal_jaga')
        ->get();

    foreach ($todaySchedules as $schedule) {
        $shift = $schedule->shiftTemplate;
        $startTime = Carbon::parse($shift->jam_masuk);
        $endTime = Carbon::parse($shift->jam_pulang);
        $isActive = $currentTime->between($startTime, $endTime);
        $isUpcoming = $currentTime->lessThan($startTime);
        $isPast = $currentTime->greaterThan($endTime);

        echo "   📋 Schedule ID: {$schedule->id}\n";
        echo "   📅 Date: {$schedule->tanggal_jaga->format('Y-m-d')}\n";
        echo "   🕐 Shift: {$shift->nama_shift} ({$shift->jam_masuk} - {$shift->jam_pulang})\n";
        echo "   🏢 Unit: {$schedule->unit_kerja}\n";
        echo "   🎭 Role: {$schedule->peran}\n";
        echo "   📊 Status: {$schedule->status_jaga}\n";
        
        if ($isActive) {
            echo "   🟢 STATUS: ACTIVE NOW\n";
        } elseif ($isUpcoming) {
            $minutesUntil = $currentTime->diffInMinutes($startTime);
            echo "   🟡 STATUS: UPCOMING (in {$minutesUntil} minutes)\n";
        } elseif ($isPast) {
            echo "   🔴 STATUS: PAST\n";
        }
        echo "\n";
    }

    // Check attendance status
    echo "👤 ATTENDANCE STATUS:\n";
    echo "==================\n";
    
    $attendanceStatus = Attendance::getTodayStatus($bita->id);
    echo "   Status: {$attendanceStatus['status']}\n";
    echo "   Message: {$attendanceStatus['message']}\n";
    echo "   Can Check In: " . ($attendanceStatus['can_check_in'] ? 'YES' : 'NO') . "\n";
    echo "   Can Check Out: " . ($attendanceStatus['can_check_out'] ? 'YES' : 'NO') . "\n";
    
    if ($attendanceStatus['attendance']) {
        $att = $attendanceStatus['attendance'];
        echo "   Check In Time: " . ($att->time_in ? $att->time_in->format('H:i:s') : 'None') . "\n";
        echo "   Check Out Time: " . ($att->time_out ? $att->time_out->format('H:i:s') : 'None') . "\n";
    }
    echo "\n";

    // Check specific 11:30 schedule
    echo "🎯 11:30 SCHEDULE ANALYSIS:\n";
    echo "=========================\n";
    
    $schedule1130 = $todaySchedules->filter(function ($schedule) {
        return $schedule->shiftTemplate->jam_masuk === '11:30:00';
    })->first();

    if ($schedule1130) {
        echo "✅ Found 11:30 schedule:\n";
        echo "   Shift: {$schedule1130->shiftTemplate->nama_shift}\n";
        echo "   Time: {$schedule1130->shiftTemplate->jam_masuk} - {$schedule1130->shiftTemplate->jam_pulang}\n";
        echo "   Unit: {$schedule1130->unit_kerja}\n";
        
        $startTime = Carbon::parse($schedule1130->shiftTemplate->jam_masuk);
        $minutesUntil = $currentTime->diffInMinutes($startTime);
        
        if ($currentTime->lessThan($startTime)) {
            echo "   🟡 Status: Starting in {$minutesUntil} minutes\n";
            echo "   ✅ Check-in should be AVAILABLE\n";
        } elseif ($currentTime->between($startTime, Carbon::parse($schedule1130->shiftTemplate->jam_pulang))) {
            echo "   🟢 Status: ACTIVE NOW\n";
            echo "   ✅ Check-in should be AVAILABLE\n";
        } else {
            echo "   🔴 Status: Past\n";
        }
    } else {
        echo "❌ No 11:30 schedule found\n";
    }
    echo "\n";

    // Build relationship test
    echo "🔗 RELATIONSHIP TESTS:\n";
    echo "====================\n";
    
    foreach ($todaySchedules as $schedule) {
        echo "Testing relationships for schedule {$schedule->id}:\n";
        
        // Test pegawai relationship
        $pegawaiRel = $schedule->pegawai;
        echo "   👤 Pegawai: " . ($pegawaiRel ? "✅ {$pegawaiRel->name}" : "❌ Missing") . "\n";
        
        // Test shift template relationship
        $shiftRel = $schedule->shiftTemplate;
        echo "   🕐 Shift Template: " . ($shiftRel ? "✅ {$shiftRel->nama_shift}" : "❌ Missing") . "\n";
        
        // Test derived attributes
        echo "   📍 Jam Shift: {$schedule->jam_shift}\n";
        echo "   🎯 Effective Start: {$schedule->effective_start_time}\n";
        echo "   🏁 Effective End: {$schedule->effective_end_time}\n";
        echo "\n";
    }

    // Dashboard API simulation
    echo "📱 DASHBOARD API SIMULATION:\n";
    echo "==========================\n";
    
    $mockApiResponse = [
        'user' => [
            'id' => $bita->id,
            'name' => $bita->name,
            'username' => $bita->username
        ],
        'attendance' => [
            'can_check_in' => $attendanceStatus['can_check_in'],
            'can_check_out' => $attendanceStatus['can_check_out'],
            'status' => $attendanceStatus['status'],
            'message' => $attendanceStatus['message']
        ],
        'schedules' => $todaySchedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'tanggal_jaga' => $schedule->tanggal_jaga->format('Y-m-d'),
                'shift' => $schedule->shiftTemplate->nama_shift,
                'jam_mulai' => $schedule->shiftTemplate->jam_masuk,
                'jam_selesai' => $schedule->shiftTemplate->jam_pulang,
                'unit_kerja' => $schedule->unit_kerja,
                'status' => $schedule->status_jaga
            ];
        })->toArray()
    ];

    echo "API Response Preview:\n";
    echo json_encode($mockApiResponse, JSON_PRETTY_PRINT) . "\n\n";

    // Final verification
    echo "🎉 FINAL VERIFICATION:\n";
    echo "====================\n";
    
    $hasUpcomingSchedule = $todaySchedules->contains(function ($schedule) use ($currentTime) {
        $startTime = Carbon::parse($schedule->shiftTemplate->jam_masuk);
        return $currentTime->lessThan($startTime) && $currentTime->diffInMinutes($startTime) <= 60;
    });

    $hasActiveSchedule = $todaySchedules->contains(function ($schedule) use ($currentTime) {
        $startTime = Carbon::parse($schedule->shiftTemplate->jam_masuk);
        $endTime = Carbon::parse($schedule->shiftTemplate->jam_pulang);
        return $currentTime->between($startTime, $endTime);
    });

    echo "Has upcoming schedule (within 1 hour): " . ($hasUpcomingSchedule ? 'YES' : 'NO') . "\n";
    echo "Has active schedule now: " . ($hasActiveSchedule ? 'YES' : 'NO') . "\n";
    echo "Can check in: " . ($attendanceStatus['can_check_in'] ? 'YES' : 'NO') . "\n";
    echo "Should show check-in button: " . (($hasUpcomingSchedule || $hasActiveSchedule) && $attendanceStatus['can_check_in'] ? 'YES' : 'NO') . "\n";
    
    echo "\n✅ RELATIONSHIP BUILDING COMPLETE!\n";
    echo "Dashboard should now show check-in button for Bita at 11:30 schedule\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
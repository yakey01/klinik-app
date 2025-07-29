<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

// Bootstrap Laravel
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 DEBUGGING ATTENDANCE STATS - Monthly Summary Zero Values\n";
echo "=========================================================\n\n";

// Find test user (bita01)
$user = User::where('username', 'bita01')->first();
if (!$user) {
    echo "❌ User bita01 not found\n";
    exit(1); 
}

echo "👤 User Found: {$user->name} (ID: {$user->id})\n\n";

// Check attendance data for current month
$currentMonth = Carbon::now()->month;
$currentYear = Carbon::now()->year;

echo "📅 Checking attendance for: {$currentYear}-{$currentMonth}\n\n";

$attendances = Attendance::where('user_id', $user->id)
    ->whereMonth('date', $currentMonth)
    ->whereYear('date', $currentYear)
    ->orderBy('date', 'desc')
    ->get();

echo "📊 ATTENDANCE RECORDS FOUND: {$attendances->count()}\n";
echo "==========================================\n";

if ($attendances->count() === 0) {
    echo "⚠️  NO ATTENDANCE RECORDS FOUND FOR CURRENT MONTH!\n";
    echo "This explains why all stats are showing 0.\n\n";
    
    // Check if there are any attendance records at all
    $allAttendances = Attendance::where('user_id', $user->id)->get();
    echo "📈 Total attendance records for user: {$allAttendances->count()}\n";
    
    if ($allAttendances->count() > 0) {
        echo "📅 Date range of existing records:\n";
        $earliest = $allAttendances->min('date');
        $latest = $allAttendances->max('date');
        echo "   From: {$earliest} to {$latest}\n\n";
        
        echo "📊 Status breakdown of all records:\n";
        $statusCounts = $allAttendances->groupBy('status')->map->count();
        foreach ($statusCounts as $status => $count) {
            echo "   {$status}: {$count}\n";
        }
    }
} else {
    echo "📋 Records breakdown:\n";
    foreach ($attendances as $attendance) {
        echo "   📅 {$attendance->date->format('Y-m-d')} | Status: {$attendance->status} | In: {$attendance->time_in} | Out: {$attendance->time_out}\n";
    }
    
    echo "\n📊 STATUS COUNTS:\n";
    $statusCounts = $attendances->groupBy('status')->map->count();
    foreach ($statusCounts as $status => $count) {
        echo "   {$status}: {$count}\n";
    }
    
    echo "\n🧮 CALCULATED STATS (Same as backend logic):\n";
    echo "   total_days: {$attendances->count()}\n";
    echo "   on_time: {$attendances->where('status', 'on_time')->count()}\n";
    echo "   late: {$attendances->where('status', 'late')->count()}\n";
    echo "   early_leave: {$attendances->where('status', 'early_leave')->count()}\n";
    echo "   incomplete: {$attendances->where('status', 'incomplete')->count()}\n";
    echo "   absent: {$attendances->where('status', 'absent')->count()}\n";
    
    echo "\n🎯 FRONTEND MAPPING:\n";
    $onTime = $attendances->where('status', 'on_time')->count();
    $late = $attendances->where('status', 'late')->count();
    $earlyLeave = $attendances->where('status', 'early_leave')->count();
    $incomplete = $attendances->where('status', 'incomplete')->count();
    $absent = $attendances->where('status', 'absent')->count();
    
    echo "   totalHadir: {$onTime}\n";
    echo "   totalTidakTerpenuhi: " . ($late + $earlyLeave + $incomplete) . " (late: {$late} + early_leave: {$earlyLeave} + incomplete: {$incomplete})\n";
    echo "   totalTidakHadir: {$absent}\n";
}

echo "\n✅ Debug complete\n";
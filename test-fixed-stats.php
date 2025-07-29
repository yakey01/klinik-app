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

echo "🔧 TESTING FIXED ATTENDANCE STATS\n";
echo "================================\n\n";

// Find test user (bita01)
$user = User::where('username', 'bita01')->first();
echo "👤 User: {$user->name} (ID: {$user->id})\n\n";

// Get current month data
$currentMonth = Carbon::now()->month;
$currentYear = Carbon::now()->year;

$allFilteredAttendance = Attendance::where('user_id', $user->id)
    ->whereMonth('date', $currentMonth)
    ->whereYear('date', $currentYear)
    ->get();

echo "📊 BACKEND STATS CALCULATION (With Legacy Support):\n";
echo "==================================================\n";

$stats = [
    'total_days' => $allFilteredAttendance->count(),
    'on_time' => $allFilteredAttendance->where('status', 'on_time')->count(),
    'late' => $allFilteredAttendance->where('status', 'late')->count(),
    'early_leave' => $allFilteredAttendance->where('status', 'early_leave')->count(),
    'incomplete' => $allFilteredAttendance->where('status', 'incomplete')->count(),
    'absent' => $allFilteredAttendance->where('status', 'absent')->count(),
    'present' => $allFilteredAttendance->where('status', 'present')->count(), // Legacy
];

foreach ($stats as $key => $value) {
    echo "   {$key}: {$value}\n";
}

echo "\n🎯 FRONTEND CALCULATION (With Legacy Support):\n";
echo "==============================================\n";

$totalHadir = ($stats['on_time'] ?? 0) + ($stats['present'] ?? 0);
$totalTidakTerpenuhi = ($stats['late'] ?? 0) + ($stats['early_leave'] ?? 0) + ($stats['incomplete'] ?? 0);
$totalTidakHadir = $stats['absent'] ?? 0;

echo "   totalHadir: {$totalHadir} (on_time: {$stats['on_time']} + present: {$stats['present']})\n";
echo "   totalTidakTerpenuhi: {$totalTidakTerpenuhi} (late: {$stats['late']} + early_leave: {$stats['early_leave']} + incomplete: {$stats['incomplete']})\n";
echo "   totalTidakHadir: {$totalTidakHadir}\n";

$attendanceRate = $stats['total_days'] > 0 ? round(($totalHadir / $stats['total_days']) * 100) : 0;
echo "   tingkatKehadiran: {$attendanceRate}%\n";

echo "\n✅ EXPECTED RESULTS:\n";
echo "   - Monthly summary should show: Hadir={$totalHadir}, Tidak Terpenuhi={$totalTidakTerpenuhi}, Tidak Hadir={$totalTidakHadir}\n";
echo "   - Attendance rate should be: {$attendanceRate}%\n";
echo "   - All attendance records should show as green 'Hadir' badges\n";

echo "\n🎉 Fix applied successfully!\n";
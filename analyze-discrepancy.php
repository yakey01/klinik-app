<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 ANALYZING ATTENDANCE DATA DISCREPANCY\n";
echo "=======================================\n\n";

// Check Naning's data
$user = \App\Models\User::find(13);
echo "👤 User: {$user->name} (ID: {$user->id})\n";

$pegawai = \App\Models\Pegawai::where('user_id', 13)->whereNull('deleted_at')->first();
echo "👥 Pegawai: " . ($pegawai ? $pegawai->nama . " (ID: {$pegawai->id})" : 'Not found') . "\n\n";

// Check raw attendance records
echo "📊 RAW ATTENDANCE RECORDS:\n";
$attendances = \App\Models\Attendance::where('pegawai_id', $pegawai->id)
    ->whereNull('deleted_at')
    ->orderBy('date', 'desc')
    ->limit(10)
    ->get();

echo "Found {$attendances->count()} attendance records:\n";
foreach ($attendances as $att) {
    echo "- {$att->date}: {$att->time_in} to {$att->time_out} (Status: {$att->status})\n";
}

echo "\n📅 JADWAL JAGA RECORDS:\n";
$jadwalJaga = \App\Models\JadwalJaga::where('pegawai_id', $pegawai->id)
    ->where('tanggal', '>=', '2025-07-01')
    ->where('tanggal', '<=', '2025-07-31')
    ->orderBy('tanggal', 'desc')
    ->limit(10)
    ->get();

echo "Found {$jadwalJaga->count()} schedule records for July 2025:\n";
foreach ($jadwalJaga as $jadwal) {
    echo "- {$jadwal->tanggal}: Shift {$jadwal->shift_name} ({$jadwal->jam_masuk} - {$jadwal->jam_keluar})\n";
}

echo "\n🔍 TESTING API RESPONSE:\n";
// Test API directly
Auth::login($user);
$controller = new \App\Http\Controllers\Api\V2\Dashboards\ParamedisDashboardController();
$request = new \Illuminate\Http\Request(['month' => 7, 'year' => 2025]);
$request->setUserResolver(function () use ($user) { return $user; });

$response = $controller->getAttendanceRecap($request);
$data = json_decode($response->content(), true);

if ($data['success']) {
    echo "API Response: SUCCESS\n";
    echo "Daily breakdown count: " . count($data['data']['daily_breakdown']) . "\n";
    
    // Count days with attendance
    $daysWithAttendance = array_filter($data['data']['daily_breakdown'], function($day) {
        return !is_null($day['attendance_info']);
    });
    echo "Days with attendance: " . count($daysWithAttendance) . "\n";
    
    // Count scheduled days
    $scheduledDays = array_filter($data['data']['daily_breakdown'], function($day) {
        return $day['is_scheduled'];
    });
    echo "Scheduled days: " . count($scheduledDays) . "\n";
    
    // Show first few days with attendance
    $attendedDays = array_slice($daysWithAttendance, 0, 3);
    echo "\nSample attended days:\n";
    foreach ($attendedDays as $day) {
        echo "- {$day['date']}: {$day['attendance_info']['time_in']} to {$day['attendance_info']['time_out']}\n";
    }
    
    echo "\n🎯 CHECKING FILTER LOGIC:\n";
    // Simulate the frontend filtering
    $today = new DateTime();
    $today->setTime(0, 0, 0, 0);
    
    echo "Today's date: " . $today->format('Y-m-d') . "\n";
    echo "Filter period: month (should show all)\n";
    
    // Check if any days would be filtered out
    $filteredData = array_filter($data['data']['daily_breakdown'], function($day) use ($today) {
        $dayDate = new DateTime($day['date']);
        // For 'month' filter, return true (show all)
        return true;
    });
    
    echo "Days after filtering: " . count($filteredData) . "\n";
    
    // Check transformation logic
    $transformedData = [];
    foreach ($filteredData as $index => $day) {
        $record = [
            'id' => $index + 1,
            'tanggal' => $day['date'],
            'hari' => $day['day_name'],
            'masuk' => $day['attendance_info']['time_in'] ?? '-',
            'keluar' => $day['attendance_info']['time_out'] ?? '-',
            'status' => $day['attendance_info'] ? 'present' : ($day['is_scheduled'] ? 'absent' : 'absent'),
            'is_scheduled' => $day['is_scheduled']
        ];
        $transformedData[] = $record;
    }
    
    $visibleRecords = array_filter($transformedData, function($record) {
        // This represents what would actually be shown in the UI
        return $record['masuk'] !== '-' || $record['is_scheduled'];
    });
    
    echo "Records that would be visible in UI: " . count($visibleRecords) . "\n";
    
    echo "\nFirst 3 visible records:\n";
    foreach (array_slice($visibleRecords, 0, 3) as $record) {
        echo "- {$record['tanggal']} ({$record['hari']}): {$record['masuk']} - {$record['keluar']} [{$record['status']}]\n";
    }
    
} else {
    echo "API Response: FAILED\n";
    echo "Error: " . $data['message'] . "\n";
}

echo "\n=======================================\n";
echo "🎯 ANALYSIS COMPLETE\n";
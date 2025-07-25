<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\AttendanceValidationService;

echo "=== TESTING BITA CHECK-IN ISSUE ===\n";

// Get user bita
$user = User::find(20);
echo "User: " . $user->name . " (ID: " . $user->id . ")\n";
echo "Work Location ID: " . ($user->work_location_id ?? 'NULL') . "\n";

// Check jadwal jaga
$jadwal = App\Models\JadwalJaga::where('pegawai_id', $user->id)
    ->whereDate('tanggal_jaga', today())
    ->first();

if ($jadwal) {
    echo "Has schedule: YES\n";
    echo "Status: " . $jadwal->status_jaga . "\n";
    echo "Shift Template ID: " . $jadwal->shift_template_id . "\n";
} else {
    echo "Has schedule: NO - THIS IS THE PROBLEM!\n";
    exit;
}

// Test validation service
$service = new AttendanceValidationService();

echo "\n=== STEP BY STEP VALIDATION ===\n";

// 1. Schedule validation
$scheduleResult = $service->validateSchedule($user);
echo "1. Schedule: " . ($scheduleResult['valid'] ? 'PASS' : 'FAIL - ' . $scheduleResult['message']) . "\n";

if (!$scheduleResult['valid']) {
    exit("Schedule validation failed!\n");
}

// 2. Location validation  
$locationResult = $service->validateWorkLocation($user, -6.2088, 106.8456, 10);
echo "2. Location: " . ($locationResult['valid'] ? 'PASS' : 'FAIL - ' . $locationResult['message']) . "\n";

if (!$locationResult['valid']) {
    exit("Location validation failed!\n");
}

// 3. Time validation
$timeResult = $service->validateShiftTime($scheduleResult['jadwal_jaga']);
echo "3. Time: " . ($timeResult['valid'] ? 'PASS' : 'FAIL - ' . $timeResult['message']) . "\n";

if (!$timeResult['valid']) {
    exit("Time validation failed!\n");
}

// 4. Full validation
$fullResult = $service->validateCheckin($user, -6.2088, 106.8456, 10);
echo "4. Full Check-in: " . ($fullResult['valid'] ? 'PASS' : 'FAIL - ' . $fullResult['message']) . "\n";

if ($fullResult['valid']) {
    echo "\n🎉 SUCCESS! Bita should be able to check-in!\n";
} else {
    echo "\n❌ STILL FAILING. Code: " . $fullResult['code'] . "\n";
    if (isset($fullResult['validations'])) {
        echo "Validation breakdown:\n";
        foreach ($fullResult['validations'] as $step => $validation) {
            echo "  $step: " . ($validation['valid'] ? 'PASS' : 'FAIL - ' . $validation['message']) . "\n";
        }
    }
}
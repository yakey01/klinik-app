<?php
echo "🔍 DEBUGGING HTTP HEADERS\n";
echo "=========================\n\n";

$cookieFile = '/tmp/dokterku_debug.txt';

// Login first
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_FOLLOWLOCATION => false,
]);

$loginForm = curl_exec($ch);
preg_match('/name="_token"\s+value="([^"]+)"/', $loginForm, $matches);
$csrfToken = $matches[1] ?? '';

$postData = http_build_query([
    'email_or_username' => 'naning@dokterku.com',
    'password' => 'password123',
    '_token' => $csrfToken
]);

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HEADER => true,
]);

$loginResponse = curl_exec($ch);
echo "1. ✅ Login completed\n\n";

// Check what cookies we have
echo "2. 🍪 COOKIES:\n";
if (file_exists($cookieFile)) {
    $cookies = file_get_contents($cookieFile);
    echo $cookies . "\n";
} else {
    echo "No cookies file found\n";
}

// Now try API with different header combinations
$testHeaders = [
    'Test 1 - Basic' => [
        'Accept: application/json',
    ],
    'Test 2 - With User-Agent' => [
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    ],
    'Test 3 - With Referer' => [
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        'Referer: http://127.0.0.1:8000/paramedis/mobile-app',
    ],
    'Test 4 - With X-Requested-With' => [
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        'Referer: http://127.0.0.1:8000/paramedis/mobile-app',
        'X-Requested-With: XMLHttpRequest',
    ],
    'Test 5 - With CSRF Token' => [
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        'Referer: http://127.0.0.1:8000/paramedis/mobile-app',
        'X-Requested-With: XMLHttpRequest',
        'X-CSRF-TOKEN: ' . $csrfToken,
    ],
];

foreach ($testHeaders as $testName => $headers) {
    echo "3. 🧪 $testName:\n";
    
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://127.0.0.1:8000/api/v2/dashboards/paramedis/attendance-recap?month=7&year=2025',
        CURLOPT_POST => false,
        CURLOPT_HEADER => false,
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "   📊 HTTP Code: $httpCode\n";
    
    if ($httpCode === 200) {
        echo "   ✅ SUCCESS!\n";
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "   📈 Days Present: " . $data['data']['recap']['days_present'] . "\n";
        }
        break;
    } else {
        echo "   ❌ Failed - Response: " . substr($response, 0, 100) . "...\n";
    }
    echo "\n";
}

curl_close($ch);
echo "=========================\n";
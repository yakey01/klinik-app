<?php

/**
 * HOSTINGER FRONTEND DEBUG TOOL
 * Generate JavaScript debug script for production frontend analysis
 */

require_once __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

// Generate JavaScript debug code for frontend
$debugJs = <<<'JS'
// HOSTINGER FRONTEND DEBUG TOOL
// Paste this into browser console on https://dokterkuklinik.com/dokter/mobile-app

console.log('🔍 HOSTINGER FRONTEND DEBUG - Dr. Yaya Dashboard Analysis');
console.log('=' + '='.repeat(60));

// 1. Check current URL and user authentication
console.log('📍 1. CURRENT CONTEXT');
console.log('URL:', window.location.href);
console.log('User Agent:', navigator.userAgent);
console.log('Timestamp:', new Date().toISOString());

// 2. Check meta tags for user data
console.log('\n👤 2. META TAG ANALYSIS');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const userAuthenticated = document.querySelector('meta[name="user-authenticated"]')?.getAttribute('content');
const userDataMeta = document.querySelector('meta[name="user-data"]')?.getAttribute('content');
const apiToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content');

console.log('CSRF Token:', csrfToken ? 'Present' : 'Missing');
console.log('User Authenticated:', userAuthenticated);
console.log('API Token:', apiToken ? 'Present' : 'Missing');

if (userDataMeta) {
    try {
        const userData = JSON.parse(userDataMeta);
        console.log('✅ User Data from Meta Tag:', userData);
        console.log('   Name:', userData.name);
        console.log('   Email:', userData.email);
        console.log('   Greeting:', userData.greeting);
    } catch (e) {
        console.error('❌ Error parsing user data meta tag:', e);
    }
} else {
    console.log('❌ User data meta tag missing');
}

// 3. Check localStorage and sessionStorage
console.log('\n💾 3. BROWSER STORAGE ANALYSIS');
console.log('LocalStorage keys:', Object.keys(localStorage));
console.log('SessionStorage keys:', Object.keys(sessionStorage));

// Check for theme data
const theme = localStorage.getItem('theme');
console.log('Theme setting:', theme);

// 4. Test Dashboard API directly
console.log('\n🔄 4. DASHBOARD API TEST');

const testDashboardAPI = async () => {
    try {
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };
        
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        if (apiToken) {
            headers['Authorization'] = `Bearer ${apiToken}`;
        }
        
        console.log('Sending request to /api/v2/dashboards/dokter/');
        console.log('Headers:', headers);
        
        const response = await fetch('/api/v2/dashboards/dokter/', {
            method: 'GET',
            credentials: 'include',
            headers
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        
        if (response.ok) {
            const result = await response.json();
            console.log('✅ API Response successful');
            console.log('Full response:', result);
            
            if (result.success && result.data) {
                console.log('\n📊 API DATA ANALYSIS:');
                console.log('User section:', result.data.user);
                console.log('Dokter section:', result.data.dokter);
                console.log('Stats section:', result.data.stats);
                console.log('Performance section:', result.data.performance);
                console.log('Next schedule:', result.data.next_schedule);
                
                // Simulate frontend welcome logic
                const dashboardStats = result.data;
                const welcomeName = dashboardStats?.dokter?.nama_lengkap || 
                                  dashboardStats?.user?.name || 
                                  (userData?.name || 'Dokter');
                
                console.log('🎯 FRONTEND WELCOME LOGIC:');
                console.log('   dashboardStats?.dokter?.nama_lengkap:', dashboardStats?.dokter?.nama_lengkap);
                console.log('   dashboardStats?.user?.name:', dashboardStats?.user?.name);
                console.log('   userData?.name:', userData?.name);
                console.log('   Final welcome name:', welcomeName);
            }
        } else {
            console.error('❌ API Response failed');
            const errorText = await response.text();
            console.error('Error response:', errorText);
            
            if (response.status === 401) {
                console.error('🔐 Authentication issue detected');
            } else if (response.status === 419) {
                console.error('🔒 CSRF token issue detected');
            }
        }
        
    } catch (error) {
        console.error('❌ API Test failed:', error);
    }
};

// 5. Test Schedules API
console.log('\n📅 5. SCHEDULES API TEST');

const testSchedulesAPI = async () => {
    try {
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        };
        
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        const response = await fetch('/dokter/api/schedules', {
            method: 'GET',
            credentials: 'same-origin',
            headers
        });
        
        console.log('Schedules API status:', response.status);
        
        if (response.ok) {
            const schedules = await response.json();
            console.log('✅ Schedules API successful');
            console.log('Schedules data:', schedules);
            console.log('Number of schedules:', Array.isArray(schedules) ? schedules.length : 'Not an array');
        } else {
            console.error('❌ Schedules API failed');
            const errorText = await response.text();
            console.error('Schedules error:', errorText);
        }
        
    } catch (error) {
        console.error('❌ Schedules API test failed:', error);
    }
};

// 6. Check React app state
console.log('\n⚛️ 6. REACT APP ANALYSIS');

const checkReactApp = () => {
    // Check if dokter app container exists
    const appContainer = document.getElementById('dokter-app');
    console.log('Dokter app container:', appContainer ? 'Present' : 'Missing');
    
    if (appContainer) {
        console.log('Container innerHTML length:', appContainer.innerHTML.length);
        console.log('Container has children:', appContainer.children.length > 0);
    }
    
    // Check for React DevTools
    console.log('React DevTools available:', typeof window.__REACT_DEVTOOLS_GLOBAL_HOOK__ !== 'undefined');
    
    // Check for window variables
    console.log('Window.__DOKTERKU_ISOLATED__:', window.__DOKTERKU_ISOLATED__);
    console.log('Window.__DOKTERKU_THEME__:', window.__DOKTERKU_THEME__);
};

checkReactApp();

// 7. Network tab analysis
console.log('\n🌐 7. NETWORK ANALYSIS');
console.log('Please check Network tab for:');
console.log('1. Failed API requests (red entries)');
console.log('2. 401/419 authentication errors');
console.log('3. CORS errors');
console.log('4. Slow loading resources');

// Execute API tests
console.log('\n🚀 EXECUTING API TESTS...');
Promise.all([
    testDashboardAPI(),
    testSchedulesAPI()
]).then(() => {
    console.log('\n✅ ALL TESTS COMPLETE');
    console.log('Check the output above for issues');
}).catch(error => {
    console.error('❌ Test execution failed:', error);
});

// 8. Generate summary
setTimeout(() => {
    console.log('\n📋 DEBUG SUMMARY:');
    console.log('Run this script on production and check:');
    console.log('1. Are API calls returning 200 status?');
    console.log('2. Does API response contain correct dokter.nama_lengkap?');
    console.log('3. Are there any authentication errors?');
    console.log('4. Is the React app loading properly?');
    console.log('5. Check browser Network tab for failed requests');
}, 2000);

JS;

echo "🔍 HOSTINGER FRONTEND DEBUG TOOL" . PHP_EOL;
echo "=" . str_repeat("=", 50) . PHP_EOL;
echo "Generated: " . Carbon::now()->format('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;

echo "📋 INSTRUCTIONS:" . PHP_EOL;
echo "1. Go to https://dokterkuklinik.com/dokter/mobile-app" . PHP_EOL;
echo "2. Login as Dr. Yaya Mulyana" . PHP_EOL;
echo "3. Open browser Developer Tools (F12)" . PHP_EOL;
echo "4. Go to Console tab" . PHP_EOL;
echo "5. Paste the JavaScript code below" . PHP_EOL;
echo "6. Press Enter and analyze the output" . PHP_EOL . PHP_EOL;

echo "📄 JAVASCRIPT DEBUG CODE:" . PHP_EOL;
echo str_repeat("-", 50) . PHP_EOL;
echo $debugJs . PHP_EOL;
echo str_repeat("-", 50) . PHP_EOL . PHP_EOL;

echo "🎯 WHAT TO LOOK FOR:" . PHP_EOL;
echo "- API status codes (should be 200)" . PHP_EOL;
echo "- Authentication errors (401, 419)" . PHP_EOL;
echo "- Correct data in API responses" . PHP_EOL;
echo "- Missing meta tags or tokens" . PHP_EOL;
echo "- React app loading issues" . PHP_EOL;
echo "- Network tab red errors" . PHP_EOL . PHP_EOL;

// Save the JavaScript to a file as well
file_put_contents(__DIR__ . '/public/hostinger-debug.js', $debugJs);
echo "✅ Debug script also saved to: /public/hostinger-debug.js" . PHP_EOL;
echo "   You can access it at: https://dokterkuklinik.com/hostinger-debug.js" . PHP_EOL;
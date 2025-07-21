<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for API v2 including rate limits, CORS, and security
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'authentication' => [
            'requests' => 5,
            'per_minutes' => 15,
        ],
        'general_api' => [
            'requests' => 100,
            'per_minutes' => 1,
        ],
        'attendance' => [
            'requests' => 10,
            'per_minutes' => 1,
        ],
        'face_recognition' => [
            'requests' => 5,
            'per_minutes' => 1,
        ],
        'file_upload' => [
            'requests' => 20,
            'per_minutes' => 5,
        ],
        'dashboard' => [
            'requests' => 30,
            'per_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    */
    'allowed_origins' => [
        '*', // Allow all origins for development - restrict in production
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    */
    'token_types' => [
        'mobile_app' => [
            'expires_in' => 30 * 24 * 60, // 30 days in minutes
            'scopes' => ['mobile:attendance', 'mobile:dashboard', 'mobile:notifications'],
            'can_refresh' => true,
        ],
        'web_app' => [
            'expires_in' => 7 * 24 * 60, // 7 days in minutes (increased from 24 hours)
            'scopes' => ['web:full'],
            'can_refresh' => true,
        ],
        'api_client' => [
            'expires_in' => 365 * 24 * 60, // 1 year in minutes
            'scopes' => ['api:read', 'api:write'],
            'can_refresh' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role-Based Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'attendance' => [
            'view_own' => ['paramedis', 'dokter', 'non_paramedis'],
            'view_all' => ['admin', 'manajer', 'petugas'],
            'manage' => ['admin', 'petugas'],
        ],
        'dashboard' => [
            'view_own' => ['admin', 'manajer', 'bendahara', 'petugas', 'paramedis', 'dokter', 'non_paramedis'],
            'view_analytics' => ['admin', 'manajer', 'bendahara'],
        ],
        'jaspel' => [
            'view_own' => ['paramedis', 'dokter', 'non_paramedis'],
            'view_all' => ['admin', 'manajer', 'bendahara'],
            'approve' => ['manajer', 'bendahara'],
        ],
        'users' => [
            'view_all' => ['admin', 'manajer'],
            'manage' => ['admin'],
        ],
        'schedules' => [
            'view_own' => ['admin', 'manajer', 'bendahara', 'petugas', 'paramedis', 'dokter', 'non_paramedis'],
            'view_all' => ['admin', 'manajer', 'petugas'],
            'manage' => ['admin', 'manajer'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Binding Configuration
    |--------------------------------------------------------------------------
    */
    'device_binding' => [
        'enabled' => env('API_DEVICE_BINDING_ENABLED', true),
        'require_for_roles' => ['paramedis', 'dokter', 'non_paramedis'],
        'max_devices_per_user' => 3,
        'device_registration_expires' => 7 * 24 * 60, // 7 days in minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | GPS Configuration
    |--------------------------------------------------------------------------
    */
    'gps' => [
        'spoofing_detection' => [
            'enabled' => env('API_GPS_SPOOFING_DETECTION', true),
            'accuracy_threshold' => 100, // meters
            'speed_threshold' => 200, // km/h - flag if user moves too fast
            'minimum_confidence' => 0.7,
        ],
        'attendance_validation' => [
            'enabled' => true,
            'required_accuracy' => 50, // meters
            'location_radius_buffer' => 10, // additional meters beyond work location radius
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'dashboard_ttl' => 300, // 5 minutes
        'attendance_today_ttl' => 60, // 1 minute
        'user_profile_ttl' => 3600, // 1 hour
        'work_locations_ttl' => 1800, // 30 minutes
        'jaspel_monthly_ttl' => 900, // 15 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | API Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('API_LOGGING_ENABLED', true),
        'log_requests' => env('API_LOG_REQUESTS', false),
        'log_responses' => env('API_LOG_RESPONSES', false),
        'log_slow_queries' => env('API_LOG_SLOW_QUERIES', true),
        'slow_query_threshold' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile App Configuration
    |--------------------------------------------------------------------------
    */
    'mobile' => [
        'offline_sync' => [
            'enabled' => true,
            'max_offline_records' => 100,
            'sync_interval_minutes' => 15,
        ],
        'push_notifications' => [
            'enabled' => env('FCM_ENABLED', false),
            'fcm_server_key' => env('FCM_SERVER_KEY'),
        ],
    ],

];
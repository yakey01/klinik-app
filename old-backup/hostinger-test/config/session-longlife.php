<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default session "driver" that will be used on
    | requests. Database driver is recommended for production environments
    | as it provides better persistence and sharing across servers.
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime - LONG LIFE CONFIGURATION
    |--------------------------------------------------------------------------
    |
    | Extended session lifetime for admin panels to reduce frequent logouts.
    | Set to 24 hours (1440 minutes) instead of default 2 hours.
    |
    */

    'lifetime' => env('SESSION_LIFETIME', 1440), // 24 hours

    'expire_on_close' => false, // Don't expire on browser close

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    |
    | Disabled for performance in production. Enable if sensitive data
    | is stored in sessions.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Session File Location
    |--------------------------------------------------------------------------
    |
    | When using the native session driver, we need a location where session
    | files may be stored. A default has been set for you but a different
    | location may be specified. This is only needed for file sessions.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify a
    | connection that should be used to manage these sessions.
    |
    */

    'connection' => env('SESSION_CONNECTION', null),

    /*
    |--------------------------------------------------------------------------
    | Session Database Table
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the table we
    | should use to manage the sessions.
    |
    */

    'table' => 'sessions',

    /*
    |--------------------------------------------------------------------------
    | Session Store Database Connection
    |--------------------------------------------------------------------------
    |
    | When using the "database" session driver, you may specify the database
    | connection that should be used to manage your sessions.
    |
    */

    'store' => env('SESSION_STORE', null),

    /*
    |--------------------------------------------------------------------------
    | Session Sweeping Lottery - OPTIMIZED FOR LONG SESSIONS
    |--------------------------------------------------------------------------
    |
    | Reduced cleanup frequency for long-life sessions.
    | Only clean 1 out of 1000 requests to avoid performance impact.
    |
    */

    'lottery' => [1, 1000], // Reduced cleanup frequency

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Name
    |--------------------------------------------------------------------------
    |
    | Here you may change the name of the cookie used to identify a session
    | instance by ID. Using domain-specific naming for better isolation.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        'dokterku_session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Path
    |--------------------------------------------------------------------------
    |
    | The session cookie path determines the path for which the cookie will
    | be regarded as available. Set to root for entire domain access.
    |
    */

    'path' => '/',

    /*
    |--------------------------------------------------------------------------
    | Session Cookie Domain
    |--------------------------------------------------------------------------
    |
    | Set to the main domain to allow subdomain access if needed.
    | Use .dokterkuklinik.com to include subdomains.
    |
    */

    'domain' => env('SESSION_DOMAIN', '.dokterkuklinik.com'),

    /*
    |--------------------------------------------------------------------------
    | HTTPS Only Cookies - PRODUCTION SECURITY
    |--------------------------------------------------------------------------
    |
    | By setting this option to true, session cookies will only be sent back
    | to the server if the browser has a HTTPS connection.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Access Only - SECURITY
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will prevent JavaScript from accessing the
    | value of the cookie and the cookie will only be accessible through
    | the HTTP protocol.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookies - CSRF PROTECTION
    |--------------------------------------------------------------------------
    |
    | This option determines how your cookies behave when cross-site requests
    | take place. "lax" provides good balance between security and usability.
    |
    */

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Partitioned Cookies
    |--------------------------------------------------------------------------
    |
    | Setting this value to true will tie the cookie to the top-level site for
    | a cross-site context. Generally not needed for admin panels.
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];
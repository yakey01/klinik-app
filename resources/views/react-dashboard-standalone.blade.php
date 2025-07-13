<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>React Dashboard - Dokterku</title>
    
    <!-- React Component Styles -->
    <link rel="stylesheet" href="{{ asset('react-build/paramedis-dashboard.css') }}">
    
    <style>
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8fafc;
        }
        
        #paramedis-dashboard-root {
            min-height: 100vh;
        }
        
        /* Hide scrollbars for clean mobile look */
        body::-webkit-scrollbar {
            display: none;
        }
        body {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body>
    <!-- React component will mount here -->
    <div id="paramedis-dashboard-root"></div>
    
    <!-- Pass user data to React -->
    <script>
        // Pass user data to React
        window.laravelData = {
            user: @json(auth()->user()),
            csrfToken: '{{ csrf_token() }}',
            baseUrl: '{{ url("/") }}',
            apiUrl: '{{ url("/api") }}'
        };
        
        // Debug info
        console.log('React Dashboard initialized', {
            user: '{{ auth()->user()->name }}',
            role: '{{ auth()->user()->role->name ?? "unknown" }}',
            hasLaravelData: !!window.laravelData
        });
    </script>
    
    <!-- React bundle -->
    <script src="{{ asset('react-build/paramedis-dashboard.js') }}"></script>
</body>
</html>
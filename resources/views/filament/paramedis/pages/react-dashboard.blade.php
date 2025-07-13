<x-filament-panels::page>
    @push('styles')
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- React Component Styles -->
    <link rel="stylesheet" href="{{ asset('react-build/paramedis-dashboard.css') }}">
    
    <!-- Hide Filament navigation for full mobile experience -->
    <style>
        @media (max-width: 1024px) {
            .fi-sidebar {
                display: none !important;
            }
            .fi-main {
                margin-left: 0 !important;
            }
            .fi-topbar {
                display: none !important;
            }
        }
        
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8fafc;
        }
        
        #paramedis-dashboard-root {
            min-height: 100vh;
        }
    </style>
    @endpush
    
    <!-- React component will mount here -->
    <div id="paramedis-dashboard-root"></div>
    
    @push('scripts')
    <!-- Pass user data to React without token for now -->
    <script>
        // Pass user data to React
        window.laravelData = {
            user: @json(auth()->user()),
            csrfToken: '{{ csrf_token() }}',
            baseUrl: '{{ url("/") }}',
            apiUrl: '{{ url("/api") }}'
        };
        
        // Set temporary auth for demo (we'll use session-based auth)
        localStorage.setItem('auth_token', 'session_based_auth');
        
        // Debug info
        console.log('React Dashboard initialized', {
            user: '{{ auth()->user()->name }}',
            role: '{{ auth()->user()->role->name ?? "unknown" }}',
            hasSessionAuth: true
        });
    </script>
    
    <!-- React bundle -->
    <script src="{{ asset('react-build/paramedis-dashboard.js') }}"></script>
    @endpush
</x-filament-panels::page>
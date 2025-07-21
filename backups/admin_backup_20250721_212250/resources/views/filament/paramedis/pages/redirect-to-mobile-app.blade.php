<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Mobile App...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f8fafc;
            color: #1f2937;
        }
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #e5e7eb;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px 0;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .icon {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            color: #3b82f6;
            animation: pulse 1s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="text-center">
        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
        </svg>
        <h2 style="font-size: 24px; font-weight: bold; margin-bottom: 8px;">Redirecting to Mobile App...</h2>
        <p style="color: #6b7280; margin-bottom: 16px;">You will be redirected to the Paramedis Mobile App</p>
        <div class="spinner"></div>
        <p style="font-size: 14px; color: #9ca3af;">If you are not redirected automatically, <a href="{{ route('paramedis.mobile-app') }}" style="color: #3b82f6; text-decoration: underline;">click here</a>.</p>
    </div>
    
    <script>
        // Redirect immediately
        setTimeout(function() {
            window.location.href = '{{ route('paramedis.mobile-app') }}';
        }, 500);
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Dashboard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #F0F4FF 0%, #E8F2FF 50%, #DDE7FF 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .redirect-container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            max-width: 400px;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(0, 122, 255, 0.1);
            border-left-color: #007AFF;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        h2 {
            color: #1F2937;
            margin-bottom: 10px;
            font-size: 24px;
            font-weight: 700;
        }
        
        p {
            color: #6B7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="redirect-container">
        <div class="loading-spinner"></div>
        <h2>Loading Premium Dashboard</h2>
        <p>Redirecting to your premium paramedis dashboard...</p>
    </div>
    
    <script>
        // Immediate redirect to premium dashboard
        window.location.replace('{{ route("premium.dashboard") }}');
        
        // Fallback redirect after 1 second (should not be needed)
        setTimeout(() => {
            window.location.replace('{{ route("premium.dashboard") }}');
        }, 1000);
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline - Dokterku Mobile</title>
    
    <!-- Minimal inline CSS for offline page -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 40px 30px;
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        
        .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            position: relative;
            overflow: hidden;
        }
        
        .icon::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.2) 50%, transparent 70%);
            animation: shine 2s infinite;
        }
        
        .icon svg {
            width: 40px;
            height: 40px;
            color: white;
            z-index: 1;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        h1 {
            color: #1f2937;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        
        .features {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .features h3 {
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .feature-list {
            list-style: none;
            text-align: left;
        }
        
        .feature-list li {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 8px;
            padding-left: 20px;
            position: relative;
        }
        
        .feature-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        
        .retry-button {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }
        
        .retry-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .retry-button:active {
            transform: translateY(0);
        }
        
        .status {
            margin-top: 20px;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status.offline {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .status.online {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 20px;
            }
            
            p {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
        </div>
        
        <h1>Anda Sedang Offline</h1>
        <p>Tidak ada koneksi internet saat ini. Beberapa fitur tetap tersedia untuk Anda gunakan.</p>
        
        <div class="features">
            <h3>Fitur yang Tersedia Offline</h3>
            <ul class="feature-list">
                <li>Lihat data dashboard yang tersimpan</li>
                <li>Akses jadwal jaga sebelumnya</li>
                <li>Review data jaspel</li>
                <li>Navigasi aplikasi</li>
            </ul>
        </div>
        
        <button class="retry-button" onclick="checkConnection()">
            Coba Lagi
        </button>
        
        <div id="status" class="status offline">
            Status: Tidak ada koneksi internet
        </div>
    </div>
    
    <script>
        function updateConnectionStatus() {
            const status = document.getElementById('status');
            
            if (navigator.onLine) {
                status.textContent = 'Status: Koneksi tersambung';
                status.className = 'status online';
                
                // Auto redirect when online
                setTimeout(() => {
                    window.location.href = '/';
                }, 2000);
            } else {
                status.textContent = 'Status: Tidak ada koneksi internet';
                status.className = 'status offline';
            }
        }
        
        function checkConnection() {
            const button = document.querySelector('.retry-button');
            button.textContent = 'Memeriksa...';
            button.disabled = true;
            
            // Simulate network check
            setTimeout(() => {
                updateConnectionStatus();
                button.textContent = 'Coba Lagi';
                button.disabled = false;
                
                if (navigator.onLine) {
                    // Try to reload the page
                    window.location.reload();
                }
            }, 1000);
        }
        
        // Listen for online/offline events
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        
        // Initial status check
        updateConnectionStatus();
        
        console.log('📱 Offline page loaded');
    </script>
</body>
</html>
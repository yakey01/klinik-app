<!DOCTYPE html>
<html>
<head>
    <title>GPS Detector Test</title>
    <style>
        .gps-button {
            background: #10b981;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px;
            width: 100%;
        }
        .gps-button:hover {
            background: #059669;
        }
        .gps-button:disabled {
            background: #6b7280;
            cursor: not-allowed;
        }
        .result {
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            background: #f3f4f6;
        }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
        .input-group {
            margin: 10px 0;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div style="max-width: 600px; margin: 50px auto; padding: 20px;">
        <h1>🧪 GPS Detection Test</h1>
        
        <button id="detectGPS" class="gps-button">
            📍 TEST DETEKSI LOKASI
        </button>
        
        <div id="result" class="result" style="display: none;"></div>
        
        <div class="input-group">
            <label for="testLat">Latitude:</label>
            <input type="text" id="testLat" readonly>
        </div>
        
        <div class="input-group">
            <label for="testLon">Longitude:</label>
            <input type="text" id="testLon" readonly>
        </div>
        
        <div class="input-group">
            <label for="testAcc">Akurasi (meter):</label>
            <input type="text" id="testAcc" readonly>
        </div>
    </div>

    <script>
        document.getElementById('detectGPS').addEventListener('click', function() {
            const button = this;
            const result = document.getElementById('result');
            const latInput = document.getElementById('testLat');
            const lonInput = document.getElementById('testLon');
            const accInput = document.getElementById('testAcc');
            
            // Reset UI
            button.disabled = true;
            button.innerHTML = '🔍 Mendeteksi lokasi...';
            result.style.display = 'none';
            latInput.value = '';
            lonInput.value = '';
            accInput.value = '';
            
            console.log('🔍 Starting GPS detection...');
            
            // Check if geolocation is supported
            if (!navigator.geolocation) {
                console.error('❌ Geolocation not supported');
                showError('Browser tidak mendukung geolocation!');
                return;
            }
            
            console.log('✅ Geolocation supported');
            
            // Get current position
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    console.log('✅ Position obtained:', position);
                    
                    const lat = position.coords.latitude.toFixed(8);
                    const lon = position.coords.longitude.toFixed(8);
                    const acc = Math.round(position.coords.accuracy);
                    
                    // Fill inputs
                    latInput.value = lat;
                    lonInput.value = lon;
                    accInput.value = acc;
                    
                    // Show success
                    showSuccess(`Lokasi berhasil dideteksi!<br>
                               📌 Latitude: ${lat}<br>
                               📌 Longitude: ${lon}<br>
                               🎯 Akurasi: ±${acc} meter`);
                    
                    button.innerHTML = '✅ BERHASIL!';
                    button.style.backgroundColor = '#10b981';
                    
                    setTimeout(() => {
                        button.innerHTML = '📍 TEST DETEKSI LOKASI';
                        button.disabled = false;
                        button.style.backgroundColor = '';
                    }, 3000);
                },
                function(error) {
                    console.error('❌ Geolocation error:', error);
                    
                    let message = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            message = 'Akses lokasi ditolak! Silakan izinkan akses lokasi di browser.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message = 'Lokasi tidak tersedia! Pastikan GPS aktif dan koneksi internet stabil.';
                            break;
                        case error.TIMEOUT:
                            message = 'Timeout! Deteksi lokasi terlalu lama.';
                            break;
                        default:
                            message = 'Error tidak dikenal: ' + error.message;
                            break;
                    }
                    
                    showError(message);
                    
                    button.innerHTML = '❌ GAGAL';
                    button.style.backgroundColor = '#ef4444';
                    
                    setTimeout(() => {
                        button.innerHTML = '📍 TEST DETEKSI LOKASI';
                        button.disabled = false;
                        button.style.backgroundColor = '';
                    }, 3000);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
            
            function showSuccess(message) {
                result.innerHTML = message;
                result.className = 'result success';
                result.style.display = 'block';
            }
            
            function showError(message) {
                result.innerHTML = message;
                result.className = 'result error';
                result.style.display = 'block';
            }
        });
    </script>
</body>
</html>
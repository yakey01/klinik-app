<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-3">
                <x-heroicon-o-camera class="h-6 w-6 text-green-500" />
                <span class="text-lg font-semibold">Attendance Lab - Face Recognition + GPS</span>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Current Status --}}
            <div class="text-center p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="current-time">
                    {{ now()->format('H:i:s') }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ now()->format('l, d F Y') }}
                </div>
                
                @if($todayAttendance)
                    <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                        ✅ Masuk: {{ Carbon\Carbon::parse($todayAttendance->time_in)->format('H:i') }}
                        @if($todayAttendance->time_out)
                            | Pulang: {{ Carbon\Carbon::parse($todayAttendance->time_out)->format('H:i') }}
                        @endif
                    </div>
                @endif
            </div>

            {{-- Face Recognition Section --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Camera Preview --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-camera class="h-5 w-5" />
                        Face Recognition Camera
                    </h3>
                    
                    <div class="relative bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden" style="aspect-ratio: 4/3;">
                        <video id="video" class="w-full h-full object-cover" autoplay muted playsinline></video>
                        <canvas id="canvas" class="absolute inset-0 w-full h-full" style="display: none;"></canvas>
                        
                        {{-- Overlay for face detection --}}
                        <div id="face-overlay" class="absolute inset-0 pointer-events-none">
                            <div id="face-box" class="absolute border-2 border-green-400 rounded-lg" style="display: none;"></div>
                        </div>
                        
                        {{-- Status indicators --}}
                        <div class="absolute top-2 left-2 space-y-1">
                            <div id="camera-status" class="px-2 py-1 rounded text-xs font-medium bg-yellow-500 text-white">
                                📷 Initializing Camera...
                            </div>
                            <div id="face-status" class="px-2 py-1 rounded text-xs font-medium bg-gray-500 text-white" style="display: none;">
                                👤 No Face Detected
                            </div>
                            <div id="gps-status" class="px-2 py-1 rounded text-xs font-medium bg-blue-500 text-white">
                                📍 Getting Location...
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Controls and Info --}}
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-map-pin class="h-5 w-5" />
                        Location & Controls
                    </h3>
                    
                    {{-- Location Info --}}
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <div class="text-sm font-medium text-blue-800 dark:text-blue-300">Current Location</div>
                        <div id="location-info" class="text-sm text-blue-600 dark:text-blue-400 mt-1">
                            Detecting location...
                        </div>
                        <div id="coordinates" class="text-xs text-blue-500 dark:text-blue-500 mt-1" style="display: none;">
                        </div>
                    </div>
                    
                    {{-- Device Info --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg border border-gray-200 dark:border-gray-800">
                        <div class="text-sm font-medium text-gray-800 dark:text-gray-300">Device Information</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            <div>Browser: <span id="browser-info">Detecting...</span></div>
                            <div>Device: <span id="device-info">Detecting...</span></div>
                            <div>IP: <span id="ip-info">Detecting...</span></div>
                        </div>
                    </div>
                    
                    {{-- Action Buttons --}}
                    <div class="space-y-3">
                        @if($canCheckin)
                            <button 
                                id="checkin-btn" 
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                disabled
                            >
                                <x-heroicon-o-play-circle class="h-5 w-5" />
                                Check In - Face & Location Required
                            </button>
                        @endif
                        
                        @if($canCheckout)
                            <button 
                                id="checkout-btn" 
                                class="w-full bg-orange-600 hover:bg-orange-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                disabled
                            >
                                <x-heroicon-o-stop-circle class="h-5 w-5" />
                                Check Out - Face & Location Required
                            </button>
                        @endif
                        
                        @if($todayAttendance && $todayAttendance->time_out)
                            <div class="text-center p-3 bg-green-100 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                <div class="text-green-800 dark:text-green-300 font-medium">✅ Attendance Complete for Today</div>
                                <div class="text-sm text-green-600 dark:text-green-400">
                                    Work Duration: {{ Carbon\Carbon::parse($todayAttendance->time_out)->diffForHumans(Carbon\Carbon::parse($todayAttendance->time_in), true) }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Requirements Checklist --}}
            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <h4 class="font-medium text-yellow-800 dark:text-yellow-300 mb-2">Requirements Checklist:</h4>
                <div class="space-y-1 text-sm">
                    <div id="req-camera" class="text-yellow-700 dark:text-yellow-400">
                        <span id="req-camera-icon">⏳</span> Camera Access
                    </div>
                    <div id="req-face" class="text-yellow-700 dark:text-yellow-400">
                        <span id="req-face-icon">⏳</span> Face Detection
                    </div>
                    <div id="req-gps" class="text-yellow-700 dark:text-yellow-400">
                        <span id="req-gps-icon">⏳</span> GPS Location
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Include face-api.js from CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    
    <script>
        class AttendanceLab {
            constructor() {
                this.video = document.getElementById('video');
                this.canvas = document.getElementById('canvas');
                this.faceDetected = false;
                this.locationDetected = false;
                this.currentPosition = null;
                this.deviceInfo = null;
                
                this.init();
            }
            
            async init() {
                console.log('🚀 Initializing Attendance Lab...');
                
                // Get device info
                this.getDeviceInfo();
                
                // Initialize camera
                await this.initCamera();
                
                // Initialize face detection
                await this.initFaceDetection();
                
                // Get location
                await this.getLocation();
                
                // Start real-time detection
                this.startDetection();
                
                // Update clock
                this.updateClock();
                setInterval(() => this.updateClock(), 1000);
            }
            
            getDeviceInfo() {
                const userAgent = navigator.userAgent;
                const browser = this.getBrowserInfo(userAgent);
                const device = this.getDeviceInfo2(userAgent);
                
                document.getElementById('browser-info').textContent = browser;
                document.getElementById('device-info').textContent = device;
                
                // Get IP (simplified - in production use proper service)
                fetch('https://api.ipify.org?format=json')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('ip-info').textContent = data.ip;
                    })
                    .catch(() => {
                        document.getElementById('ip-info').textContent = 'Unable to detect';
                    });
                    
                this.deviceInfo = {
                    userAgent: userAgent,
                    browser: browser,
                    device: device,
                    timestamp: new Date().toISOString()
                };
            }
            
            getBrowserInfo(userAgent) {
                if (userAgent.includes('Chrome')) return 'Chrome';
                if (userAgent.includes('Firefox')) return 'Firefox';
                if (userAgent.includes('Safari')) return 'Safari';
                if (userAgent.includes('Edge')) return 'Edge';
                return 'Unknown';
            }
            
            getDeviceInfo2(userAgent) {
                if (/Mobi|Android/i.test(userAgent)) return 'Mobile';
                if (/Tablet|iPad/i.test(userAgent)) return 'Tablet';
                return 'Desktop';
            }
            
            async initCamera() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { 
                            width: 640, 
                            height: 480,
                            facingMode: 'user'
                        } 
                    });
                    
                    this.video.srcObject = stream;
                    this.updateCameraStatus('✅ Camera Active', 'bg-green-500');
                    this.updateRequirement('camera', true);
                    
                    console.log('📷 Camera initialized successfully');
                } catch (error) {
                    console.error('❌ Camera initialization failed:', error);
                    this.updateCameraStatus('❌ Camera Access Denied', 'bg-red-500');
                    this.updateRequirement('camera', false);
                }
            }
            
            async initFaceDetection() {
                try {
                    console.log('🤖 Loading face detection models...');
                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights'),
                        faceapi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights'),
                        faceapi.nets.faceRecognitionNet.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights')
                    ]);
                    console.log('✅ Face detection models loaded');
                } catch (error) {
                    console.error('❌ Face detection initialization failed:', error);
                }
            }
            
            async getLocation() {
                if (!navigator.geolocation) {
                    this.updateGpsStatus('❌ GPS Not Supported', 'bg-red-500');
                    this.updateRequirement('gps', false);
                    return;
                }
                
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.currentPosition = position;
                        this.locationDetected = true;
                        
                        const { latitude, longitude, accuracy } = position.coords;
                        
                        document.getElementById('location-info').textContent = 
                            `Accuracy: ${Math.round(accuracy)}m`;
                        document.getElementById('coordinates').textContent = 
                            `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                        document.getElementById('coordinates').style.display = 'block';
                        
                        this.updateGpsStatus('✅ Location Detected', 'bg-green-500');
                        this.updateRequirement('gps', true);
                        
                        console.log('📍 Location detected:', { latitude, longitude, accuracy });
                        this.checkRequirements();
                    },
                    (error) => {
                        console.error('❌ Location detection failed:', error);
                        this.updateGpsStatus('❌ Location Access Denied', 'bg-red-500');
                        this.updateRequirement('gps', false);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            }
            
            startDetection() {
                const detectFaces = async () => {
                    if (this.video.readyState === 4) {
                        const detections = await faceapi.detectAllFaces(
                            this.video, 
                            new faceapi.TinyFaceDetectorOptions()
                        );
                        
                        if (detections.length > 0) {
                            if (!this.faceDetected) {
                                this.faceDetected = true;
                                this.updateFaceStatus('✅ Face Detected', 'bg-green-500');
                                this.updateRequirement('face', true);
                                console.log('👤 Face detected');
                                this.checkRequirements();
                            }
                        } else {
                            if (this.faceDetected) {
                                this.faceDetected = false;
                                this.updateFaceStatus('👤 No Face Detected', 'bg-gray-500');
                                this.updateRequirement('face', false);
                                this.checkRequirements();
                            }
                        }
                    }
                };
                
                // Run face detection every 500ms
                setInterval(detectFaces, 500);
            }
            
            checkRequirements() {
                const allRequirementsMet = this.faceDetected && this.locationDetected;
                
                const checkinBtn = document.getElementById('checkin-btn');
                const checkoutBtn = document.getElementById('checkout-btn');
                
                if (checkinBtn) {
                    checkinBtn.disabled = !allRequirementsMet;
                    if (allRequirementsMet) {
                        checkinBtn.textContent = '✅ Ready to Check In';
                        checkinBtn.onclick = () => this.performCheckin();
                    } else {
                        checkinBtn.innerHTML = '<svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Check In - Face & Location Required';
                    }
                }
                
                if (checkoutBtn) {
                    checkoutBtn.disabled = !allRequirementsMet;
                    if (allRequirementsMet) {
                        checkoutBtn.textContent = '✅ Ready to Check Out';
                        checkoutBtn.onclick = () => this.performCheckout();
                    } else {
                        checkoutBtn.innerHTML = '<svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Check Out - Face & Location Required';
                    }
                }
            }
            
            async performCheckin() {
                console.log('⏰ Performing check-in...');
                
                // Capture face image
                const canvas = document.createElement('canvas');
                canvas.width = this.video.videoWidth;
                canvas.height = this.video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(this.video, 0, 0);
                const imageData = canvas.toDataURL('image/jpeg', 0.8);
                
                // Prepare data
                const attendanceData = {
                    action: 'checkin',
                    latitude: this.currentPosition.coords.latitude,
                    longitude: this.currentPosition.coords.longitude,
                    accuracy: this.currentPosition.coords.accuracy,
                    device_info: JSON.stringify(this.deviceInfo),
                    face_image: imageData,
                    timestamp: new Date().toISOString()
                };
                
                try {
                    const response = await fetch('/api/paramedis/attendance/checkin', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(attendanceData)
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok) {
                        alert('✅ Check-in successful!');
                        window.location.reload();
                    } else {
                        alert('❌ Check-in failed: ' + result.message);
                    }
                } catch (error) {
                    console.error('❌ Check-in error:', error);
                    alert('❌ Check-in failed: Network error');
                }
            }
            
            async performCheckout() {
                console.log('🏠 Performing check-out...');
                
                // Similar to check-in but for checkout
                const canvas = document.createElement('canvas');
                canvas.width = this.video.videoWidth;
                canvas.height = this.video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(this.video, 0, 0);
                const imageData = canvas.toDataURL('image/jpeg', 0.8);
                
                const attendanceData = {
                    action: 'checkout',
                    latitude: this.currentPosition.coords.latitude,
                    longitude: this.currentPosition.coords.longitude,
                    accuracy: this.currentPosition.coords.accuracy,
                    device_info: JSON.stringify(this.deviceInfo),
                    face_image: imageData,
                    timestamp: new Date().toISOString()
                };
                
                try {
                    const response = await fetch('/api/paramedis/attendance/checkout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(attendanceData)
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok) {
                        alert('✅ Check-out successful!');
                        window.location.reload();
                    } else {
                        alert('❌ Check-out failed: ' + result.message);
                    }
                } catch (error) {
                    console.error('❌ Check-out error:', error);
                    alert('❌ Check-out failed: Network error');
                }
            }
            
            updateCameraStatus(text, bgClass) {
                const status = document.getElementById('camera-status');
                status.textContent = text;
                status.className = `px-2 py-1 rounded text-xs font-medium text-white ${bgClass}`;
            }
            
            updateFaceStatus(text, bgClass) {
                const status = document.getElementById('face-status');
                status.textContent = text;
                status.className = `px-2 py-1 rounded text-xs font-medium text-white ${bgClass}`;
                status.style.display = 'block';
            }
            
            updateGpsStatus(text, bgClass) {
                const status = document.getElementById('gps-status');
                status.textContent = text;
                status.className = `px-2 py-1 rounded text-xs font-medium text-white ${bgClass}`;
            }
            
            updateRequirement(type, success) {
                const icon = document.getElementById(`req-${type}-icon`);
                icon.textContent = success ? '✅' : '❌';
            }
            
            updateClock() {
                const now = new Date();
                document.getElementById('current-time').textContent = 
                    now.toLocaleTimeString('id-ID', { hour12: false });
            }
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', () => {
            new AttendanceLab();
        });
    </script>
</x-filament-widgets::widget>
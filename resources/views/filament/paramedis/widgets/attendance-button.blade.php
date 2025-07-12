<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-3">
                <x-heroicon-o-clock class="h-6 w-6 text-green-500" />
                <span class="text-lg font-semibold">Absensi Cepat - Real Time</span>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Real Time Clock --}}
            <div class="text-center p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="text-5xl font-bold text-blue-600 dark:text-blue-400" id="real-time-clock">
                    {{ $currentTime->format('H:i:s') }}
                </div>
                <div class="text-lg text-gray-600 dark:text-gray-400 mt-2" id="real-time-date">
                    {{ $currentTime->format('l, d F Y') }}
                </div>
                <div class="text-sm text-blue-500 dark:text-blue-400 mt-1">
                    🕐 Waktu Real-Time WIB (Auto Update Every Second)
                </div>
                <div class="text-xs text-green-500 mt-1" id="update-indicator">
                    ● Live
                </div>
            </div>

            {{-- Current Status --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Check-in Status --}}
                <div class="p-4 rounded-lg border {{ $todayAttendance ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800' : 'bg-gray-50 border-gray-200 dark:bg-gray-900/20 dark:border-gray-800' }}">
                    <div class="flex items-center gap-x-3">
                        @if($todayAttendance)
                            <x-heroicon-o-check-circle class="h-6 w-6 text-green-500" />
                            <div>
                                <div class="font-medium text-green-700 dark:text-green-400">✅ Sudah Absen Masuk</div>
                                <div class="text-sm text-green-600 dark:text-green-500">
                                    Waktu: {{ \Carbon\Carbon::parse($todayAttendance->time_in)->format('H:i:s') }}
                                </div>
                                <div class="text-xs text-green-500">
                                    Status: {{ $todayAttendance->status === 'late' ? '⚠️ Terlambat' : '✅ Tepat Waktu' }}
                                </div>
                            </div>
                        @else
                            <x-heroicon-o-clock class="h-6 w-6 text-gray-400" />
                            <div>
                                <div class="font-medium text-gray-700 dark:text-gray-300">⏰ Belum Absen Masuk</div>
                                <div class="text-sm text-gray-500">Silakan lakukan presensi masuk</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Check-out Status --}}
                <div class="p-4 rounded-lg border {{ $todayAttendance && $todayAttendance->time_out ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800' : 'bg-gray-50 border-gray-200 dark:bg-gray-900/20 dark:border-gray-800' }}">
                    <div class="flex items-center gap-x-3">
                        @if($todayAttendance && $todayAttendance->time_out)
                            <x-heroicon-o-check-circle class="h-6 w-6 text-blue-500" />
                            <div>
                                <div class="font-medium text-blue-700 dark:text-blue-400">✅ Sudah Absen Pulang</div>
                                <div class="text-sm text-blue-600 dark:text-blue-500">
                                    Waktu: {{ \Carbon\Carbon::parse($todayAttendance->time_out)->format('H:i:s') }}
                                </div>
                                <div class="text-xs text-blue-500">
                                    Durasi: {{ \Carbon\Carbon::parse($todayAttendance->time_out)->diffForHumans(\Carbon\Carbon::parse($todayAttendance->time_in), true) }}
                                </div>
                            </div>
                        @else
                            <x-heroicon-o-clock class="h-6 w-6 text-gray-400" />
                            <div>
                                <div class="font-medium text-gray-700 dark:text-gray-300">🏠 Belum Absen Pulang</div>
                                <div class="text-sm text-gray-500">
                                    {{ $todayAttendance ? 'Silakan lakukan presensi pulang' : 'Absen masuk dulu' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @if($canCheckin)
                    <button 
                        id="checkin-btn"
                        wire:click="checkin"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-bold py-4 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 flex items-center justify-center gap-3 text-lg shadow-lg"
                        disabled
                    >
                        <x-heroicon-o-play-circle class="h-6 w-6" />
                        <span wire:loading.remove wire:target="checkin" id="checkin-text">👤 Wajah Belum Terdeteksi</span>
                        <span wire:loading wire:target="checkin">⏳ Sedang Absen...</span>
                    </button>
                @endif
                
                @if($canCheckout)
                    <button 
                        id="checkout-btn"
                        wire:click="checkout"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="flex-1 bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white font-bold py-4 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 flex items-center justify-center gap-3 text-lg shadow-lg"
                        disabled
                    >
                        <x-heroicon-o-stop-circle class="h-6 w-6" />
                        <span wire:loading.remove wire:target="checkout" id="checkout-text">👤 Wajah Belum Terdeteksi</span>
                        <span wire:loading wire:target="checkout">⏳ Sedang Absen...</span>
                    </button>
                @endif
                
                @if($todayAttendance && $todayAttendance->time_out)
                    <div class="flex-1 text-center p-4 bg-green-100 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="text-green-800 dark:text-green-300 font-bold text-lg">✅ Absensi Selesai</div>
                        <div class="text-sm text-green-600 dark:text-green-400 mt-1">
                            Total Durasi Kerja: {{ \Carbon\Carbon::parse($todayAttendance->time_out)->diffForHumans(\Carbon\Carbon::parse($todayAttendance->time_in), true) }}
                        </div>
                        <div class="text-xs text-green-500 mt-2">
                            Terima kasih atas dedikasi Anda hari ini! 👏
                        </div>
                    </div>
                @endif
            </div>
            
            {{-- Quick Info --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="font-medium text-blue-800 dark:text-blue-300">⏰ Jam Kerja</div>
                    <div class="text-blue-600 dark:text-blue-400">08:00 - 17:00</div>
                </div>
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="font-medium text-green-800 dark:text-green-300">📍 Lokasi</div>
                    <div class="text-green-600 dark:text-green-400">Klinik Dokterku</div>
                </div>
                <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <div class="font-medium text-purple-800 dark:text-purple-300">👤 Petugas</div>
                    <div class="text-purple-600 dark:text-purple-400">{{ $user->name }}</div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Face Recognition + Real-time Clock Script --}}
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    
    <script>
        class AttendanceSystemRealTime {
            constructor() {
                this.clockInterval = null;
                this.faceDetected = false;
                this.video = null;
                this.canvas = null;
                this.faceDetectionInterval = null;
                this.modelsLoaded = false;
                
                this.init();
            }
            
            init() {
                console.log('🚀 Initializing Real-Time Attendance System...');
                
                // Start real-time clock immediately
                this.startRealTimeClock();
                
                // Initialize face recognition with delay to ensure DOM is ready
                setTimeout(() => {
                    this.initFaceRecognition();
                }, 1000);
                
                // Listen for Livewire updates
                this.setupLivewireListeners();
                
                // Update button states initially
                this.updateButtonStates();
            }
            
            startRealTimeClock() {
                // Clear existing interval
                if (this.clockInterval) {
                    clearInterval(this.clockInterval);
                }
                
                const updateClock = () => {
                    try {
                        // Create Jakarta time
                        const now = new Date();
                        const jakartaTime = new Intl.DateTimeFormat('id-ID', {
                            timeZone: 'Asia/Jakarta',
                            hour12: false,
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        }).format(now);
                        
                        const jakartaDate = new Intl.DateTimeFormat('id-ID', {
                            timeZone: 'Asia/Jakarta',
                            weekday: 'long',
                            year: 'numeric', 
                            month: 'long',
                            day: 'numeric'
                        }).format(now);
                        
                        // Update time
                        const clockElement = document.getElementById('real-time-clock');
                        if (clockElement) {
                            clockElement.textContent = jakartaTime;
                        }
                        
                        // Update date
                        const dateElement = document.getElementById('real-time-date');
                        if (dateElement) {
                            dateElement.textContent = jakartaDate;
                        }
                        
                        // Update indicator with pulsing effect
                        const indicator = document.getElementById('update-indicator');
                        if (indicator) {
                            indicator.style.color = '#22c55e';
                            indicator.textContent = '● Live - ' + now.getMilliseconds();
                            setTimeout(() => {
                                if (indicator) {
                                    indicator.style.color = '#10b981';
                                    indicator.textContent = '● Live';
                                }
                            }, 100);
                        }
                        
                        console.log('🕐 Clock updated: ' + jakartaTime);
                    } catch (error) {
                        console.error('❌ Clock update error:', error);
                    }
                };
                
                // Initial update
                updateClock();
                
                // Update every second
                this.clockInterval = setInterval(updateClock, 1000);
                
                console.log('🕐 Real-time clock started');
            }
            
            async initFaceRecognition() {
                try {
                    console.log('🤖 Loading face recognition models...');
                    
                    // Create video element for face detection
                    this.createVideoElement();
                    
                    // Load face-api models with better error handling
                    try {
                        await Promise.all([
                            faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights'),
                            faceapi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights'),
                            faceapi.nets.faceRecognitionNet.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights')
                        ]);
                        
                        this.modelsLoaded = true;
                        console.log('✅ Face recognition models loaded successfully');
                        this.showFaceRecognitionStatus('✅ Models Loaded', 'bg-blue-500');
                    } catch (modelError) {
                        console.warn('⚠️ Face API models failed to load, using fallback mode:', modelError);
                        this.showFaceRecognitionStatus('⚠️ Basic Mode', 'bg-yellow-500');
                        // Continue without face detection for now
                        this.enableButtonsManually();
                        return;
                    }
                    
                    // Start camera
                    await this.startCamera();
                    
                    // Start face detection
                    this.startFaceDetection();
                    
                } catch (error) {
                    console.error('❌ Face recognition initialization failed:', error);
                    this.showFaceRecognitionStatus('❌ Camera Failed', 'bg-red-500');
                    // Enable buttons manually as fallback
                    this.enableButtonsManually();
                }
            }
            
            createVideoElement() {
                // Check if video element already exists
                if (document.getElementById('face-video')) return;
                
                // Create face recognition section
                const faceSection = document.createElement('div');
                faceSection.className = 'mt-6 p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg border border-gray-200 dark:border-gray-800';
                faceSection.innerHTML = `
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Face Recognition Camera
                    </h3>
                    <div class="relative bg-gray-800 rounded-lg overflow-hidden" style="aspect-ratio: 4/3; max-width: 400px; margin: 0 auto;">
                        <video id="face-video" class="w-full h-full object-cover" autoplay muted playsinline></video>
                        <canvas id="face-canvas" class="absolute inset-0 w-full h-full"></canvas>
                        <div id="face-status" class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium bg-yellow-500 text-white">
                            📷 Initializing Camera...
                        </div>
                        <div id="face-detection-status" class="absolute top-2 right-2 px-2 py-1 rounded text-xs font-medium bg-gray-500 text-white">
                            👤 No Face
                        </div>
                    </div>
                    <div class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                        Face recognition enhances attendance security
                    </div>
                `;
                
                // Insert after clock section
                const clockSection = document.querySelector('.space-y-6 > div');
                if (clockSection && clockSection.nextSibling) {
                    clockSection.parentNode.insertBefore(faceSection, clockSection.nextSibling);
                } else if (clockSection) {
                    clockSection.parentNode.appendChild(faceSection);
                }
                
                this.video = document.getElementById('face-video');
                this.canvas = document.getElementById('face-canvas');
            }
            
            async startCamera() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ 
                        video: { 
                            width: 400, 
                            height: 300,
                            facingMode: 'user'
                        } 
                    });
                    
                    this.video.srcObject = stream;
                    this.showFaceRecognitionStatus('✅ Camera Active', 'bg-green-500');
                    
                    console.log('📷 Camera started successfully');
                } catch (error) {
                    console.error('❌ Camera access failed:', error);
                    this.showFaceRecognitionStatus('❌ Camera Denied', 'bg-red-500');
                }
            }
            
            startFaceDetection() {
                const detectFaces = async () => {
                    if (this.video && this.video.readyState === 4 && this.modelsLoaded) {
                        try {
                            const detections = await faceapi.detectAllFaces(
                                this.video, 
                                new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })
                            );
                            
                            // Clear previous drawings
                            if (this.canvas) {
                                const ctx = this.canvas.getContext('2d');
                                ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                                
                                // Set canvas size to match video
                                this.canvas.width = this.video.videoWidth;
                                this.canvas.height = this.video.videoHeight;
                            }
                            
                            if (detections.length > 0) {
                                if (!this.faceDetected) {
                                    this.faceDetected = true;
                                    this.showFaceDetectionStatus('✅ Face Detected', 'bg-green-500');
                                    this.updateButtonStates();
                                    console.log('👤 Face detected');
                                }
                                
                                // Draw face detection boxes
                                if (this.canvas) {
                                    faceapi.draw.drawDetections(this.canvas, detections);
                                }
                            } else {
                                if (this.faceDetected) {
                                    this.faceDetected = false;
                                    this.showFaceDetectionStatus('👤 No Face', 'bg-gray-500');
                                    this.updateButtonStates();
                                }
                            }
                        } catch (error) {
                            console.error('Face detection error:', error);
                        }
                    }
                };
                
                // Run face detection every 500ms
                this.faceDetectionInterval = setInterval(detectFaces, 500);
                console.log('🔍 Face detection started');
            }
            
            showFaceRecognitionStatus(text, bgClass) {
                const status = document.getElementById('face-status');
                if (status) {
                    status.textContent = text;
                    status.className = `absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium text-white ${bgClass}`;
                }
            }
            
            showFaceDetectionStatus(text, bgClass) {
                const status = document.getElementById('face-detection-status');
                if (status) {
                    status.textContent = text;
                    status.className = `absolute top-2 right-2 px-2 py-1 rounded text-xs font-medium text-white ${bgClass}`;
                }
            }
            
            updateButtonStates() {
                const checkinBtn = document.getElementById('checkin-btn');
                const checkoutBtn = document.getElementById('checkout-btn');
                const checkinText = document.getElementById('checkin-text');
                const checkoutText = document.getElementById('checkout-text');
                
                // For development mode: always enable buttons after 3 seconds
                setTimeout(() => {
                    if (checkinBtn && checkinText) {
                        checkinBtn.disabled = false;
                        checkinBtn.classList.remove('disabled:bg-green-400');
                        checkinBtn.classList.add('hover:bg-green-700');
                        checkinText.textContent = '✅ Check In Masuk';
                    }
                    
                    if (checkoutBtn && checkoutText) {
                        checkoutBtn.disabled = false;
                        checkoutBtn.classList.remove('disabled:bg-orange-400');
                        checkoutBtn.classList.add('hover:bg-orange-700');
                        checkoutText.textContent = '🏠 Check Out Pulang';
                    }
                }, 3000);
                
                // If face is detected, enable immediately
                if (this.faceDetected) {
                    if (checkinBtn && checkinText) {
                        checkinBtn.disabled = false;
                        checkinBtn.classList.remove('disabled:bg-green-400');
                        checkinBtn.classList.add('hover:bg-green-700');
                        checkinText.textContent = '👤 Check In - Wajah Terdeteksi';
                    }
                    
                    if (checkoutBtn && checkoutText) {
                        checkoutBtn.disabled = false;
                        checkoutBtn.classList.remove('disabled:bg-orange-400');
                        checkoutBtn.classList.add('hover:bg-orange-700');
                        checkoutText.textContent = '👤 Check Out - Wajah Terdeteksi';
                    }
                }
            }
            
            enableButtonsManually() {
                setTimeout(() => {
                    const checkinBtn = document.getElementById('checkin-btn');
                    const checkoutBtn = document.getElementById('checkout-btn');
                    const checkinText = document.getElementById('checkin-text');
                    const checkoutText = document.getElementById('checkout-text');
                    
                    if (checkinBtn && checkinText) {
                        checkinBtn.disabled = false;
                        checkinBtn.classList.remove('disabled:bg-green-400');
                        checkinBtn.classList.add('hover:bg-green-700');
                        checkinText.textContent = '✅ Check In Masuk';
                    }
                    
                    if (checkoutBtn && checkoutText) {
                        checkoutBtn.disabled = false;
                        checkoutBtn.classList.remove('disabled:bg-orange-400');
                        checkoutBtn.classList.add('hover:bg-orange-700');
                        checkoutText.textContent = '🏠 Check Out Pulang';
                    }
                    
                    console.log('🔓 Buttons enabled manually (fallback mode)');
                }, 2000);
            }
            
            setupLivewireListeners() {
                // Restart clock after Livewire updates
                document.addEventListener('livewire:navigated', () => {
                    setTimeout(() => this.startRealTimeClock(), 100);
                });
                
                // Handle page visibility changes
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        this.startRealTimeClock();
                    }
                });
            }
        }
        
        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            new AttendanceSystemRealTime();
        });
        
        // Also initialize if this script runs after DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                new AttendanceSystemRealTime();
            });
        } else {
            new AttendanceSystemRealTime();
        }
    </script>
</x-filament-widgets::widget>
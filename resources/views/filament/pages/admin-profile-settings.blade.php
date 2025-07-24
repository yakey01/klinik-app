<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-6 text-white">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                        <x-heroicon-o-user-circle class="w-10 h-10" />
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">{{ auth()->user()->name }}</h1>
                    <p class="text-blue-100">{{ auth()->user()->email }}</p>
                    <p class="text-sm text-blue-200">Administrator • Login terakhir: {{ auth()->user()->updated_at?->diffForHumans() ?? 'Tidak diketahui' }}</p>
                </div>
            </div>
        </div>

        <!-- Email Change Form -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-envelope class="w-5 h-5 text-blue-600" />
                    <h2 class="text-lg font-semibold text-gray-900">Ganti Email Admin</h2>
                </div>
                <p class="text-sm text-gray-600 mt-1">Ubah alamat email yang digunakan untuk login ke sistem admin</p>
            </div>
            <div class="p-6">
                <form wire:submit="updateEmail">
                    {{ $this->getForms()['emailForm'] }}
                    
                    <div class="mt-6 flex justify-end">
                        {{ ($this->getActions())[0] }}
                    </div>
                </form>
            </div>
        </div>

        <!-- Password Change Form -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-lock-closed class="w-5 h-5 text-amber-600" />
                    <h2 class="text-lg font-semibold text-gray-900">Ganti Password Admin</h2>
                </div>
                <p class="text-sm text-gray-600 mt-1">Ubah password untuk menjaga keamanan akun admin Anda</p>
            </div>
            <div class="p-6">
                <form wire:submit="updatePassword">
                    {{ $this->getForms()['passwordForm'] }}
                    
                    <div class="mt-6 flex justify-end">
                        {{ ($this->getActions())[1] }}
                    </div>
                </form>
            </div>
        </div>

        <!-- Security Info -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Tips Keamanan</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Gunakan password yang kuat (minimal 8 karakter, mengandung huruf besar, kecil, dan angka)</li>
                            <li>Jangan bagikan informasi login kepada siapapun</li>
                            <li>Ubah password secara berkala untuk menjaga keamanan</li>
                            <li>Pastikan email yang digunakan masih aktif dan dapat diakses</li>
                            <li>Notifikasi perubahan akan dikirim ke email lama dan baru</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Log Preview -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center space-x-2">
                    <x-heroicon-o-clock class="w-5 h-5 text-green-600" />
                    <h2 class="text-lg font-semibold text-gray-900">Aktivitas Terbaru</h2>
                </div>
            </div>
            <div class="p-6">
                @php
                    $recentLogs = \App\Models\AuditLog::where('user_id', auth()->id())
                        ->whereIn('action', ['email_changed', 'password_changed', 'login'])
                        ->latest()
                        ->take(5)
                        ->get();
                @endphp

                @if($recentLogs->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentLogs as $log)
                            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                                <div class="flex items-center space-x-3">
                                    @if($log->action === 'email_changed')
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <x-heroicon-o-envelope class="w-4 h-4 text-blue-600" />
                                        </div>
                                    @elseif($log->action === 'password_changed')
                                        <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center">
                                            <x-heroicon-o-lock-closed class="w-4 h-4 text-amber-600" />
                                        </div>
                                    @else
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 text-green-600" />
                                        </div>
                                    @endif
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $log->description }}</p>
                                        <p class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }} • IP: {{ $log->ip_address }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">Belum ada aktivitas yang tercatat</p>
                @endif
            </div>
        </div>
    </div>

    <style>
        .fi-form-field-wrp {
            margin-bottom: 1rem;
        }
        
        .fi-input-wrp {
            position: relative;
        }
        
        .fi-btn {
            transition: all 0.2s ease-in-out;
        }
        
        .fi-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add some interactive feedback
            const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-blue-500', 'ring-opacity-50');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-blue-500', 'ring-opacity-50');
                });
            });

            // Password strength indicator
            const passwordInput = document.querySelector('input[name="passwordData.new_password"]');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strength = checkPasswordStrength(password);
                    
                    // Remove existing strength indicator
                    const existingIndicator = this.parentElement.querySelector('.password-strength');
                    if (existingIndicator) {
                        existingIndicator.remove();
                    }
                    
                    if (password.length > 0) {
                        const indicator = document.createElement('div');
                        indicator.className = 'password-strength mt-1 text-xs';
                        indicator.innerHTML = `
                            <div class="flex items-center space-x-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-1">
                                    <div class="h-1 rounded-full transition-all duration-300 ${strength.color}" style="width: ${strength.percentage}%"></div>
                                </div>
                                <span class="text-${strength.textColor}">${strength.text}</span>
                            </div>
                        `;
                        this.parentElement.appendChild(indicator);
                    }
                });
            }
        });

        function checkPasswordStrength(password) {
            let score = 0;
            
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            if (score < 2) {
                return { percentage: 20, color: 'bg-red-500', textColor: 'red-600', text: 'Lemah' };
            } else if (score < 4) {
                return { percentage: 60, color: 'bg-yellow-500', textColor: 'yellow-600', text: 'Sedang' };
            } else {
                return { percentage: 100, color: 'bg-green-500', textColor: 'green-600', text: 'Kuat' };
            }
        }
    </script>
</x-filament-panels::page>
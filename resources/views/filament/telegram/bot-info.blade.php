<div class="telegram-bot-info">
    <div class="space-y-6">
        <!-- Bot Status Card -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
            <div class="flex items-center space-x-4">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-bold">{{ $botInfo['first_name'] ?? 'Unknown Bot' }}</h3>
                    <p class="text-blue-100">@{{ $botInfo['username'] ?? 'unknown' }}</p>
                    <p class="text-sm text-blue-200">Bot ID: {{ $botInfo['id'] ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Bot Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Connection Status -->
            <div class="bg-white rounded-lg border p-6 space-y-4">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900">Status Koneksi</h4>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Status:</span>
                        <span class="text-sm font-medium text-green-600">✅ Terhubung</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Dapat Menerima Pesan:</span>
                        <span class="text-sm font-medium text-green-600">{{ isset($botInfo['can_join_groups']) && $botInfo['can_join_groups'] ? '✅ Ya' : '❌ Tidak' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Dapat Membaca Pesan:</span>
                        <span class="text-sm font-medium text-green-600">{{ isset($botInfo['can_read_all_group_messages']) && $botInfo['can_read_all_group_messages'] ? '✅ Ya' : '❌ Tidak' }}</span>
                    </div>
                </div>
            </div>

            <!-- Bot Capabilities -->
            <div class="bg-white rounded-lg border p-6 space-y-4">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900">Kemampuan Bot</h4>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm text-gray-600">Kirim notifikasi real-time</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm text-gray-600">Kirim rekap harian & mingguan</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm text-gray-600">Format HTML dan markdown</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-sm text-gray-600">Notifikasi role-based</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Setup Guide -->
        <div class="bg-gray-50 rounded-lg p-6">
            <h4 class="font-semibold text-gray-900 mb-4">📋 Panduan Cepat Setup</h4>
            <div class="space-y-3 text-sm text-gray-600">
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                    <div>
                        <p class="font-medium">Buat grup Telegram untuk setiap role</p>
                        <p class="text-gray-500">Contoh: "Dokterku - Admin", "Dokterku - Manajer"</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                    <div>
                        <p class="font-medium">Tambahkan bot @{{ $botInfo['username'] ?? 'dokterku_bot' }} ke grup</p>
                        <p class="text-gray-500">Pastikan bot menjadi admin grup</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                    <div>
                        <p class="font-medium">Dapatkan Chat ID grup menggunakan @userinfobot</p>
                        <p class="text-gray-500">Forward pesan dari grup ke @userinfobot</p>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold">4</span>
                    <div>
                        <p class="font-medium">Tambah pengaturan role di halaman ini</p>
                        <p class="text-gray-500">Masukkan Chat ID dan pilih jenis notifikasi</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Updated Info -->
        <div class="text-center text-sm text-gray-500">
            <p>Informasi terakhir diperbarui: {{ now()->format('d M Y H:i:s') }}</p>
            <p class="mt-1">Bot Token: ●●●●●●{{ substr(config('telegram.bots.dokterku.token', ''), -6) }}</p>
        </div>
    </div>
</div>

<style>
.telegram-bot-info {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.telegram-bot-info .bg-gradient-to-r {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.telegram-bot-info .border {
    border-color: #e5e7eb;
}

.telegram-bot-info .rounded-lg {
    border-radius: 0.75rem;
}

.telegram-bot-info .text-sm {
    font-size: 0.875rem;
    line-height: 1.25rem;
}

.telegram-bot-info .space-y-6 > * + * {
    margin-top: 1.5rem;
}

.telegram-bot-info .space-y-4 > * + * {
    margin-top: 1rem;
}

.telegram-bot-info .space-y-3 > * + * {
    margin-top: 0.75rem;
}

.telegram-bot-info .space-y-2 > * + * {
    margin-top: 0.5rem;
}

.telegram-bot-info .grid {
    display: grid;
}

.telegram-bot-info .grid-cols-1 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 768px) {
    .telegram-bot-info .md\:grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.telegram-bot-info .gap-6 {
    gap: 1.5rem;
}

.telegram-bot-info .flex {
    display: flex;
}

.telegram-bot-info .items-center {
    align-items: center;
}

.telegram-bot-info .items-start {
    align-items: flex-start;
}

.telegram-bot-info .justify-between {
    justify-content: space-between;
}

.telegram-bot-info .space-x-2 > * + * {
    margin-left: 0.5rem;
}

.telegram-bot-info .space-x-3 > * + * {
    margin-left: 0.75rem;
}

.telegram-bot-info .space-x-4 > * + * {
    margin-left: 1rem;
}
</style>
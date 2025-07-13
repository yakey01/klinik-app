@extends('layouts.settings')

@section('title', 'Pengaturan Telegram')

@section('content')
<div class="space-y-6">
    <!-- Bot Configuration -->
    <div class="bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100 flex items-center">
                <svg class="w-6 h-6 text-blue-400 mr-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/>
                </svg>
                Konfigurasi Bot Telegram
            </h2>
        </div>
        
        <div class="p-6">
            <div class="bg-blue-900/20 border border-blue-600 rounded-md p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-blue-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-blue-100">Cara Setup Bot Telegram:</h3>
                        <div class="mt-2 text-sm text-blue-200">
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Chat dengan @BotFather di Telegram</li>
                                <li>Ketik /newbot dan ikuti instruksi</li>
                                <li>Copy token bot dan masukkan ke .env file sebagai TELEGRAM_BOT_TOKEN</li>
                                <li>Dapatkan Chat ID dengan mengirim pesan ke bot, lalu akses https://api.telegram.org/bot[TOKEN]/getUpdates</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.telegram.update') }}">
                @csrf
                
                <!-- Bot Token Display -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Token Bot Telegram</label>
                    <div class="flex items-center space-x-3">
                        <input type="text" 
                               id="telegram_bot_token" 
                               name="telegram_bot_token"
                               value="{{ $telegramToken }}"
                               placeholder="Masukkan token dari @BotFather"
                               class="flex-1 rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" 
                                onclick="checkBotInfo()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                            Cek Bot
                        </button>
                    </div>
                    <div id="bot-info" class="mt-2 text-sm"></div>
                    <p class="text-xs text-gray-400 mt-1">Token bot disimpan di file .env untuk keamanan</p>
                </div>

                <!-- Role-based Settings -->
                <div class="space-y-6">
                    @foreach(['petugas', 'bendahara', 'admin', 'manajer'] as $role)
                        <div class="bg-gray-700 rounded-lg p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-100 capitalize flex items-center">
                                    <span class="inline-block w-3 h-3 rounded-full mr-3
                                        {{ $role === 'admin' ? 'bg-red-500' : 
                                           ($role === 'manajer' ? 'bg-purple-500' : 
                                            ($role === 'bendahara' ? 'bg-green-500' : 'bg-blue-500')) }}"></span>
                                    {{ ucfirst($role) }}
                                </h3>
                                <label class="flex items-center">
                                    <input type="hidden" name="settings[{{ $role }}][is_active]" value="0">
                                    <input type="checkbox" 
                                           name="settings[{{ $role }}][is_active]" 
                                           value="1"
                                           {{ $settings[$role]->is_active ? 'checked' : '' }}
                                           class="rounded bg-gray-600 border-gray-500 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-300">Aktif</span>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Chat ID -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Chat ID</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="text" 
                                               name="settings[{{ $role }}][chat_id]" 
                                               value="{{ $settings[$role]->chat_id }}"
                                               placeholder="Contoh: -1001234567890"
                                               class="flex-1 rounded-md bg-gray-600 border-gray-500 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                                        <button type="button" 
                                                onclick="testNotification('{{ $role }}')" 
                                                class="px-3 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 text-sm">
                                            Test
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1">ID chat untuk menerima notifikasi</p>
                                </div>

                                <!-- Notification Types -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Jenis Notifikasi</label>
                                    <div class="space-y-2 max-h-32 overflow-y-auto">
                                        @foreach(\App\Models\TelegramSetting::getRoleNotifications($role) as $notifType)
                                            <label class="flex items-center">
                                                <input type="checkbox" 
                                                       name="settings[{{ $role }}][notification_types][]" 
                                                       value="{{ $notifType }}"
                                                       {{ in_array($notifType, $settings[$role]->notification_types ?? []) ? 'checked' : '' }}
                                                       class="rounded bg-gray-600 border-gray-500 text-blue-600 focus:ring-blue-500">
                                                <span class="ml-2 text-sm text-gray-300">
                                                    {{ $notificationTypes[$notifType] ?? $notifType }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Submit Button -->
                <div class="mt-6 pt-6 border-t border-gray-700 flex justify-end">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notification Types Reference -->
    <div class="bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100">Referensi Jenis Notifikasi</h2>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($notificationTypes as $key => $title)
                    <div class="flex items-center p-3 bg-gray-700 rounded-lg">
                        <span class="text-2xl mr-3">
                            @switch($key)
                                @case('income_success') 💰 @break
                                @case('patient_success') 👤 @break
                                @case('daily_validation_approved') ✅ @break
                                @case('jaspel_completed') 💼 @break
                                @case('backup_failed') 🚨 @break
                                @case('user_added') 👋 @break
                                @case('daily_recap') 📊 @break
                                @case('weekly_recap') 📈 @break
                                @default 📢
                            @endswitch
                        </span>
                        <div>
                            <div class="text-sm font-medium text-gray-100">{{ $title }}</div>
                            <div class="text-xs text-gray-400">{{ $key }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
function checkBotInfo() {
    const token = document.getElementById('telegram_bot_token').value;
    const botInfoDiv = document.getElementById('bot-info');
    
    if (!token) {
        botInfoDiv.innerHTML = '<div class="text-red-400">Masukkan token bot terlebih dahulu</div>';
        return;
    }
    
    botInfoDiv.innerHTML = '<div class="text-blue-400">Memeriksa bot...</div>';
    
    fetch('/settings/telegram/bot-info', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            botInfoDiv.innerHTML = `
                <div class="text-green-400">
                    ✅ Bot ditemukan: <strong>${data.data.first_name}</strong> (@${data.data.username})
                </div>
            `;
        } else {
            botInfoDiv.innerHTML = `<div class="text-red-400">❌ ${data.message}</div>`;
        }
    })
    .catch(error => {
        botInfoDiv.innerHTML = '<div class="text-red-400">❌ Gagal memeriksa bot</div>';
    });
}

function testNotification(role) {
    const chatIdInput = document.querySelector(`input[name="settings[${role}][chat_id]"]`);
    const chatId = chatIdInput.value;
    
    if (!chatId) {
        alert('Masukkan Chat ID terlebih dahulu');
        return;
    }
    
    fetch('/settings/telegram/test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            role: role,
            chat_id: chatId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Gagal mengirim test notifikasi');
    });
}
</script>
@endsection
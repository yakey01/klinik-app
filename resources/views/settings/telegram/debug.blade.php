<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Debug</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">🤖 Telegram Bot Debug Page</h1>
        
        <!-- Section 1: Bot Configuration -->
        <div class="bg-gray-800 rounded-lg shadow p-6 mb-8">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <svg class="w-6 h-6 text-blue-400 mr-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/>
                </svg>
                Konfigurasi Bot Telegram
            </h2>
            
            <div class="space-y-4">
                <!-- Token Display -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        🔐 Token Bot Telegram
                    </label>
                    <div class="flex">
                        <input type="password" 
                               id="botToken"
                               class="flex-1 bg-gray-700 border border-gray-600 text-gray-300 text-sm rounded-l-lg focus:ring-blue-500 focus:border-blue-500 block p-3"
                               value="{{ str_repeat('•', 20) . substr(env('TELEGRAM_BOT_TOKEN', ''), -6) }}"
                               readonly>
                        <button type="button" 
                                id="toggleToken"
                                class="px-4 py-3 bg-gray-600 border border-l-0 border-gray-600 text-gray-300 text-sm rounded-r-lg hover:bg-gray-500">
                            👁️
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Token: {{ env('TELEGRAM_BOT_TOKEN') ? 'SET' : 'NOT SET' }}
                    </p>
                </div>

                <!-- Admin Chat ID -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        📲 Admin Chat ID
                    </label>
                    <input type="text" 
                           id="adminChatId"
                           class="bg-gray-700 border border-gray-600 text-gray-300 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-3"
                           placeholder="Contoh: 123456789"
                           value="{{ $adminChatId ?? '' }}">
                    <p class="text-xs text-gray-500 mt-1">
                        Admin Chat ID: {{ $adminChatId ? 'SET' : 'NOT SET' }}
                    </p>
                </div>

                <!-- Test Button -->
                <button type="button" 
                        id="testConnection"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    🧪 Test Notifikasi
                </button>
            </div>
        </div>

        <!-- Debug Info -->
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">🔍 Debug Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <strong>Environment:</strong> {{ app()->environment() }}
                </div>
                <div>
                    <strong>User:</strong> {{ auth()->user()->email ?? 'Not authenticated' }}
                </div>
                <div>
                    <strong>Role:</strong> {{ auth()->user()->role->name ?? 'No role' }}
                </div>
                <div>
                    <strong>Telegram Settings Count:</strong> {{ $telegramSettings->count() }}
                </div>
                <div>
                    <strong>View Name:</strong> settings.telegram.debug
                </div>
                <div>
                    <strong>Controller:</strong> TelegramController@index
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('toggleToken').addEventListener('click', function() {
            const tokenInput = document.getElementById('botToken');
            const actualToken = '{{ env("TELEGRAM_BOT_TOKEN", "") }}';
            
            if (tokenInput.type === 'password') {
                tokenInput.type = 'text';
                tokenInput.value = actualToken;
                this.innerHTML = '🙈';
            } else {
                tokenInput.type = 'password';
                tokenInput.value = '{{ str_repeat("•", 20) . substr(env("TELEGRAM_BOT_TOKEN", ""), -6) }}';
                this.innerHTML = '👁️';
            }
        });

        document.getElementById('testConnection').addEventListener('click', function() {
            const adminChatId = document.getElementById('adminChatId').value;
            
            if (!adminChatId) {
                alert('Masukkan Chat ID Admin terlebih dahulu');
                return;
            }

            this.disabled = true;
            this.innerHTML = '⏳ Testing...';

            fetch('/settings/telegram/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    chat_id: adminChatId,
                    message: '🧪 Test koneksi Telegram Bot berhasil!\n\nBot: Dokterku\nWaktu: ' + new Date().toLocaleString('id-ID')
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Test notifikasi berhasil dikirim!');
                } else {
                    alert('❌ Gagal: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('❌ Error: ' + error.message);
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '🧪 Test Notifikasi';
            });
        });
    </script>
</body>
</html>
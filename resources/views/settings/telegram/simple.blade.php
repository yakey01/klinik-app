@extends('layouts.settings')

@section('title', 'Telegram Bot Configuration')

@section('content')
<div class="space-y-6">
    
    <!-- Section 1: Bot Configuration -->
    <div class="bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-100 mb-4">🤖 Section 1: Konfigurasi Bot Telegram</h2>
        
        <form class="space-y-4">
            @csrf
            
            <!-- Token Bot -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    🔐 Token Bot Telegram
                </label>
                <div class="flex">
                    <input type="password" 
                           class="flex-1 bg-gray-700 border border-gray-600 text-gray-300 text-sm rounded-l-lg p-3"
                           value="{{ str_repeat('•', 20) . substr(env('TELEGRAM_BOT_TOKEN', ''), -6) }}"
                           readonly>
                    <button type="button" 
                            class="px-4 py-3 bg-gray-600 border border-gray-600 text-gray-300 text-sm rounded-r-lg hover:bg-gray-500">
                        👁️
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Token tersimpan aman di .env file
                </p>
            </div>

            <!-- Admin Chat ID -->
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">
                    📲 Admin Chat ID
                </label>
                <input type="text" 
                       class="bg-gray-700 border border-gray-600 text-gray-300 text-sm rounded-lg block w-full p-3"
                       placeholder="Contoh: 123456789"
                       value="{{ $adminChatId ?? '' }}">
                <p class="text-xs text-gray-500 mt-1">
                    Chat ID admin untuk fallback notifikasi
                </p>
            </div>

            <!-- Test & Save Buttons -->
            <div class="flex space-x-4">
                <button type="button" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    🧪 Test Notifikasi
                </button>
                
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    💾 Simpan Config
                </button>
            </div>
        </form>
    </div>

    <!-- Section 2: Role-based Notifications -->
    <div class="bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-100 mb-4">👥 Section 2: Notifikasi Role-based</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Role Card Example -->
            <div class="bg-gray-700 rounded-lg p-4 border border-gray-600">
                <div class="flex items-center mb-3">
                    <span class="text-2xl mr-3">👑</span>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-100">Admin</h3>
                        <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">admin</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Chat ID</label>
                        <input type="text" 
                               class="bg-gray-600 border border-gray-500 text-gray-300 text-sm rounded-lg block w-full p-2"
                               placeholder="-1001234567890">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Jenis Notifikasi</label>
                        <div class="space-y-1">
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-500 text-blue-600 mr-2">
                                <span class="text-sm text-gray-300">💰 Pendapatan Baru</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-500 text-blue-600 mr-2">
                                <span class="text-sm text-gray-300">👤 User Baru</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-500 text-blue-600 mr-2">
                                <span class="text-sm text-gray-300">📊 Rekap Harian</span>
                            </label>
                        </div>
                    </div>
                    
                    <label class="flex items-center mt-3 pt-3 border-t border-gray-600">
                        <input type="checkbox" class="rounded border-gray-500 text-green-600 mr-2">
                        <span class="text-sm font-medium text-gray-300">Aktifkan Notifikasi</span>
                    </label>
                </div>
            </div>

            <!-- Additional roles would be here... -->
            <div class="bg-gray-700 rounded-lg p-4 border border-gray-600">
                <div class="text-center text-gray-400 py-8">
                    <p>+ 5 Role Lainnya</p>
                    <p class="text-sm">Manajer, Bendahara, Petugas, Dokter, Paramedis</p>
                </div>
            </div>
            
        </div>

        <div class="mt-6 text-center">
            <button type="button"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                💾 Simpan Konfigurasi Role
            </button>
        </div>
    </div>

    <!-- Debug Info -->
    <div class="bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-100 mb-4">🔍 Debug Information</h2>
        <div class="grid grid-cols-2 gap-4 text-sm text-gray-300">
            <div><strong>Environment:</strong> {{ app()->environment() }}</div>
            <div><strong>User:</strong> {{ auth()->user()->email ?? 'Not logged in' }}</div>
            <div><strong>Role:</strong> {{ auth()->user()->role->name ?? 'No role' }}</div>
            <div><strong>Telegram Settings:</strong> {{ $telegramSettings->count() }}</div>
            <div><strong>Admin Chat ID:</strong> {{ $adminChatId ? 'SET' : 'NOT SET' }}</div>
            <div><strong>Bot Token:</strong> {{ env('TELEGRAM_BOT_TOKEN') ? 'SET' : 'NOT SET' }}</div>
        </div>
    </div>

</div>
@endsection
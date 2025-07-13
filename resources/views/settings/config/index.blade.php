@extends('layouts.settings')

@section('title', 'Konfigurasi Sistem')

@section('content')
<div class="space-y-6">
    <!-- System Branding -->
    <div class="bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100">Branding & Informasi Klinik</h2>
        </div>
        
        <form method="POST" action="{{ route('settings.config.update') }}" enctype="multipart/form-data" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label for="clinic_name" class="block text-sm font-medium text-gray-300">Nama Klinik</label>
                        <input type="text" 
                               id="clinic_name" 
                               name="clinic_name" 
                               value="{{ old('clinic_name', $configs['branding']['clinic_name'] ?? 'Dokterku') }}"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="clinic_motto" class="block text-sm font-medium text-gray-300">Motto Klinik</label>
                        <input type="text" 
                               id="clinic_motto" 
                               name="clinic_motto" 
                               value="{{ old('clinic_motto', $configs['branding']['clinic_motto'] ?? 'SAHABAT MENUJU SEHAT') }}"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="clinic_phone" class="block text-sm font-medium text-gray-300">Telepon Klinik</label>
                        <input type="text" 
                               id="clinic_phone" 
                               name="clinic_phone" 
                               value="{{ old('clinic_phone', $configs['branding']['clinic_phone'] ?? '') }}"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label for="clinic_address" class="block text-sm font-medium text-gray-300">Alamat Klinik</label>
                        <textarea id="clinic_address" 
                                  name="clinic_address" 
                                  rows="3"
                                  class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">{{ old('clinic_address', $configs['branding']['clinic_address'] ?? '') }}</textarea>
                    </div>
                    
                    <div>
                        <label for="logo" class="block text-sm font-medium text-gray-300">Logo Klinik</label>
                        <input type="file" 
                               id="logo" 
                               name="logo" 
                               accept="image/*"
                               class="mt-1 block w-full text-sm text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                        @if(isset($configs['branding']['clinic_logo']))
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $configs['branding']['clinic_logo']) }}" 
                                     alt="Logo Klinik" 
                                     class="h-16 w-auto">
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-700">
                <button type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Simpan Branding
                </button>
            </div>
        </form>
    </div>

    <!-- Work Schedule -->
    <div class="bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100">Jadwal Kerja & Hari Libur</h2>
        </div>
        
        <form method="POST" action="{{ route('settings.config.update') }}" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="work_start_time" class="block text-sm font-medium text-gray-300">Jam Mulai Kerja</label>
                    <input type="time" 
                           id="work_start_time" 
                           name="work_start_time" 
                           value="{{ old('work_start_time', $configs['schedule']['work_start_time'] ?? '06:00') }}"
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="work_end_time" class="block text-sm font-medium text-gray-300">Jam Selesai Kerja</label>
                    <input type="time" 
                           id="work_end_time" 
                           name="work_end_time" 
                           value="{{ old('work_end_time', $configs['schedule']['work_end_time'] ?? '18:00') }}"
                           class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            
            <div class="mt-4">
                <label for="holidays" class="block text-sm font-medium text-gray-300">Hari Libur (satu per baris, format: YYYY-MM-DD)</label>
                <textarea id="holidays" 
                          name="holidays" 
                          rows="6"
                          placeholder="2024-12-25&#10;2024-01-01&#10;2024-08-17"
                          class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">{{ old('holidays', $configs['schedule']['holidays'] ?? '') }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Masukkan tanggal hari libur, satu per baris dalam format YYYY-MM-DD</p>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-700">
                <button type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Simpan Jadwal
                </button>
            </div>
        </form>
    </div>

    <!-- Security Settings -->
    <div class="bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100">Pengaturan Keamanan</h2>
        </div>
        
        <form method="POST" action="{{ route('settings.config.update-security') }}" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label for="password_min_length" class="block text-sm font-medium text-gray-300">Panjang Minimum Password</label>
                        <input type="number" 
                               id="password_min_length" 
                               name="password_min_length" 
                               min="6" 
                               max="20"
                               value="{{ old('password_min_length', $configs['security']['password_min_length'] ?? 8) }}"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="hidden" name="password_require_uppercase" value="0">
                            <input type="checkbox" 
                                   name="password_require_uppercase" 
                                   value="1"
                                   {{ old('password_require_uppercase', $configs['security']['password_require_uppercase'] ?? true) ? 'checked' : '' }}
                                   class="rounded bg-gray-700 border-gray-600 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-300">Wajib Huruf Besar</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="hidden" name="password_require_numbers" value="0">
                            <input type="checkbox" 
                                   name="password_require_numbers" 
                                   value="1"
                                   {{ old('password_require_numbers', $configs['security']['password_require_numbers'] ?? true) ? 'checked' : '' }}
                                   class="rounded bg-gray-700 border-gray-600 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-300">Wajib Angka</span>
                        </label>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label for="session_timeout" class="block text-sm font-medium text-gray-300">Timeout Sesi (menit)</label>
                        <input type="number" 
                               id="session_timeout" 
                               name="session_timeout" 
                               min="15" 
                               max="1440"
                               value="{{ old('session_timeout', $configs['security']['session_timeout'] ?? 120) }}"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="max_login_attempts" class="block text-sm font-medium text-gray-300">Maksimal Percobaan Login</label>
                        <input type="number" 
                               id="max_login_attempts" 
                               name="max_login_attempts" 
                               min="3" 
                               max="10"
                               value="{{ old('max_login_attempts', $configs['security']['max_login_attempts'] ?? 5) }}"
                               class="mt-1 block w-full rounded-md bg-gray-700 border-gray-600 text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>
            
            <div class="mt-6 pt-6 border-t border-gray-700">
                <button type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                    Simpan Pengaturan Keamanan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
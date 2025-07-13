@extends('layouts.settings')

@section('title', 'Backup & Export')

@section('content')
<div class="space-y-6">
    <!-- Export Data -->
    <div class="bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100">Export Data Master</h2>
            <p class="text-sm text-gray-400 mt-1">Export data master sistem untuk backup atau migrasi</p>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Excel Export -->
                <div class="bg-gray-700 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-100">Export Excel</h3>
                    </div>
                    <p class="text-sm text-gray-300 mb-4">
                        Export data dalam format Excel (.xlsx) dengan multiple sheets untuk setiap tabel.
                    </p>
                    <ul class="text-sm text-gray-400 mb-4 space-y-1">
                        <li>• Users & Roles</li>
                        <li>• Jenis Tindakan</li>
                        <li>• Data Pegawai</li>
                        <li>• Data Dokter</li>
                    </ul>
                    <a href="{{ route('settings.backup.export-excel') }}" 
                       class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download Excel
                    </a>
                </div>

                <!-- JSON Export -->
                <div class="bg-gray-700 rounded-lg p-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-100">Export JSON</h3>
                    </div>
                    <p class="text-sm text-gray-300 mb-4">
                        Export data dalam format JSON untuk import ulang atau integrasi dengan sistem lain.
                    </p>
                    <ul class="text-sm text-gray-400 mb-4 space-y-1">
                        <li>• Format standard JSON</li>
                        <li>• Dapat di-import kembali</li>
                        <li>• Include relationships</li>
                        <li>• Timestamp export</li>
                    </ul>
                    <a href="{{ route('settings.backup.export-json') }}" 
                       class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Download JSON
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Data -->
    <div class="bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100">Import Data Master</h2>
            <p class="text-sm text-gray-400 mt-1">Import data master dari file JSON yang sudah di-export sebelumnya</p>
        </div>
        
        <div class="p-6">
            <div class="bg-yellow-900 border border-yellow-600 rounded-md p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-yellow-100">Peringatan!</h3>
                        <div class="mt-2 text-sm text-yellow-200">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Import akan menimpa data yang sudah ada berdasarkan email untuk users</li>
                                <li>Pastikan file JSON memiliki format yang benar</li>
                                <li>Backup data terlebih dahulu sebelum melakukan import</li>
                                <li>Proses ini tidak dapat dibatalkan</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.backup.import-json') }}" enctype="multipart/form-data">
                @csrf
                
                <div class="mb-4">
                    <label for="json_file" class="block text-sm font-medium text-gray-300 mb-2">File JSON</label>
                    <input type="file" 
                           id="json_file" 
                           name="json_file" 
                           accept=".json"
                           required
                           class="block w-full text-sm text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    <p class="text-xs text-gray-400 mt-1">Hanya file JSON yang di-export dari sistem ini</p>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button type="submit" 
                            onclick="return confirm('Yakin ingin melakukan import? Data yang ada akan ditimpa!')"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm font-medium">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Import Data
                    </button>
                    
                    <span class="text-sm text-gray-400">atau</span>
                    
                    <button type="button" 
                            onclick="document.getElementById('json_file').click()"
                            class="px-4 py-2 bg-gray-600 text-gray-300 rounded-md hover:bg-gray-700 text-sm font-medium">
                        Pilih File
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Backup Information -->
    <div class="bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100">Informasi Backup</h2>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-md font-medium text-gray-100 mb-3">Rekomendasi Backup</h3>
                    <ul class="text-sm text-gray-300 space-y-2">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lakukan backup harian untuk data kritikal
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan file backup di lokasi yang aman
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Test restore secara berkala
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Dokumentasikan proses backup
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-md font-medium text-gray-100 mb-3">Data yang Di-backup</h3>
                    <ul class="text-sm text-gray-300 space-y-2">
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Master data users dan roles
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Data jenis tindakan medis
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Database pegawai dan dokter
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            Konfigurasi sistem
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
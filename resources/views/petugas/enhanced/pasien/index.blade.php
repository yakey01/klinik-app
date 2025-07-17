@extends('layouts.enhanced')

@section('title', 'Enhanced Pasien Management')
@section('page-title', 'Manajemen Pasien')
@section('page-description', 'Enhanced Patient Management System')

@section('page-actions')
<div class="flex items-center space-x-4 mt-4">
    <!-- View Toggle -->
    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-1 flex">
        <button @click="viewMode = 'table'" 
                :class="viewMode === 'table' ? 'bg-white dark:bg-gray-700 shadow' : ''"
                class="px-3 py-1 rounded-md text-sm font-medium transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18m-9 8h9"></path>
            </svg>
        </button>
        <button @click="viewMode = 'grid'" 
                :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-700 shadow' : ''"
                class="px-3 py-1 rounded-md text-sm font-medium transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
        </button>
    </div>
    
    <!-- Add Patient Button -->
    <a href="/petugas/enhanced/pasien/create" 
       class="inline-flex items-center px-4 py-2 bg-medical-600 hover:bg-medical-700 text-white font-medium rounded-lg transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Tambah Pasien
    </a>
</div>
@endsection

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="pasienManager()">
    
    <!-- Filters & Search -->
    <div class="medical-card rounded-xl p-6 mb-8 shadow-lg animate-fade-in">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Search -->
            <div class="col-span-full md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Pencarian
                </label>
                <input type="text" 
                       x-model="filters.search" 
                       @input.debounce.300ms="loadPatients()"
                       placeholder="Cari nama, alamat, atau telepon..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
            </div>
            
            <!-- Gender Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jenis Kelamin</label>
                <select x-model="filters.jenis_kelamin" 
                        @change="loadPatients()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                    <option value="">Semua</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </div>
            
            <!-- Age Range -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rentang Umur</label>
                <div class="flex space-x-2">
                    <input type="number" 
                           x-model="filters.age_min" 
                           @change="loadPatients()"
                           placeholder="Min"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                    <input type="number" 
                           x-model="filters.age_max" 
                           @change="loadPatients()"
                           placeholder="Max"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
                </div>
            </div>
        </div>
        
        <!-- Date Range Filter -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Mulai</label>
                <input type="date" 
                       x-model="filters.date_from" 
                       @change="loadPatients()"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Akhir</label>
                <input type="date" 
                       x-model="filters.date_to" 
                       @change="loadPatients()"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-medical-500 focus:border-transparent dark:bg-gray-800 dark:text-white">
            </div>
        </div>
        
        <!-- Active Filters Display -->
        <div x-show="hasActiveFilters()" class="flex flex-wrap gap-2 mb-4">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filter aktif:</span>
            <template x-for="(value, key) in getActiveFilters()" :key="key">
                <span class="filter-badge">
                    <span x-text="getFilterLabel(key, value)"></span>
                    <button @click="removeFilter(key)" class="ml-2 text-red-500 hover:text-red-700">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </span>
            </template>
            <button @click="clearFilters()" class="text-sm text-red-600 hover:text-red-800 font-medium">
                Hapus Semua Filter
            </button>
        </div>
        
        <!-- Actions -->
        <div class="flex flex-wrap gap-4 items-center justify-between">
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Menampilkan <span x-text="pagination.from || 0"></span> - <span x-text="pagination.to || 0"></span> 
                    dari <span x-text="pagination.total || 0"></span> pasien
                </span>
            </div>
            
            <div class="flex items-center space-x-2">
                <!-- Per Page -->
                <select x-model="pagination.per_page" 
                        @change="loadPatients()"
                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg text-sm dark:bg-gray-800 dark:text-white">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                
                <!-- Export -->
                <button @click="exportData()" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export
                </button>
                
                <!-- Bulk Actions -->
                <button x-show="selectedPatients.length > 0" 
                        @click="showBulkDeleteModal = true"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Hapus (<span x-text="selectedPatients.length"></span>)
                </button>
            </div>
        </div>
    </div>

    <!-- Table View -->
    <div x-show="viewMode === 'table'" class="medical-card rounded-xl shadow-lg overflow-hidden animate-fade-in">
        <!-- Loading State -->
        <div x-show="loading" class="p-8">
            <div class="space-y-4">
                <div class="loading-skeleton h-4 rounded w-3/4"></div>
                <div class="loading-skeleton h-4 rounded w-1/2"></div>
                <div class="loading-skeleton h-4 rounded w-5/6"></div>
            </div>
        </div>
        
        <!-- Table Content -->
        <div x-show="!loading" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" 
                                   @change="toggleAllPatients($event.target.checked)"
                                   class="rounded border-gray-300 text-medical-600 focus:ring-medical-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            @click="sortBy('nomor_pasien')">
                            <div class="flex items-center space-x-1">
                                <span>No. Pasien</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            @click="sortBy('nama_pasien')">
                            <div class="flex items-center space-x-1">
                                <span>Nama Pasien</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Jenis Kelamin
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Umur
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Kontak
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            @click="sortBy('created_at')">
                            <div class="flex items-center space-x-1">
                                <span>Terdaftar</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="patient in patients" :key="patient.id">
                        <tr class="table-row-hover transition-all duration-200 cursor-pointer"
                            @click="viewPatient(patient.id)">
                            <td class="px-6 py-4 whitespace-nowrap" @click.stop>
                                <input type="checkbox" 
                                       :value="patient.id"
                                       x-model="selectedPatients"
                                       class="rounded border-gray-300 text-medical-600 focus:ring-medical-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="patient.nomor_pasien"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full flex items-center justify-center text-white font-semibold"
                                             :class="patient.jenis_kelamin === 'L' ? 'bg-blue-500' : 'bg-pink-500'">
                                            <span x-text="patient.nama_pasien.charAt(0).toUpperCase()"></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="patient.nama_pasien"></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="patient.alamat"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="patient.jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'">
                                    <span x-text="patient.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'"></span>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <span x-text="calculateAge(patient.tanggal_lahir)"></span> tahun
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                <span x-text="patient.nomor_telepon || '-'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <span x-text="formatDate(patient.created_at)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" @click.stop>
                                <div class="flex items-center space-x-2">
                                    <a :href="'/petugas/enhanced/pasien/' + patient.id" 
                                       class="text-medical-600 hover:text-medical-900 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <a :href="'/petugas/enhanced/pasien/' + patient.id + '/edit'" 
                                       class="text-blue-600 hover:text-blue-900 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <button @click="deletePatient(patient.id)" 
                                            class="text-red-600 hover:text-red-900 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    
                    <!-- Empty State -->
                    <tr x-show="!loading && patients.length === 0">
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Tidak ada data pasien</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Belum ada pasien yang terdaftar atau sesuai dengan filter pencarian.</p>
                                <a href="/petugas/enhanced/pasien/create" 
                                   class="inline-flex items-center px-4 py-2 bg-medical-600 hover:bg-medical-700 text-white font-medium rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Tambah Pasien Pertama
                                </a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div x-show="!loading && patients.length > 0" class="flex items-center justify-between bg-white dark:bg-gray-900 px-6 py-3 rounded-lg shadow mt-6">
        <div class="flex-1 flex justify-between sm:hidden">
            <button @click="previousPage()" 
                    :disabled="pagination.current_page <= 1"
                    :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white">
                Previous
            </button>
            <button @click="nextPage()" 
                    :disabled="pagination.current_page >= pagination.last_page"
                    :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white">
                Next
            </button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Menampilkan <span class="font-medium" x-text="pagination.from || 0"></span> sampai 
                    <span class="font-medium" x-text="pagination.to || 0"></span> dari 
                    <span class="font-medium" x-text="pagination.total || 0"></span> hasil
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <!-- Previous Button -->
                    <button @click="previousPage()" 
                            :disabled="pagination.current_page <= 1"
                            :class="pagination.current_page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    
                    <!-- Page Numbers -->
                    <template x-for="page in getPageNumbers()" :key="page">
                        <button @click="goToPage(page)" 
                                :class="page === pagination.current_page ? 'bg-medical-50 border-medical-500 text-medical-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                            <span x-text="page"></span>
                        </button>
                    </template>
                    
                    <!-- Next Button -->
                    <button @click="nextPage()" 
                            :disabled="pagination.current_page >= pagination.last_page"
                            :class="pagination.current_page >= pagination.last_page ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Modal -->
<div x-show="showBulkDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" style="display: none;">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Hapus Pasien Terpilih</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus <span class="font-semibold" x-text="selectedPatients.length"></span> pasien yang dipilih? 
                    Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button @click="confirmBulkDelete()" 
                        class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Ya, Hapus Semua
                </button>
                <button @click="showBulkDeleteModal = false" 
                        class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Batal
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .filter-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        background-color: rgba(16, 185, 129, 0.1);
        color: #047857;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .table-row-hover:hover {
        background-color: rgba(16, 185, 129, 0.05);
        transform: scale(1.01);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .loading-skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.6s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endpush

@push('scripts')
<script>
function pasienManager() {
    return {
        // Data
        patients: [],
        selectedPatients: [],
        loading: false,
        viewMode: 'table',
        showBulkDeleteModal: false,
        
        // Filters
        filters: {
            search: '',
            jenis_kelamin: '',
            date_from: '',
            date_to: '',
            age_min: '',
            age_max: '',
            sort_by: 'created_at',
            sort_order: 'desc'
        },
        
        // Pagination
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 25,
            total: 0,
            from: 0,
            to: 0
        },
        
        // Initialize
        init() {
            this.loadPatients();
        },
        
        // Load patients data
        async loadPatients() {
            this.loading = true;
            try {
                const params = {
                    ...this.filters,
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page
                };
                
                // Remove empty filters
                Object.keys(params).forEach(key => {
                    if (params[key] === '' || params[key] === null || params[key] === undefined) {
                        delete params[key];
                    }
                });
                
                const response = await axios.get('/petugas/enhanced/pasien/data', { params });
                
                if (response.data.success) {
                    this.patients = response.data.data;
                    this.pagination = response.data.pagination;
                } else {
                    throw new Error(response.data.message || 'Gagal memuat data');
                }
            } catch (error) {
                console.error('Error loading patients:', error);
                showAlert('error', 'Gagal memuat data pasien: ' + (error.response?.data?.message || error.message));
                this.patients = [];
            } finally {
                this.loading = false;
            }
        },
        
        // Sorting
        sortBy(column) {
            if (this.filters.sort_by === column) {
                this.filters.sort_order = this.filters.sort_order === 'asc' ? 'desc' : 'asc';
            } else {
                this.filters.sort_by = column;
                this.filters.sort_order = 'asc';
            }
            this.pagination.current_page = 1;
            this.loadPatients();
        },
        
        // Pagination
        previousPage() {
            if (this.pagination.current_page > 1) {
                this.pagination.current_page--;
                this.loadPatients();
            }
        },
        
        nextPage() {
            if (this.pagination.current_page < this.pagination.last_page) {
                this.pagination.current_page++;
                this.loadPatients();
            }
        },
        
        goToPage(page) {
            this.pagination.current_page = page;
            this.loadPatients();
        },
        
        getPageNumbers() {
            const pages = [];
            const current = this.pagination.current_page;
            const last = this.pagination.last_page;
            
            // Show max 7 pages
            let start = Math.max(1, current - 3);
            let end = Math.min(last, current + 3);
            
            // Adjust if we're near the beginning or end
            if (end - start < 6) {
                if (start === 1) {
                    end = Math.min(last, start + 6);
                } else if (end === last) {
                    start = Math.max(1, end - 6);
                }
            }
            
            for (let i = start; i <= end; i++) {
                pages.push(i);
            }
            
            return pages;
        },
        
        // Selection
        toggleAllPatients(checked) {
            if (checked) {
                this.selectedPatients = this.patients.map(p => p.id);
            } else {
                this.selectedPatients = [];
            }
        },
        
        // Filters
        hasActiveFilters() {
            return Object.values(this.filters).some(value => value !== '' && value !== 'created_at' && value !== 'desc');
        },
        
        getActiveFilters() {
            const active = {};
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value !== '' && key !== 'sort_by' && key !== 'sort_order') {
                    active[key] = value;
                }
            });
            return active;
        },
        
        getFilterLabel(key, value) {
            const labels = {
                search: `Pencarian: ${value}`,
                jenis_kelamin: `Kelamin: ${value === 'L' ? 'Laki-laki' : 'Perempuan'}`,
                date_from: `Dari: ${value}`,
                date_to: `Sampai: ${value}`,
                age_min: `Umur min: ${value}`,
                age_max: `Umur max: ${value}`
            };
            return labels[key] || `${key}: ${value}`;
        },
        
        removeFilter(key) {
            this.filters[key] = '';
            this.pagination.current_page = 1;
            this.loadPatients();
        },
        
        clearFilters() {
            this.filters = {
                search: '',
                jenis_kelamin: '',
                date_from: '',
                date_to: '',
                age_min: '',
                age_max: '',
                sort_by: 'created_at',
                sort_order: 'desc'
            };
            this.pagination.current_page = 1;
            this.loadPatients();
        },
        
        // Actions
        viewPatient(id) {
            window.location.href = `/petugas/enhanced/pasien/${id}`;
        },
        
        async deletePatient(id) {
            if (!confirm('Apakah Anda yakin ingin menghapus pasien ini?')) {
                return;
            }
            
            try {
                const response = await axios.delete(`/petugas/enhanced/pasien/${id}`);
                
                if (response.data.success) {
                    showAlert('success', response.data.message);
                    this.loadPatients();
                } else {
                    throw new Error(response.data.message);
                }
            } catch (error) {
                showAlert('error', 'Gagal menghapus pasien: ' + (error.response?.data?.message || error.message));
            }
        },
        
        async confirmBulkDelete() {
            try {
                const response = await axios.post('/petugas/enhanced/pasien/bulk-delete', {
                    ids: this.selectedPatients
                });
                
                if (response.data.success) {
                    showAlert('success', response.data.message);
                    this.selectedPatients = [];
                    this.showBulkDeleteModal = false;
                    this.loadPatients();
                } else {
                    throw new Error(response.data.message);
                }
            } catch (error) {
                showAlert('error', 'Gagal menghapus pasien: ' + (error.response?.data?.message || error.message));
            }
        },
        
        async exportData() {
            try {
                const response = await axios.post('/petugas/enhanced/pasien/export', {
                    ...this.filters,
                    format: 'excel'
                });
                
                if (response.data.success) {
                    showAlert('success', `Data berhasil diekspor. ${response.data.record_count} record diunduh.`);
                    // Trigger download
                    window.open(response.data.download_url, '_blank');
                } else {
                    throw new Error(response.data.message);
                }
            } catch (error) {
                showAlert('error', 'Gagal mengekspor data: ' + (error.response?.data?.message || error.message));
            }
        },
        
        // Utilities
        calculateAge(birthDate) {
            if (!birthDate) return '-';
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            return age;
        },
        
        formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>
@endpush
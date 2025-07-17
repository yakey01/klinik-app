@extends('layouts.enhanced')

@section('title', 'Enhanced Tindakan Management')
@section('page-title', 'Timeline Tindakan')
@section('page-description', 'Enhanced Medical Procedures Management with Timeline View')

@section('page-actions')
<div class="flex items-center space-x-4 mt-4">
    <!-- View Toggle -->
    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-1 flex">
        <button @click="viewMode = 'timeline'" 
                :class="viewMode === 'timeline' ? 'bg-white dark:bg-gray-700 shadow' : ''"
                class="px-3 py-1 rounded-md text-sm font-medium transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </button>
        <button @click="viewMode = 'table'" 
                :class="viewMode === 'table' ? 'bg-white dark:bg-gray-700 shadow' : ''"
                class="px-3 py-1 rounded-md text-sm font-medium transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 6h18m-9 8h9"></path>
            </svg>
        </button>
    </div>
    
    <!-- Add Procedure Button -->
    <a href="/petugas/enhanced/tindakan/create" 
       class="inline-flex items-center px-4 py-2 bg-medical-600 hover:bg-medical-700 text-white font-medium rounded-lg transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Input Tindakan
    </a>
</div>
@endsection

@section('content')
<div class="px-4 sm:px-6 lg:px-8" x-data="tindakanManager()">
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="medical-card p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="h-8 w-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Hari Ini</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="stats.today || 0"></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="medical-card p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="h-8 w-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Approved</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="stats.approved || 0"></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="medical-card p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="h-8 w-8 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Pending</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="stats.pending || 0"></dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="medical-card p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="h-8 w-8 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Revenue</dt>
                        <dd class="text-lg font-medium text-gray-900 dark:text-white" x-text="formatCurrency(stats.revenue || 0)"></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="medical-card rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Timeline Tindakan Medis</h2>
            <div class="flex items-center space-x-2">
                <button @click="loadData()" class="btn-outline text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Timeline View -->
        <div x-show="viewMode === 'timeline'" class="space-y-6">
            <template x-for="item in procedures" :key="item.id">
                <div class="relative pl-8">
                    <!-- Timeline line -->
                    <div class="absolute left-3 top-0 h-full w-0.5 bg-gray-200 dark:bg-gray-600"></div>
                    
                    <!-- Timeline dot -->
                    <div class="absolute left-1 top-6 h-4 w-4 rounded-full border-2 border-white dark:border-gray-800 shadow"
                         :class="{
                             'bg-green-400': item.status === 'approved',
                             'bg-yellow-400': item.status === 'pending',
                             'bg-red-400': item.status === 'rejected',
                             'bg-gray-400': !item.status
                         }">
                    </div>
                    
                    <!-- Content -->
                    <div class="ml-6 pb-8">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white" x-text="item.procedure_name"></h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="item.patient_name"></p>
                                    <p class="text-sm text-gray-500 dark:text-gray-500 mt-1" x-text="item.description"></p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-800': item.status === 'approved',
                                              'bg-yellow-100 text-yellow-800': item.status === 'pending',
                                              'bg-red-100 text-red-800': item.status === 'rejected',
                                              'bg-gray-100 text-gray-800': !item.status
                                          }"
                                          x-text="item.status_label"></span>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white mt-1" x-text="formatCurrency(item.tarif)"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500" x-text="formatDate(item.date)"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            
            <!-- Empty State -->
            <div x-show="!loading && procedures.length === 0" class="text-center py-12">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Belum ada tindakan</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Belum ada tindakan medis yang tercatat hari ini.</p>
                <a href="/petugas/enhanced/tindakan/create" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Input Tindakan Pertama
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="space-y-4">
            <template x-for="i in 3" :key="i">
                <div class="animate-pulse">
                    <div class="bg-gray-200 dark:bg-gray-700 h-20 rounded-lg"></div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function tindakanManager() {
    return {
        loading: true,
        viewMode: 'timeline',
        stats: {
            today: 0,
            approved: 0,
            pending: 0,
            revenue: 0
        },
        procedures: [],
        
        init() {
            this.loadData();
        },
        
        async loadData() {
            this.loading = true;
            try {
                // Mock data for demonstration
                setTimeout(() => {
                    this.stats = {
                        today: 8,
                        approved: 12,
                        pending: 3,
                        revenue: 2500000
                    };
                    
                    this.procedures = [
                        {
                            id: 1,
                            procedure_name: 'Medical Checkup',
                            patient_name: 'John Doe',
                            description: 'General health examination',
                            status: 'approved',
                            status_label: 'Approved',
                            tarif: 150000,
                            date: new Date().toISOString()
                        },
                        {
                            id: 2,
                            procedure_name: 'Blood Test',
                            patient_name: 'Jane Smith',
                            description: 'Complete blood count analysis',
                            status: 'pending',
                            status_label: 'Pending',
                            tarif: 75000,
                            date: new Date(Date.now() - 3600000).toISOString()
                        },
                        {
                            id: 3,
                            procedure_name: 'Vaccination',
                            patient_name: 'Bob Johnson',
                            description: 'COVID-19 vaccine dose 2',
                            status: 'approved',
                            status_label: 'Approved',
                            tarif: 85000,
                            date: new Date(Date.now() - 7200000).toISOString()
                        }
                    ];
                    
                    this.loading = false;
                }, 1000);
                
            } catch (error) {
                console.error('Error loading data:', error);
                showAlert('error', 'Gagal memuat data tindakan');
                this.loading = false;
            }
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        },
        
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>
@endpush
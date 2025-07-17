@extends('layouts.enhanced')

@section('title', 'Enhanced Dashboard')
@section('page-title', 'Enhanced Dashboard')
@section('page-description', 'Advanced analytics and management tools for medical staff')

@push('styles')
<style>
/* Minimal Dashboard Design */
.minimal-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.main-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
}

.stat-card {
  background: #ffffff;
  border: 1px solid #e5e5e5;
  border-radius: 0.75rem;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: box-shadow 200ms cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card:hover {
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #737373;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.stat-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: #171717;
  line-height: 1.25;
  margin-bottom: 0.25rem;
}

.stat-change {
  font-size: 0.75rem;
  font-weight: 500;
}

.stat-change.positive {
  color: #10b981;
}

.stat-change.neutral {
  color: #737373;
}

.stat-icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  background: #f5f5f5;
  color: #525252;
}

.content-card {
  background: #ffffff;
  border: 1px solid #e5e5e5;
  border-radius: 0.75rem;
  overflow: hidden;
}

.card-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e5e5;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #171717;
  margin: 0;
}

.view-all-link {
  font-size: 0.875rem;
  color: #10b981;
  text-decoration: none;
  font-weight: 500;
  transition: color 200ms cubic-bezier(0.4, 0, 0.2, 1);
}

.view-all-link:hover {
  color: #059669;
}

.activity-list {
  padding: 1.5rem;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid #f5f5f5;
  transition: color 200ms cubic-bezier(0.4, 0, 0.2, 1), background-color 200ms cubic-bezier(0.4, 0, 0.2, 1), border-color 200ms cubic-bezier(0.4, 0, 0.2, 1);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-item:hover {
  background: #fafafa;
  margin: 0 -1.5rem;
  padding-left: 1.5rem;
  padding-right: 1.5rem;
  border-radius: 0.25rem;
}

.activity-icon {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  background: #f5f5f5;
  color: #525252;
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-description {
  font-size: 0.875rem;
  font-weight: 500;
  color: #171717;
  margin-bottom: 0.25rem;
}

.activity-patient {
  font-size: 0.75rem;
  color: #737373;
}

.activity-time {
  flex-shrink: 0;
  font-size: 0.75rem;
  color: #a3a3a3;
  font-weight: 500;
}

.actions-list {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.action-button {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 0.25rem;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: color 200ms cubic-bezier(0.4, 0, 0.2, 1), background-color 200ms cubic-bezier(0.4, 0, 0.2, 1), border-color 200ms cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid;
}

.action-button.primary {
  background: #10b981;
  color: white;
  border-color: #10b981;
}

.action-button.primary:hover {
  background: #059669;
  border-color: #059669;
}

.action-button.secondary {
  background: #f5f5f5;
  color: #404040;
  border-color: #e5e5e5;
}

.action-button.secondary:hover {
  background: #e5e5e5;
  border-color: #d4d4d4;
}

.action-button.outline {
  background: transparent;
  color: #525252;
  border-color: #d4d4d4;
}

.action-button.outline:hover {
  background: #fafafa;
  color: #404040;
  border-color: #a3a3a3;
}

.monthly-summary {
  padding: 1.5rem;
  border-top: 1px solid #e5e5e5;
  background: #fafafa;
}

.summary-title {
  font-size: 1rem;
  font-weight: 600;
  color: #171717;
  margin: 0 0 1rem 0;
}

.summary-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-label {
  font-size: 0.875rem;
  color: #525252;
}

.summary-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #171717;
}

@media (max-width: 1024px) {
  .main-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .minimal-dashboard {
    padding: 1.5rem 1rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
  }
  
  .main-grid {
    gap: 1rem;
  }
}
</style>
@endpush

@section('page-actions')
<div class="flex items-center space-x-4 mt-4">
    <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-500 dark:text-gray-400">Quick Actions:</span>
        <a href="/petugas/enhanced/pasien/create" class="inline-flex items-center px-3 py-1.5 bg-medical-500 hover:bg-medical-600 text-white text-sm font-medium rounded-md transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Patient
        </a>
        <a href="/petugas/enhanced/tindakan/create" class="inline-flex items-center px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-md transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Procedure
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="minimal-dashboard" x-data="enhancedDashboard()">
    
    <!-- Minimal Stats Overview -->
    <div class="stats-grid mb-12">
        <!-- Today's Patients -->
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-label">Today's Patients</div>
                <div class="stat-value" x-text="stats.today_patients || 0">0</div>
                <div class="stat-change positive" x-text="`+${stats.growth_percentage || 0}% from yesterday`">+0% from yesterday</div>
            </div>
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Today's Procedures -->
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-label">Today's Procedures</div>
                <div class="stat-value" x-text="stats.today_procedures || 0">0</div>
                <div class="stat-change positive" x-text="`${stats.procedure_efficiency || 0}% efficiency`">0% efficiency</div>
            </div>
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>

        <!-- Today's Revenue -->
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-label">Today's Revenue</div>
                <div class="stat-value" x-text="formatCurrency(stats.today_revenue || 0)">Rp 0</div>
                <div class="stat-change positive" x-text="`+${stats.revenue_growth || 0}% vs last week`">+0% vs last week</div>
            </div>
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Pending Items -->
        <div class="stat-card">
            <div class="stat-content">
                <div class="stat-label">Pending Items</div>
                <div class="stat-value" x-text="stats.pending_validations || 0">0</div>
                <div class="stat-change neutral">Needs attention</div>
            </div>
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Main Content - Clean 2-Column Layout -->
    <div class="main-grid">
        
        <!-- Recent Activity -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Recent Activity</h2>
                <a href="/petugas/enhanced/tindakan" class="view-all-link">View All</a>
            </div>
            
            <div class="activity-list">
                <template x-for="activity in recentActivity" :key="activity.id">
                    <div class="activity-item">
                        <div class="activity-icon">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="activity-content">
                            <div class="activity-description" x-text="activity.description">Action</div>
                            <div class="activity-patient" x-text="activity.patient_name">Patient</div>
                        </div>
                        <div class="activity-time" x-text="activity.time">Time</div>
                    </div>
                </template>
                
                <!-- Loading State -->
                <div x-show="loading" class="loading-items">
                    <template x-for="i in 3" :key="i">
                        <div class="loading-item">
                            <div class="loading-icon"></div>
                            <div class="loading-content">
                                <div class="loading-line-long"></div>
                                <div class="loading-line-short"></div>
                            </div>
                            <div class="loading-time"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Quick Actions</h2>
            </div>
            
            <div class="actions-list">
                <a href="/petugas/enhanced/pasien/create" class="action-button primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    <span>Register New Patient</span>
                </a>
                
                <a href="/petugas/enhanced/tindakan/create" class="action-button secondary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Add Medical Procedure</span>
                </a>
                
                <a href="/petugas/enhanced/jumlah-pasien" class="action-button outline">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>View Analytics</span>
                </a>
            </div>

            <!-- Monthly Summary -->
            <div class="monthly-summary">
                <h3 class="summary-title">This Month</h3>
                <div class="summary-items">
                    <div class="summary-item">
                        <span class="summary-label">Total Patients</span>
                        <span class="summary-value" x-text="stats.month_patients || 0">0</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Procedures</span>
                        <span class="summary-value" x-text="stats.month_procedures || 0">0</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Revenue</span>
                        <span class="summary-value" x-text="formatCurrency(stats.month_revenue || 0)">Rp 0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function enhancedDashboard() {
    return {
        loading: true,
        stats: {
            today_patients: 0,
            today_procedures: 0,
            today_revenue: 0,
            pending_validations: 0,
            month_patients: 0,
            month_procedures: 0,
            month_revenue: 0,
            daily_average: 0,
            growth_percentage: 0,
            procedure_efficiency: 0,
            revenue_growth: 0
        },
        recentActivity: [],
        
        init() {
            this.loadDashboardData();
        },
        
        async loadDashboardData() {
            this.loading = true;
            try {
                // Mock data for demonstration
                // In real implementation, this would be API calls
                setTimeout(() => {
                    this.stats = {
                        today_patients: 15,
                        today_procedures: 23,
                        today_revenue: 2500000,
                        pending_validations: 3,
                        month_patients: 450,
                        month_procedures: 680,
                        month_revenue: 75000000,
                        daily_average: 2500000,
                        growth_percentage: 12.5,
                        procedure_efficiency: 87,
                        revenue_growth: 8.3
                    };
                    
                    this.recentActivity = [
                        {
                            id: 1,
                            description: 'Medical checkup completed',
                            patient_name: 'John Doe - #P001',
                            time: '2 minutes ago'
                        },
                        {
                            id: 2,
                            description: 'New patient registered',
                            patient_name: 'Jane Smith - #P002',
                            time: '15 minutes ago'
                        },
                        {
                            id: 3,
                            description: 'Vaccination administered',
                            patient_name: 'Bob Johnson - #P003',
                            time: '1 hour ago'
                        }
                    ];
                    
                    this.loading = false;
                }, 1000);
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
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
        }
    }
}
</script>
@endpush
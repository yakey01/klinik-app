import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { MainApp } from '../components/MainApp';
import { Dashboard } from '../components/Dashboard';

// Type definitions
interface DashboardData {
    user: any;
    stats: any;
    schedule: any;
    jaspel: any;
    chartData: any;
    quickActions: any;
    csrfToken: string;
    apiBaseUrl: string;
    panelUrl: string;
    routes: {
        attendance: string;
        schedule: string;
        tindakan: string;
        jaspel: string;
        profile: string;
    };
}

interface ModernDashboardProps {
    initialData: DashboardData;
}

// Enhanced Dashboard wrapper component
function ModernDashboardWrapper({ initialData }: ModernDashboardProps) {
    const [dashboardData, setDashboardData] = useState(initialData);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [userData, setUserData] = useState<any>(null);

    // Get user data from meta tag
    useEffect(() => {
        const userDataMeta = document.querySelector('meta[name="user-data"]');
        if (userDataMeta) {
            try {
                const data = JSON.parse(userDataMeta.getAttribute('content') || '{}');
                setUserData(data);
            } catch (e) {
                console.error('Error parsing user data:', e);
            }
        }
    }, []);

    // Handle data updates from global events
    useEffect(() => {
        const handleDataUpdate = (event: CustomEvent) => {
            setDashboardData(prev => ({
                ...prev,
                ...event.detail
            }));
        };

        window.addEventListener('dashboard-data-updated', handleDataUpdate as EventListener);

        return () => {
            window.removeEventListener('dashboard-data-updated', handleDataUpdate as EventListener);
        };
    }, []);

    // Handle logout
    const handleLogout = () => {
        if (window.handleLogout) {
            window.handleLogout();
        } else {
            // Fallback logout
            window.location.href = '/logout';
        }
    };

    // Enhanced Dashboard component with Filament integration
    const EnhancedDashboard = () => {
        const [currentTime, setCurrentTime] = useState(new Date());
        const [localDashboardData, setLocalDashboardData] = useState({
            jadwalMendatang: [],
            stats: {
                attendance: { current: 0, target: 90, change: 0 },
                performance: { score: 0, change: 0 },
                jaspel: { thisMonth: 0, lastMonth: 0, change: 0 }
            },
            loading: false
        });

        // Fetch dashboard data with Filament integration
        useEffect(() => {
            const fetchDashboardData = async () => {
                try {
                    setIsLoading(true);
                    
                    // Use Filament/Laravel data first
                    if (dashboardData) {
                        const transformedData = {
                            jadwalMendatang: dashboardData.schedule?.upcoming || [],
                            stats: {
                                attendance: dashboardData.stats?.attendance || { current: 85, target: 90, change: +5 },
                                performance: dashboardData.stats?.performance || { score: 92, change: +3 },
                                jaspel: dashboardData.stats?.jaspel || { thisMonth: 15500000, lastMonth: 14200000, change: +9.2 }
                            },
                            loading: false
                        };

                        setLocalDashboardData(transformedData);
                        setIsLoading(false);
                        return;
                    }

                    // Fallback to API call
                    const response = await fetch(`${dashboardData.apiBaseUrl}/dashboards/paramedis/`, {
                        headers: {
                            'Authorization': `Bearer ${localStorage.getItem('auth_token') || dashboardData.csrfToken}`,
                            'X-CSRF-TOKEN': dashboardData.csrfToken,
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        const data = await response.json();
                        setLocalDashboardData({
                            jadwalMendatang: data.jadwal || [],
                            stats: data.stats || {
                                attendance: { current: 85, target: 90, change: +5 },
                                performance: { score: 92, change: +3 },
                                jaspel: { thisMonth: 15500000, lastMonth: 14200000, change: +9.2 }
                            },
                            loading: false
                        });
                    } else {
                        throw new Error('Failed to fetch dashboard data');
                    }
                } catch (error) {
                    console.error('Failed to fetch dashboard data:', error);
                    setError('Gagal memuat data dashboard');
                    // Use fallback data
                    setLocalDashboardData({
                        jadwalMendatang: [
                            {
                                id: '1',
                                tanggal: '2025-01-18',
                                waktu: '07:00 - 15:00',
                                lokasi: 'IGD',
                                jenis: 'pagi',
                                status: 'scheduled'
                            }
                        ],
                        stats: {
                            attendance: { current: 85, target: 90, change: +5 },
                            performance: { score: 92, change: +3 },
                            jaspel: { thisMonth: 15500000, lastMonth: 14200000, change: +9.2 }
                        },
                        loading: false
                    });
                } finally {
                    setIsLoading(false);
                }
            };

            fetchDashboardData();
        }, [dashboardData]);

        // Update current time
        useEffect(() => {
            const timer = setInterval(() => {
                setCurrentTime(new Date());
            }, 1000);
            return () => clearInterval(timer);
        }, []);

        // Return your existing Dashboard component with integrated data
        return <Dashboard userData={userData} />;
    };

    if (error) {
        return (
            <div className="flex items-center justify-center min-h-64 p-4">
                <div className="text-center">
                    <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg className="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 className="text-lg font-medium text-gray-900 mb-2">Terjadi Kesalahan</h3>
                    <p className="text-gray-600 mb-4">{error}</p>
                    <div className="space-x-3">
                        <button
                            onClick={() => window.location.reload()}
                            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Coba Lagi
                        </button>
                        <button
                            onClick={() => window.location.href = dashboardData.routes?.attendance || '/paramedis'}
                            className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors"
                        >
                            Dashboard Klasik
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    if (isLoading) {
        return (
            <div className="flex items-center justify-center min-h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <span className="ml-3 text-gray-600">Memuat dashboard...</span>
            </div>
        );
    }

    // Use MainApp component for full navigation experience
    return (
        <MainApp onLogout={handleLogout} userData={userData} />
    );
}

// Initialize the React application
function initializeModernDashboard() {
    const container = document.getElementById('modern-dashboard-root');
    if (!container) {
        console.error('Modern dashboard root element not found');
        return;
    }

    // Get data from global window object
    const dashboardData = (window as any).DashboardData;
    if (!dashboardData) {
        console.error('Dashboard data not found');
        return;
    }

    try {
        const root = createRoot(container);
        root.render(<ModernDashboardWrapper initialData={dashboardData} />);
        
        // Hide loading overlay
        const loadingElement = container.querySelector('.modern-dashboard-loading');
        if (loadingElement) {
            loadingElement.remove();
        }

        console.log('Modern dashboard initialized successfully');
    } catch (error) {
        console.error('Failed to initialize modern dashboard:', error);
        
        // Show error boundary
        if (window.ParamedisUtils?.showErrorBoundary) {
            window.ParamedisUtils.showErrorBoundary();
        }
    }
}

// Enhanced error boundary
class ErrorBoundary extends React.Component<
    { children: React.ReactNode },
    { hasError: boolean; error?: Error }
> {
    constructor(props: { children: React.ReactNode }) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError(error: Error) {
        return { hasError: true, error };
    }

    componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
        console.error('React error boundary caught an error:', error, errorInfo);
        
        // Report error to monitoring service if available
        if (window.ParamedisUtils?.showNotification) {
            window.ParamedisUtils.showNotification(
                'Terjadi kesalahan pada dashboard. Silakan refresh halaman.',
                'error',
                5000
            );
        }
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="flex items-center justify-center min-h-64 p-4">
                    <div className="text-center">
                        <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg className="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 className="text-lg font-medium text-gray-900 mb-2">Komponen Bermasalah</h3>
                        <p className="text-gray-600 mb-4">Terjadi kesalahan pada komponen dashboard.</p>
                        <button
                            onClick={() => window.location.reload()}
                            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Refresh Halaman
                        </button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

// Wait for DOM to be ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeModernDashboard);
} else {
    initializeModernDashboard();
}

export { ModernDashboardWrapper, ErrorBoundary };
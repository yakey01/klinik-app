import { useState, useEffect, lazy, Suspense, useMemo } from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Progress } from '../ui/progress';
import { 
  Activity, 
  TrendingUp, 
  TrendingDown, 
  CheckCircle, 
  XCircle, 
  DollarSign, 
  Users, 
  Award, 
  ArrowRight,
  Timer,
  RefreshCw,
  Navigation,
  Clock,
  Calendar,
  MapPin
} from 'lucide-react';

// Lazy load visualization components for better performance
const Sparkline = lazy(() => import('../ui/sparkline').then(module => ({ default: module.Sparkline })));
const CircularProgress = lazy(() => import('../ui/circular-progress').then(module => ({ default: module.CircularProgress })));
const TrendIndicator = lazy(() => import('../ui/trend-indicator').then(module => ({ default: module.TrendIndicator })));


interface PerformanceBadge {
  name: string;
  icon: string;
  color: string;
}

interface PerformanceTrend {
  data: Array<{ month: string; percentage: number }>;
  direction: 'up' | 'down' | 'stable';
  change: number;
}

interface DashboardProps {
  userData?: {
    name: string;
    greeting?: string;
  };
}

export function Dashboard({ userData: propUserData }: DashboardProps) {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [userData, setUserData] = useState<any>(null);
  const [dashboardStats, setDashboardStats] = useState<any>(null);
  const [loadingStats, setLoadingStats] = useState(true);
  const [lastLocationUpdate, setLastLocationUpdate] = useState<string | null>(null);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [pullToRefreshDistance, setPullToRefreshDistance] = useState(0);
  const [isPullingToRefresh, setIsPullingToRefresh] = useState(false);
  const [isCheckingIn, setIsCheckingIn] = useState(false);
  const [checkInStatus, setCheckInStatus] = useState<'idle' | 'locating' | 'submitting' | 'success' | 'error'>('idle');
  const [attendanceData, setAttendanceData] = useState<any>(null);

  // Fetch real dashboard stats from API - moved outside useEffect for scope
  const fetchDashboardStats = async (forceRefresh = false) => {
    try {
      setLoadingStats(true);
      
      // Get CSRF token and API token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const apiToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
      
      const headers: Record<string, string> = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      };
      
      // Add Bearer token if available
      if (apiToken) {
        headers['Authorization'] = `Bearer ${apiToken}`;
      }

      // Use web-based API endpoint (fixes 401/500 errors)
      const url = '/paramedis/web-api/dashboard-stats';
      
      const response = await fetch(url, {
        credentials: 'include',
        headers
      });
      if (response.ok) {
        const result = await response.json();
        // Handle both API formats: test API returns direct data, real API has success/data structure
        const data = result.success ? result.data : result;
        if (data) {
          // Check if work location data has changed
          const newLocationUpdate = data.user?.work_location?.updated_at;
          if (newLocationUpdate && newLocationUpdate !== lastLocationUpdate) {
            console.log('🔄 Work location updated detected:', newLocationUpdate);
            setLastLocationUpdate(newLocationUpdate);
            
            // Show notification about location update
            if (lastLocationUpdate) {
              console.log('📍 Location data refreshed automatically');
            }
          }
          
          // Transform test API data to match expected dashboard format
          const transformedData = {
            stats: {
              jaspel_month: data.jaspel_monthly || data.stats?.jaspel_month || 0,
              jaspel_last_month: data.last_month_total || data.stats?.jaspel_last_month || 0,
              jaspel_growth_percent: data.growth_percent || data.stats?.jaspel_growth_percent || 0,
              patients_today: data.stats?.patients_today || 0,
              tindakan_today: data.stats?.tindakan_today || 0,
              shifts_week: data.stats?.shifts_week || 0
            },
            user: data.user || { name: data.paramedis_name || 'Loading...' },
            performance: data.performance || {}
          };
          
          setDashboardStats(transformedData);
          console.log('✅ Dashboard data loaded successfully');
        }
      } else {
        console.error('Failed to fetch dashboard stats:', response.status, response.statusText);
        if (response.status === 401) {
          console.error('Authentication required. Please ensure you are logged in.');
        }
      }
    } catch (error) {
      console.error('Error fetching dashboard stats:', error);
    } finally {
      setLoadingStats(false);
    }
  };

  // Manual refresh function for user
  const handleManualRefresh = async () => {
    setIsRefreshing(true);
    await fetchDashboardStats(true);
    setTimeout(() => setIsRefreshing(false), 1000);
  };

  // Check-in/Check-out functionality
  const handleCheckInOut = async () => {
    if (isCheckingIn) return;
    
    setIsCheckingIn(true);
    setCheckInStatus('locating');
    
    try {
      // Get current location
      const location = await getCurrentLocation();
      
      setCheckInStatus('submitting');
      
      // Get CSRF token
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      
      // Enhanced logic for multiple shifts
      const attendanceStatus = dashboardStats?.stats?.attendance_today;
      const canCheckIn = attendanceStatus?.can_check_in ?? true;
      const canCheckOut = attendanceStatus?.can_check_out ?? false;
      
      const endpoint = canCheckIn ? '/paramedis/web-api/checkin' : '/paramedis/web-api/checkout';
      const action = canCheckIn ? 'Check In' : 'Check Out';
      
      console.log(`🔄 ${action}: Starting ${action.toLowerCase()}...`);
      console.log('📍 Location:', location);
      
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'include',
        body: JSON.stringify({
          latitude: location.latitude,
          longitude: location.longitude,
          accuracy: location.accuracy || 0,
          timestamp: new Date().toISOString()
        })
      });
      
      const result = await response.json();
      console.log(`📨 ${action} Response:`, result);
      
      if (response.ok && result.success) {
        setCheckInStatus('success');
        setAttendanceData(result.data);
        
        // Refresh dashboard data after successful check-in/out
        setTimeout(() => {
          fetchDashboardStats(true);
          setCheckInStatus('idle');
        }, 2000);
        
        // Show success message
        alert(`✅ ${action} berhasil!`);
      } else {
        throw new Error(result.message || `${action} gagal`);
      }
      
    } catch (error: any) {
      console.error('❌ Check In/Out Error:', error);
      setCheckInStatus('error');
      
      let errorMessage = 'Terjadi kesalahan';
      if (error.message?.includes('location')) {
        errorMessage = 'Gagal mendapatkan lokasi. Pastikan GPS aktif.';
      } else if (error.message?.includes('radius') || error.message?.includes('jarak')) {
        errorMessage = 'Anda berada di luar radius yang diizinkan.';
      } else if (error.message) {
        errorMessage = error.message;
      }
      
      alert(`❌ ${errorMessage}`);
      
      setTimeout(() => {
        setCheckInStatus('idle');
      }, 3000);
    } finally {
      setIsCheckingIn(false);
    }
  };
  
  // Get current location with high accuracy
  const getCurrentLocation = (): Promise<{ latitude: number; longitude: number; accuracy?: number }> => {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Geolocation tidak didukung oleh browser ini'));
        return;
      }
      
      const options = {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0
      };
      
      navigator.geolocation.getCurrentPosition(
        (position) => {
          resolve({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy
          });
        },
        (error) => {
          let message = 'Gagal mendapatkan lokasi';
          switch (error.code) {
            case error.PERMISSION_DENIED:
              message = 'Akses lokasi ditolak. Harap izinkan akses lokasi.';
              break;
            case error.POSITION_UNAVAILABLE:
              message = 'Informasi lokasi tidak tersedia.';
              break;
            case error.TIMEOUT:
              message = 'Timeout saat mendapatkan lokasi.';
              break;
          }
          reject(new Error(message));
        },
        options
      );
    });
  };

  // Pull-to-refresh functionality
  const handleTouchStart = (e: React.TouchEvent) => {
    if (window.scrollY === 0) {
      setIsPullingToRefresh(true);
    }
  };

  const handleTouchMove = (e: React.TouchEvent) => {
    if (isPullingToRefresh && window.scrollY === 0) {
      const touchY = e.touches[0].clientY;
      const distance = Math.max(0, Math.min(touchY - 50, 120));
      setPullToRefreshDistance(distance);
    }
  };

  const handleTouchEnd = () => {
    if (isPullingToRefresh && pullToRefreshDistance > 80) {
      handleManualRefresh();
    }
    setIsPullingToRefresh(false);
    setPullToRefreshDistance(0);
  };


  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    
    // Get user data from meta tag
    const userDataMeta = document.querySelector('meta[name="user-data"]');
    if (userDataMeta) {
      try {
        const data = JSON.parse(userDataMeta.getAttribute('content') || '{}');
        setUserData(data);
      } catch (e) {
        console.error('Failed to parse user data:', e);
      }
    }
    
    fetchDashboardStats();

    // Set up interval to check for location updates every 30 seconds
    const locationCheckInterval = setInterval(() => {
      fetchDashboardStats(false); // Check for updates without forcing refresh
    }, 30000);
    
    return () => {
      clearInterval(timer);
      clearInterval(locationCheckInterval);
    };
  }, []);


  const container = {
    hidden: { opacity: 0 },
    show: {
      opacity: 1,
      transition: {
        staggerChildren: 0.08,
        delayChildren: 0.04
      }
    }
  };

  const item = {
    hidden: { opacity: 0, y: 20 },
    show: { 
      opacity: 1, 
      y: 0,
      transition: {
        type: "spring",
        stiffness: 300,
        damping: 24
      }
    }
  };

  const floatingAnimation = {
    y: [0, -10, 0],
    transition: {
      duration: 3,
      repeat: Infinity,
      ease: "easeInOut"
    }
  };

  // Use real data from API or fallback to dummy data - memoized for performance
  const stats = useMemo(() => {
    if (!dashboardStats) {
      return {
        attendance: {
          current: 0,
          target: 90,
          change: 0
        },
        performance: {
          score: 0,
          change: 0,
          attendance_rank: null,
          total_staff: 0
        },
        jaspel: {
          thisMonth: 0,
          lastMonth: 0,
          change: 0
        }
      };
    }

    return {
      attendance: {
        current: Math.round(dashboardStats.performance?.attendance_rate || 0),
        target: 90,
        change: +5
      },
      performance: {
        score: dashboardStats.performance?.patient_satisfaction || 92,
        change: +3,
        attendance_rank: dashboardStats.performance?.attendance_rank,
        total_staff: dashboardStats.performance?.total_staff
      },
      jaspel: {
        thisMonth: dashboardStats.stats?.jaspel_month || 0,
        lastMonth: dashboardStats.stats?.jaspel_last_month || 0,
        change: dashboardStats.stats?.jaspel_growth_percent || 0
      }
    };
  }, [dashboardStats]);

  // Debug log to see attendance data
  console.log('🎯 Stats object:', {
    attendance_current: stats.attendance.current,
    attendance_rate_raw: dashboardStats?.performance?.attendance_rate,
    performance_data: dashboardStats?.performance,
  });

  return (
    <div className="dashboard-container">
      {/* Skip navigation link for screen readers */}
      <a href="#main-content" className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-white dark:bg-gray-800 p-2 rounded-lg shadow-lg z-50">
        Skip to main content
      </a>
      {/* Pull-to-refresh indicator */}
      {isPullingToRefresh && (
        <motion.div 
          className="fixed top-0 left-1/2 transform -translate-x-1/2 z-50 bg-white dark:bg-gray-800 rounded-full p-3 shadow-lg border"
          style={{ y: pullToRefreshDistance - 40 }}
        >
          <RefreshCw className={`w-5 h-5 text-blue-500 ${pullToRefreshDistance > 80 ? 'animate-spin' : ''}`} />
        </motion.div>
      )}

      <motion.div 
        id="main-content"
        role="main"
        variants={container}
        initial="hidden"
        animate="show"
        className="space-y-4 pb-20"
        onTouchStart={handleTouchStart}
        onTouchMove={handleTouchMove}
        onTouchEnd={handleTouchEnd}
      >
      {/* Minimalist Welcome Card */}
      <motion.div variants={item}>
        <div className="welcome-card-minimal" role="banner" aria-label="Welcome section">
          <div className="welcome-card-content">
            <div className="flex items-start justify-between">
              <div>
                <p className="welcome-greeting">
                  {propUserData?.greeting || userData?.greeting || 'Selamat datang kembali'}
                </p>
                <h1 className="welcome-name">
                  {propUserData?.name || userData?.name || 'Paramedis'}
                </h1>
              </div>
              <div className="text-right">
                <p className="welcome-time">
                  {currentTime.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                  })}
                </p>
                <p className="welcome-date">
                  {currentTime.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long'
                  })}
                </p>
              </div>
            </div>
          </div>
        </div>
      </motion.div>

      {/* Minimalist Work Location Card */}
      {dashboardStats?.user?.work_location && (
        <motion.div variants={item}>
          <div className="card-minimal">
            <div className="card-minimal-header">
              <div className="flex items-center gap-2">
                <div className="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                  <MapPin className="w-4 h-4 text-green-600 dark:text-green-400" />
                </div>
                <h3 className="card-minimal-title">Lokasi Kerja</h3>
              </div>
              <button
                onClick={handleManualRefresh}
                disabled={isRefreshing}
                className="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
              >
                <RefreshCw className={`w-4 h-4 ${isRefreshing ? 'animate-spin' : ''}`} />
              </button>
            </div>
            
            <div className="space-y-3">
              <div>
                <h4 className="font-semibold text-gray-900 dark:text-white">
                  {dashboardStats.user.work_location.name}
                </h4>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  {dashboardStats.user.work_location.address}
                </p>
              </div>
              
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                  <span>Radius: {dashboardStats.user.work_location.radius_meters}m</span>
                  {dashboardStats.user.work_location.is_active && (
                    <Badge className="status-badge success">Aktif</Badge>
                  )}
                </div>
              </div>
            </div>
          </div>
        </motion.div>
      )}


      {/* Minimalist Key Metrics */}
      <motion.div variants={item} className="dashboard-grid">
        {/* Attendance Metric Card */}
        <motion.div 
          className="metric-card"
          role="article"
          aria-labelledby="attendance-label"
          whileHover={{ 
            scale: 1.02,
            boxShadow: "0 20px 40px rgba(0, 0, 0, 0.1)"
          }}
          whileTap={{ scale: 0.98 }}
          transition={{ type: "spring", stiffness: 400, damping: 17 }}
        >
          {loadingStats ? (
            <div className="skeleton-card h-32"></div>
          ) : (
            <>
              <motion.div 
                className="metric-card-icon green"
                whileHover={{ rotate: 360 }}
                transition={{ duration: 0.5 }}
              >
                <CheckCircle className="w-5 h-5" />
              </motion.div>
              <div className="flex items-start justify-between mb-3">
                <div className="flex-1">
                  <p id="attendance-label" className="metric-card-label">Kehadiran Bulan Ini</p>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Target: {stats.attendance.target}% • {new Date().toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}
                  </p>
                </div>
                <Suspense fallback={<div className="w-[50px] h-[50px] bg-gray-200 dark:bg-gray-700 rounded-full animate-pulse" />}>
                  <CircularProgress 
                    value={stats.attendance.current} 
                    size="sm"
                    color="hsl(var(--primary-green))"
                    strokeWidth={3}
                  />
                </Suspense>
              </div>
              
              <div className="space-y-2">
                <div className="flex items-baseline justify-between">
                  <motion.p 
                    className="metric-card-value"
                    initial={{ opacity: 0, scale: 0.5 }}
                    animate={{ opacity: 1, scale: 1 }}
                    transition={{ delay: 0.2, type: "spring", stiffness: 200 }}
                  >
                    {stats.attendance.current}%
                  </motion.p>
                  <Suspense fallback={<div className="h-4 w-16 bg-gray-200 dark:bg-gray-700 rounded animate-pulse" />}>
                    <TrendIndicator 
                      value={stats.attendance.current} 
                      previousValue={stats.attendance.current - stats.attendance.change}
                      format="percentage"
                      size="sm"
                    />
                  </Suspense>
                </div>
                
                <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                  <span>Hari kerja: {Math.floor(new Date().getDate() * 0.7)}/22</span>
                  <span className={`font-medium ${stats.attendance.current >= stats.attendance.target ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'}`}>
                    {stats.attendance.current >= stats.attendance.target ? 'Mencapai target' : `Perlu ${stats.attendance.target - stats.attendance.current}%`}
                  </span>
                </div>
              </div>
            </>
          )}
        </motion.div>

        {/* Jaspel Metric Card */}
        <motion.div 
          className="metric-card"
          whileHover={{ 
            scale: 1.02,
            boxShadow: "0 20px 40px rgba(0, 0, 0, 0.1)"
          }}
          whileTap={{ scale: 0.98 }}
          transition={{ type: "spring", stiffness: 400, damping: 17 }}
        >
          {loadingStats ? (
            <div className="skeleton-card h-32"></div>
          ) : (
            <>
              <motion.div 
                className="metric-card-icon blue"
                whileHover={{ rotate: 360 }}
                transition={{ duration: 0.5 }}
              >
                <DollarSign className="w-5 h-5" />
              </motion.div>
              <div className="flex items-start justify-between mb-3">
                <div className="flex-1">
                  <p className="metric-card-label">Jaspel Bulan Ini</p>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Periode: {new Date().toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}
                  </p>
                </div>
                <Sparkline 
                  data={[2100000, 2300000, 2000000, 2500000, 2800000, stats.jaspel.thisMonth]}
                  color="hsl(var(--primary-blue))"
                  height={35}
                  width={70}
                />
              </div>
              
              <div className="space-y-2">
                <div className="flex items-baseline justify-between">
                  <p className="metric-card-value">
                    Rp {stats.jaspel.thisMonth.toLocaleString('id-ID')}
                  </p>
                  <TrendIndicator 
                    value={stats.jaspel.thisMonth} 
                    previousValue={stats.jaspel.lastMonth}
                    format="currency"
                    size="sm"
                  />
                </div>
                
                <div className="grid grid-cols-2 gap-2 text-xs">
                  <div className="space-y-1">
                    <p className="text-gray-500 dark:text-gray-400">Rata-rata/hari</p>
                    <p className="font-medium text-gray-700 dark:text-gray-300">
                      Rp {Math.round(stats.jaspel.thisMonth / new Date().getDate()).toLocaleString('id-ID')}
                    </p>
                  </div>
                  <div className="space-y-1">
                    <p className="text-gray-500 dark:text-gray-400">Bulan lalu</p>
                    <p className="font-medium text-gray-700 dark:text-gray-300">
                      Rp {(stats.jaspel.lastMonth / 1000).toFixed(0)}K
                    </p>
                  </div>
                </div>
              </div>
            </>
          )}
        </motion.div>

        {/* Performance Metric Card */}
        <motion.div 
          className="metric-card"
          whileHover={{ 
            scale: 1.02,
            boxShadow: "0 20px 40px rgba(0, 0, 0, 0.1)"
          }}
          whileTap={{ scale: 0.98 }}
          transition={{ type: "spring", stiffness: 400, damping: 17 }}
        >
          {loadingStats ? (
            <div className="skeleton-card h-32"></div>
          ) : (
            <>
              <motion.div 
                className="metric-card-icon purple"
                whileHover={{ rotate: 360 }}
                transition={{ duration: 0.5 }}
              >
                <Award className="w-5 h-5" />
              </motion.div>
              <div className="flex items-start justify-between mb-3">
                <div className="flex-1">
                  <p className="metric-card-label">Peringkat Kinerja</p>
                  <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Evaluasi bulanan • {new Date().toLocaleDateString('id-ID', { month: 'long' })}
                  </p>
                </div>
                <CircularProgress 
                  value={stats.performance.score} 
                  size="sm"
                  color="hsl(var(--primary-purple))"
                  strokeWidth={3}
                  showValue={false}
                />
              </div>
              
              <div className="space-y-3">
                <div className="flex items-baseline justify-between">
                  <div>
                    <p className="metric-card-value">#{stats.performance.attendance_rank || '--'}</p>
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                      dari {stats.performance.total_staff || 0} paramedis
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="text-lg font-bold text-purple-600 dark:text-purple-400">
                      {stats.performance.score}%
                    </p>
                    <p className="text-xs text-gray-500 dark:text-gray-400">skor kinerja</p>
                  </div>
                </div>
                
                <div className="space-y-2">
                  <div className="flex items-center justify-between text-xs">
                    <span className="text-gray-500 dark:text-gray-400">Progress evaluasi</span>
                    <span className={`font-medium ${
                      stats.performance.score >= 90 ? 'text-green-600 dark:text-green-400' :
                      stats.performance.score >= 75 ? 'text-blue-600 dark:text-blue-400' :
                      'text-amber-600 dark:text-amber-400'
                    }`}>
                      {stats.performance.score >= 90 ? 'Sangat Baik' :
                       stats.performance.score >= 75 ? 'Baik' : 'Perlu Peningkatan'}
                    </span>
                  </div>
                  <div className="flex items-center gap-2">
                    <div className="flex-1 h-2 bg-purple-100 dark:bg-purple-900/30 rounded-full overflow-hidden">
                      <motion.div 
                        className="h-full bg-gradient-to-r from-purple-400 to-purple-600 dark:from-purple-500 dark:to-purple-700 rounded-full"
                        initial={{ width: 0 }}
                        animate={{ width: `${stats.performance.score}%` }}
                        transition={{ duration: 0.8, ease: "easeOut" }}
                      />
                    </div>
                  </div>
                </div>
              </div>
            </>
          )}
        </motion.div>
      </motion.div>

      {/* Minimalist Jaspel Summary */}
      <motion.div variants={item}>
        <div className="card-minimal">
          <div className="card-minimal-header">
            <h3 className="card-minimal-title">Ringkasan Jaspel</h3>
            <TrendIndicator 
              value={stats.jaspel.thisMonth} 
              previousValue={stats.jaspel.lastMonth}
              format="percentage"
              size="md"
            />
          </div>
          
          <div className="space-y-4">
            {/* Main Amount with Sparkline */}
            <div className="flex items-end justify-between">
              <div className="flex-1">
                <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Bulan Ini</p>
                <p className="text-3xl font-bold text-gray-900 dark:text-white">
                  Rp {stats.jaspel.thisMonth.toLocaleString('id-ID')}
                </p>
              </div>
              <Sparkline 
                data={[1800000, 2100000, 1900000, 2300000, 2500000, stats.jaspel.lastMonth, stats.jaspel.thisMonth]}
                color="hsl(var(--primary-blue))"
                height={50}
                width={120}
                className="ml-4"
              />
            </div>

            {/* Progress with animated bar */}
            {dashboardStats?.period_info && (
              <div>
                <div className="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-2">
                  <span>Progress Bulan</span>
                  <span className="font-medium">{dashboardStats.period_info.month_progress}%</span>
                </div>
                <div className="h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                  <motion.div 
                    className="h-full bg-gradient-to-r from-blue-400 to-blue-600 dark:from-blue-500 dark:to-blue-700 rounded-full"
                    initial={{ width: 0 }}
                    animate={{ width: `${dashboardStats.period_info.month_progress}%` }}
                    transition={{ duration: 1, ease: "easeOut" }}
                  />
                </div>
              </div>
            )}

            {/* Stats Grid with mini charts */}
            <div className="grid grid-cols-2 gap-3">
              <div className="mini-metric">
                <div className="flex items-center justify-between mb-1">
                  <p className="mini-metric-label">Rata-rata Harian</p>
                  <Sparkline 
                    data={[45000, 52000, 48000, 55000, 51000]}
                    color="hsl(var(--primary-green))"
                    height={20}
                    width={40}
                    showArea={false}
                  />
                </div>
                <p className="mini-metric-value">
                  Rp {(dashboardStats?.daily_average || 0).toLocaleString('id-ID')}
                </p>
              </div>
              <div className="mini-metric">
                <div className="flex items-center justify-between mb-1">
                  <p className="mini-metric-label">Bulan Lalu</p>
                  <TrendIndicator 
                    value={stats.jaspel.thisMonth} 
                    previousValue={stats.jaspel.lastMonth}
                    format="percentage"
                    size="sm"
                    showIcon={false}
                  />
                </div>
                <p className="mini-metric-value">
                  Rp {stats.jaspel.lastMonth.toLocaleString('id-ID')}
                </p>
              </div>
            </div>

            {/* Action Button */}
            <Button 
              variant="outline" 
              className="w-full action-button group"
            >
              <div className="action-button-icon group-hover:scale-110 transition-transform">
                <DollarSign className="w-4 h-4" />
              </div>
              <span className="action-button-text">Lihat Detail Jaspel</span>
              <ArrowRight className="w-4 h-4 ml-auto group-hover:translate-x-1 transition-transform" />
            </Button>
          </div>
        </div>
      </motion.div>

      {/* Minimalist Quick Actions */}
      <motion.div variants={item}>
        <div className="card-minimal">
          <div className="card-minimal-header">
            <h3 className="card-minimal-title">Aksi Cepat</h3>
          </div>
          
          <div className="space-y-3">
            {/* Shift Status */}
            {dashboardStats?.stats?.attendance_today && (
              <div className="p-3 bg-blue-50 dark:bg-blue-950/20 rounded-lg border border-blue-100 dark:border-blue-800/30">
                <div className="flex items-center justify-between mb-1">
                  <div className="flex items-center gap-2">
                    <div className={`w-2 h-2 rounded-full ${
                      dashboardStats.stats.attendance_today.status === 'checked_in' ? 'bg-green-500 animate-pulse' :
                      dashboardStats.stats.attendance_today.status === 'all_shifts_completed' ? 'bg-emerald-500' :
                      'bg-blue-500'
                    }`}></div>
                    <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                      {dashboardStats.stats.attendance_today.message}
                    </span>
                  </div>
                  <Badge className="text-xs">
                    {dashboardStats.stats.attendance_today.completed_shifts}/{dashboardStats.stats.attendance_today.total_shifts}
                  </Badge>
                </div>
                {dashboardStats.stats.attendance_today.active_shift && (
                  <p className="text-xs text-gray-600 dark:text-gray-400">
                    Shift: {dashboardStats.stats.attendance_today.active_shift.start_time} - {dashboardStats.stats.attendance_today.active_shift.end_time}
                  </p>
                )}
              </div>
            )}

            {/* Check In/Out Button */}
            <button
              onClick={handleCheckInOut}
              disabled={isCheckingIn}
              aria-label={
                dashboardStats?.stats?.attendance_today?.can_check_in 
                  ? 'Check in to your shift'
                  : dashboardStats?.stats?.attendance_today?.can_check_out 
                  ? 'Check out from your shift'
                  : 'Check in or out'
              }
              aria-busy={isCheckingIn}
              className={`action-button ${
                checkInStatus === 'success' ? 'border-green-200 dark:border-green-700' : 
                checkInStatus === 'error' ? 'border-red-200 dark:border-red-700' : ''
              } ${dashboardStats?.stats?.attendance_today?.can_check_in ? 'pulse' : ''}`}
            >
              <div className={`action-button-icon ${
                checkInStatus === 'success' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' :
                checkInStatus === 'error' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' :
                ''
              }`}>
                {checkInStatus === 'locating' ? (
                  <Navigation className="w-4 h-4 animate-pulse" />
                ) : checkInStatus === 'submitting' ? (
                  <RefreshCw className="w-4 h-4 animate-spin" />
                ) : checkInStatus === 'success' ? (
                  <CheckCircle className="w-4 h-4" />
                ) : checkInStatus === 'error' ? (
                  <XCircle className="w-4 h-4" />
                ) : (
                  <Clock className="w-4 h-4" />
                )}
              </div>
              <span className="action-button-text">
                {checkInStatus === 'locating' && 'Mencari Lokasi...'}
                {checkInStatus === 'submitting' && 'Memproses...'}
                {checkInStatus === 'success' && 'Berhasil!'}
                {checkInStatus === 'error' && 'Gagal'}
                {checkInStatus === 'idle' && (
                  dashboardStats?.stats?.attendance_today?.can_check_in 
                    ? 'Check In Sekarang'
                    : dashboardStats?.stats?.attendance_today?.can_check_out 
                    ? 'Check Out Sekarang'
                    : 'Check In/Out'
                )}
              </span>
            </button>

            {/* Performance Report Button */}
            <button className="action-button">
              <div className="action-button-icon">
                <Activity className="w-4 h-4" />
              </div>
              <span className="action-button-text">Lihat Laporan Kinerja</span>
            </button>
          </div>
        </div>
      </motion.div>

    </motion.div>
    </div>
  );
}
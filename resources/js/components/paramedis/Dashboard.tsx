import { useState, useEffect } from 'react';
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
        staggerChildren: 0.1
      }
    }
  };

  const item = {
    hidden: { opacity: 0, y: 20 },
    show: { opacity: 1, y: 0 }
  };

  // Use real data from API or fallback to dummy data
  const stats = dashboardStats ? {
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
  } : {
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

  // Debug log to see attendance data
  console.log('🎯 Stats object:', {
    attendance_current: stats.attendance.current,
    attendance_rate_raw: dashboardStats?.performance?.attendance_rate,
    performance_data: dashboardStats?.performance,
  });

  return (
    <div className="relative">
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
        variants={container}
        initial="hidden"
        animate="show"
        className="space-y-6 theme-transition pb-20"
        onTouchStart={handleTouchStart}
        onTouchMove={handleTouchMove}
        onTouchEnd={handleTouchEnd}
      >
      {/* Header Welcome */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-br from-blue-600 to-purple-700 dark:from-blue-700 dark:to-purple-800 border-0 relative overflow-hidden">
          <CardContent className="p-6 card-content-mobile relative z-10">
            {/* Background overlay for better text contrast - only for dark mode */}
            <div className="absolute inset-0 bg-gradient-to-r from-black/20 via-black/10 to-black/20"></div>
            
            <div className="text-white relative z-10">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-14 h-14 bg-white/30 dark:bg-white/35 rounded-2xl flex items-center justify-center shadow-2xl backdrop-blur-lg border-2 border-white/30">
                  <Activity className="w-7 h-7 text-white drop-shadow-lg" />
                </div>
                <div className="greeting-panel-enhanced">
                  <h2 className="greeting-text-gradient text-2xl">Dashboard</h2>
                  <p className="greeting-text-gradient text-base">
                    {propUserData?.greeting || userData?.greeting || 'Selamat datang kembali'}, <span className="greeting-text-gradient font-black">{propUserData?.name || userData?.name || 'Paramedis'}</span>
                  </p>
                </div>
              </div>
              
              <div className="flex items-center justify-between">
                <div className="greeting-panel-enhanced">
                  <p className="greeting-text-gradient text-sm">Waktu Sekarang</p>
                  <p className="greeting-text-gradient text-xl font-black">
                    {currentTime.toLocaleTimeString('id-ID', { 
                      hour: '2-digit', 
                      minute: '2-digit' 
                    })}
                  </p>
                </div>
                <div className="text-right greeting-panel-enhanced">
                  <p className="greeting-text-gradient text-sm">
                    {currentTime.toLocaleDateString('id-ID', {
                      weekday: 'long',
                      day: 'numeric',
                      month: 'long'
                    })}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Work Location Card - Only show if work location exists */}
      {dashboardStats?.user?.work_location && (
        <motion.div variants={item}>
          <Card className="shadow-xl border-0 bg-gradient-to-br from-white to-green-50/50 dark:from-gray-900 dark:to-green-950/30 backdrop-blur-sm overflow-hidden card-glass-enhanced card-interactive">
            <CardContent className="p-6 card-content-mobile">
              <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center shadow-mobile-soft">
                    <Navigation className="w-5 h-5 text-green-600 dark:text-green-400" />
                  </div>
                  <div>
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Lokasi Kerja</h3>
                    <p className="text-sm text-gray-600 dark:text-gray-300">Area validasi presensi</p>
                  </div>
                </div>
                <div className="flex items-center gap-2">
                  <Button
                    onClick={handleManualRefresh}
                    disabled={isRefreshing}
                    variant="outline"
                    size="sm"
                    className="h-8 px-3 text-xs hover:bg-green-50 dark:hover:bg-green-950/50 hover:border-green-200 dark:hover:border-green-700"
                  >
                    <RefreshCw className={`w-3 h-3 mr-1 ${isRefreshing ? 'animate-spin' : ''}`} />
                    {isRefreshing ? 'Refresh...' : 'Refresh'}
                  </Button>
                </div>
              </div>

              <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                className="bg-gradient-to-r from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl p-4 text-white relative overflow-hidden"
              >
                {/* Background decoration */}
                <div className="absolute top-0 right-0 w-20 h-20 bg-white/10 dark:bg-white/15 rounded-full -translate-y-10 translate-x-10" />
                
                <div className="relative z-10">
                  <div className="space-y-3">
                    <div className="flex items-center justify-between">
                      <h4 className="font-semibold text-lg">{dashboardStats.user.work_location.name}</h4>
                      <Badge className="bg-white/20 text-white border-white/30 text-xs">
                        {dashboardStats.user.work_location.is_active ? '✅ Aktif' : '❌ Nonaktif'}
                      </Badge>
                    </div>
                    
                    <div className="flex items-center gap-2">
                      <MapPin className="w-4 h-4 text-green-200 dark:text-green-300" />
                      <span className="text-sm text-green-100 dark:text-green-200">
                        {dashboardStats.user.work_location.address}
                      </span>
                    </div>
                    
                    <div className="flex items-center justify-between text-sm">
                      <div className="flex items-center gap-2">
                        <div className="w-2 h-2 bg-green-200 rounded-full"></div>
                        <span className="text-green-100">Radius: {dashboardStats.user.work_location.radius_meters}m</span>
                      </div>
                      <div className="text-green-100 text-xs">
                        {lastLocationUpdate && `Updated: ${new Date(lastLocationUpdate).toLocaleTimeString('id-ID')}`}
                      </div>
                    </div>
                  </div>
                </div>
              </motion.div>
            </CardContent>
          </Card>
        </motion.div>
      )}


      {/* Enhanced Performance Stats with Badges */}
      <motion.div variants={item}>
        <Card className="shadow-xl border-0 bg-white dark:bg-gray-800 backdrop-blur-sm overflow-hidden">
          <CardContent className="p-6 card-content-mobile">
            <div className="flex items-center justify-between mb-6">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                  <Activity className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Performa Anda</h3>
                  <p className="text-sm text-gray-600 dark:text-gray-300">Statistik dan pencapaian</p>
                </div>
              </div>
              
              {/* Performance Badges */}
              {dashboardStats?.performance?.badges && dashboardStats.performance.badges.length > 0 && (
                <div className="flex flex-wrap gap-1">
                  {dashboardStats.performance.badges.slice(0, 2).map((badge: PerformanceBadge, index: number) => (
                    <motion.div
                      key={index}
                      initial={{ scale: 0, opacity: 0 }}
                      animate={{ scale: 1, opacity: 1 }}
                      transition={{ delay: 0.5 + (index * 0.1) }}
                      className={`px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1 ${
                        badge.color === 'gold' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                        badge.color === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                      }`}
                    >
                      <span>{badge.icon}</span>
                      <span className="hidden sm:inline">{badge.name}</span>
                    </motion.div>
                  ))}
                </div>
              )}
            </div>

            <div className="grid grid-cols-2 gap-4 mb-6">
              {/* Attendance Rate */}
              <div className="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-950/20 dark:to-emerald-950/20 rounded-xl p-4 border border-green-100 dark:border-green-800/20">
                <div className="flex items-center gap-2 mb-2">
                  <CheckCircle className="w-4 h-4 text-green-600 dark:text-green-400" />
                  <span className="text-sm text-high-contrast">Kehadiran</span>
                </div>
                <div className="text-2xl font-bold text-green-600 dark:text-green-400 mb-1">
                  {loadingStats ? (
                    <div className="w-12 h-8 bg-green-200 dark:bg-green-800 rounded animate-pulse"></div>
                  ) : (
                    `${stats.attendance.current}%`
                  )}
                </div>
                <div className="progress-clean mb-2">
                  <div className="progress-clean-fill" style={{width: `${stats.attendance.current}%`}}></div>
                </div>
                <div className="flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                  <TrendingUp className="w-3 h-3" />
                  <span className="font-semibold">+{stats.attendance.change}% dari bulan lalu</span>
                </div>
              </div>

              {/* Ranking */}
              <div className="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/20 dark:to-indigo-950/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800/20">
                <div className="flex items-center gap-2 mb-2">
                  <Award className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                  <span className="text-sm text-high-contrast">Peringkat</span>
                </div>
                <div className="text-2xl font-bold text-blue-600 dark:text-blue-400 mb-1">
                  {loadingStats ? (
                    <div className="w-12 h-8 bg-blue-200 dark:bg-blue-800 rounded animate-pulse"></div>
                  ) : (
                    `#${stats.performance.attendance_rank || '--'}`
                  )}
                </div>
                <div className="progress-clean mb-2">
                  <div className="progress-clean-fill" style={{width: `${stats.performance.attendance_rank ? Math.max(0, 100 - ((stats.performance.attendance_rank / stats.performance.total_staff) * 100)) : 0}%`}}></div>
                </div>
                <div className="flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400">
                  <Users className="w-3 h-3" />
                  <span className="font-semibold">dari {loadingStats ? '...' : (stats.performance.total_staff || 0)} paramedis</span>
                </div>
              </div>
            </div>

            {/* Additional Performance Metrics */}
            {dashboardStats?.performance && (
              <div className="grid grid-cols-3 gap-3">
                {/* Punctuality Rate */}
                {dashboardStats.performance.punctuality_rate !== undefined && (
                  <div className="text-center">
                    <div className="text-lg font-bold text-purple-600 dark:text-purple-400">
                      {dashboardStats.performance.punctuality_rate}%
                    </div>
                    <div className="text-xs text-high-contrast">Ketepatan Waktu</div>
                  </div>
                )}
                
                {/* Weekly Attendance */}
                {dashboardStats.performance.week_attendance !== undefined && (
                  <div className="text-center">
                    <div className="text-lg font-bold text-orange-600 dark:text-orange-400">
                      {dashboardStats.performance.week_attendance}
                    </div>
                    <div className="text-xs text-high-contrast">Minggu Ini</div>
                  </div>
                )}
                
                {/* Work Hours */}
                {dashboardStats.performance.total_work_hours !== undefined && (
                  <div className="text-center">
                    <div className="text-lg font-bold text-teal-600 dark:text-teal-400">
                      {dashboardStats.performance.total_work_hours}h
                    </div>
                    <div className="text-xs text-high-contrast">Total Jam</div>
                  </div>
                )}
              </div>
            )}

            {/* Performance Trend */}
            {dashboardStats?.performance?.performance_trend && (
              <motion.div 
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.8 }}
                className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700"
              >
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm text-high-contrast">Tren 3 Bulan</span>
                  <div className={`flex items-center gap-1 text-xs ${
                    dashboardStats.performance.performance_trend.direction === 'up' ? 'text-green-600 dark:text-green-400' :
                    dashboardStats.performance.performance_trend.direction === 'down' ? 'text-red-600 dark:text-red-400' :
                    'text-gray-600 dark:text-gray-400'
                  }`}>
                    {dashboardStats.performance.performance_trend.direction === 'up' ? '↗️' : 
                     dashboardStats.performance.performance_trend.direction === 'down' ? '↘️' : '➡️'}
                    <span>{dashboardStats.performance.performance_trend.change > 0 ? '+' : ''}{dashboardStats.performance.performance_trend.change}%</span>
                  </div>
                </div>
                <div className="flex items-end gap-1 h-8">
                  {dashboardStats.performance.performance_trend.data.map((month: any, index: number) => (
                    <div key={index} className="flex-1 flex flex-col items-center">
                      <div 
                        className="w-full bg-gradient-to-t from-blue-400 to-blue-500 dark:from-blue-500 dark:to-blue-600 rounded-sm"
                        style={{ height: `${Math.max(4, (month.percentage / 100) * 24)}px` }}
                      ></div>
                      <span className="text-xs text-medium-contrast mt-1">{month.month}</span>
                    </div>
                  ))}
                </div>
              </motion.div>
            )}
          </CardContent>
        </Card>
      </motion.div>

      {/* Enhanced Jaspel Summary with Modern Card Design */}
      <motion.div variants={item}>
        <Card className="shadow-xl border-0 card-frosted-clean dark:from-gray-900 dark:to-emerald-950/30 backdrop-blur-sm overflow-hidden card-interactive group">
          <CardContent className="p-6 card-content-mobile">
            <div className="flex items-center justify-between mb-6">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl flex items-center justify-center shadow-mobile-strong group-hover:shadow-emerald-200 dark:group-hover:shadow-emerald-900/50 transition-shadow">
                  <DollarSign className="w-6 h-6 text-white" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Jaspel Bulan Ini</h3>
                  <p className="text-sm text-gray-600 dark:text-gray-300">
                    {dashboardStats?.period_info && (
                      <>Progress {dashboardStats.period_info.month_progress}% bulan ini</>
                    )}
                  </p>
                </div>
              </div>
              
              {/* Amount with Trend */}
              <div className="text-right">
                <motion.p 
                  className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 text-mobile-hero"
                  key={stats.jaspel.thisMonth}
                  initial={{ scale: 0.8, opacity: 0 }}
                  animate={{ scale: 1, opacity: 1 }}
                  transition={{ duration: 0.3 }}
                >
                  Rp {stats.jaspel.thisMonth.toLocaleString('id-ID')}
                </motion.p>
                <div className="flex items-center justify-end gap-1 text-xs mt-1">
                  {stats.jaspel.change > 0 ? (
                    <TrendingUp className="w-3 h-3 text-emerald-500 dark:text-emerald-400" />
                  ) : stats.jaspel.change < 0 ? (
                    <TrendingDown className="w-3 h-3 text-red-500 dark:text-red-400" />
                  ) : null}
                  <span className={`font-semibold ${
                    stats.jaspel.change > 0 ? 'text-emerald-600 dark:text-emerald-400' :
                    stats.jaspel.change < 0 ? 'text-red-600 dark:text-red-400' :
                    'text-gray-600 dark:text-gray-400'
                  }`}>
                    {stats.jaspel.change > 0 ? '+' : ''}{stats.jaspel.change}%
                  </span>
                </div>
              </div>
            </div>
            
            {/* Enhanced Progress Bar */}
            {dashboardStats?.period_info && (
              <motion.div 
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.3 }}
                className="mb-6"
              >
                <div className="flex justify-between text-xs text-high-contrast mb-2">
                  <span className="font-medium">Hari ke-{dashboardStats.period_info.days_passed}</span>
                  <span className="font-medium">{dashboardStats.period_info.days_in_month} hari total</span>
                </div>
                <div className="relative">
                  <div className="progress-clean">
                    <div className="progress-clean-fill" style={{width: `${dashboardStats.period_info.month_progress}%`}}></div>
                  </div>
                  <div className="absolute inset-0 bg-gradient-to-r from-emerald-400/20 to-green-500/20 rounded-full"></div>
                </div>
                <div className="text-center mt-2">
                  <span className="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                    {dashboardStats.period_info.month_progress}% bulan berjalan
                  </span>
                </div>
              </motion.div>
            )}
            
            {/* Statistics Grid with Enhanced Design */}
            <div className="grid grid-cols-2 gap-4 mb-6">
              <motion.div 
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                className="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-950/30 dark:to-green-950/30 rounded-xl p-4 border border-emerald-100/50 dark:border-emerald-800/20 group cursor-pointer"
              >
                <div className="flex items-center gap-2 mb-2">
                  <div className="w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center">
                    <span className="text-xs text-white font-bold">Ⓓ</span>
                  </div>
                  <span className="text-xs text-high-contrast">Rata-rata Harian</span>
                </div>
                <div className="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                  Rp {(dashboardStats?.daily_average || 0).toLocaleString('id-ID')}
                </div>
              </motion.div>
              
              <motion.div 
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                className="bg-gradient-to-br from-green-50 to-teal-50 dark:from-green-950/30 dark:to-teal-950/30 rounded-xl p-4 border border-green-100/50 dark:border-green-800/20 group cursor-pointer"
              >
                <div className="flex items-center gap-2 mb-2">
                  <div className="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center">
                    <span className="text-xs text-white font-bold">Ⓦ</span>
                  </div>
                  <span className="text-xs text-high-contrast">Estimasi Mingguan</span>
                </div>
                <div className="text-lg font-bold text-green-600 dark:text-green-400">
                  Rp {(dashboardStats?.jaspel_weekly || 0).toLocaleString('id-ID')}
                </div>
              </motion.div>
            </div>
            
            {/* Previous Month Comparison */}
            <motion.div 
              whileHover={{ scale: 1.01 }}
              className="bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-950/30 dark:to-green-950/30 rounded-xl p-4 border border-emerald-100/50 dark:border-emerald-800/20"
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Calendar className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                  <span className="text-sm text-high-contrast">Bulan Lalu</span>
                </div>
                <div className="text-right">
                  <div className="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                    Rp {stats.jaspel.lastMonth.toLocaleString('id-ID')}
                  </div>
                  <div className="text-xs text-high-contrast">
                    Perbandingan periode
                  </div>
                </div>
              </div>
            </motion.div>

            {/* View Details Button */}
            <motion.div 
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.5 }}
              className="mt-4"
            >
              <Button 
                variant="outline" 
                className="w-full bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:hover:bg-emerald-950/50 border-emerald-200 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 hover:text-emerald-800 dark:hover:text-emerald-200 group font-semibold"
              >
                <DollarSign className="w-4 h-4 mr-2" />
                <span>Lihat Detail Jaspel</span>
                <ArrowRight className="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
              </Button>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Quick Actions */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white dark:bg-gray-800 backdrop-blur-sm">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
              <Timer className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              Aksi Cepat
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3 card-content-mobile">
            {/* Shift Status Display */}
            {dashboardStats?.stats?.attendance_today && (
              <motion.div
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                className="mb-4 p-3 bg-blue-50 dark:bg-blue-950/30 rounded-lg border border-blue-200 dark:border-blue-800"
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <div className={`w-3 h-3 rounded-full ${
                      dashboardStats.stats.attendance_today.status === 'checked_in' ? 'bg-green-500 animate-pulse' :
                      dashboardStats.stats.attendance_today.status === 'all_shifts_completed' ? 'bg-emerald-500' :
                      'bg-blue-500'
                    }`}></div>
                    <span className="text-sm font-medium text-blue-800 dark:text-blue-200">
                      Shift Status
                    </span>
                  </div>
                  <div className="text-xs text-blue-600 dark:text-blue-300">
                    {dashboardStats.stats.attendance_today.completed_shifts}/{dashboardStats.stats.attendance_today.total_shifts} selesai
                  </div>
                </div>
                <p className="text-xs text-blue-700 dark:text-blue-300 mt-1">
                  {dashboardStats.stats.attendance_today.message}
                </p>
                {dashboardStats.stats.attendance_today.active_shift && (
                  <div className="mt-2 text-xs text-blue-600 dark:text-blue-400">
                    <span className="font-medium">Shift Aktif:</span> {dashboardStats.stats.attendance_today.active_shift.start_time} - {dashboardStats.stats.attendance_today.active_shift.end_time}
                  </div>
                )}
              </motion.div>
            )}

            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button 
                variant="outline" 
                className={`w-full justify-start gap-3 h-12 font-medium btn-mobile-primary focus-enhanced ${
                  checkInStatus === 'success' 
                    ? 'hover:bg-green-50 dark:hover:bg-green-950/50 hover:border-green-200 dark:hover:border-green-700 border-green-200' 
                    : checkInStatus === 'error'
                    ? 'hover:bg-red-50 dark:hover:bg-red-950/50 hover:border-red-200 dark:hover:border-red-700 border-red-200'
                    : 'hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-200 dark:hover:border-blue-700'
                }`}
                onClick={handleCheckInOut}
                disabled={isCheckingIn}
              >
                <div className={`w-8 h-8 rounded-full flex items-center justify-center ${
                  checkInStatus === 'success' 
                    ? 'bg-green-100 dark:bg-green-900/50' 
                    : checkInStatus === 'error'
                    ? 'bg-red-100 dark:bg-red-900/50'
                    : 'bg-blue-100 dark:bg-blue-900/50'
                }`}>
                  {checkInStatus === 'locating' ? (
                    <Navigation className="w-4 h-4 text-blue-600 dark:text-blue-400 animate-pulse" />
                  ) : checkInStatus === 'submitting' ? (
                    <RefreshCw className="w-4 h-4 text-blue-600 dark:text-blue-400 animate-spin" />
                  ) : checkInStatus === 'success' ? (
                    <CheckCircle className="w-4 h-4 text-green-600 dark:text-green-400" />
                  ) : checkInStatus === 'error' ? (
                    <XCircle className="w-4 h-4 text-red-600 dark:text-red-400" />
                  ) : (
                    <Clock className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                  )}
                </div>
                {checkInStatus === 'locating' && 'Mencari Lokasi...'}
                {checkInStatus === 'submitting' && 'Memproses...'}
                {checkInStatus === 'success' && 'Berhasil!'}
                {checkInStatus === 'error' && 'Gagal'}
                {checkInStatus === 'idle' && (
                  dashboardStats?.stats?.attendance_today?.can_check_in 
                    ? (dashboardStats?.stats?.attendance_today?.message || 'Check In Sekarang')
                    : dashboardStats?.stats?.attendance_today?.can_check_out 
                    ? 'Check Out Sekarang'
                    : 'Check In/Out Sekarang'
                )}
              </Button>
            </motion.div>
            

            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-purple-50 dark:hover:bg-purple-950/50 hover:border-purple-200 dark:hover:border-purple-700 font-medium">
                <div className="w-8 h-8 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center">
                  <Activity className="w-4 h-4 text-purple-600 dark:text-purple-400" />
                </div>
                Lihat Laporan Kinerja
              </Button>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>

    </motion.div>
    </div>
  );
}
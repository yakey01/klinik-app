import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Progress } from '../ui/progress';
import { 
  Calendar, 
  Clock, 
  MapPin, 
  Activity, 
  TrendingUp, 
  TrendingDown, 
  CheckCircle, 
  XCircle, 
  DollarSign, 
  Users, 
  Award, 
  ArrowRight,
  AlertCircle,
  Timer
} from 'lucide-react';

interface DashboardProps {
  userData?: {
    name: string;
    greeting?: string;
  };
}

interface JadwalItem {
  id: string;
  tanggal: string;
  waktu: string;
  lokasi: string;
  jenis: 'pagi' | 'siang' | 'malam';
  status: 'scheduled' | 'completed' | 'missed';
  shift_nama?: string;
  status_jaga?: string;
  keterangan?: string;
}


export function Dashboard({ userData }: DashboardProps) {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [jadwalMendatang, setJadwalMendatang] = useState<JadwalItem[]>([]);
  const [nextSchedule, setNextSchedule] = useState<JadwalItem | null>(null);
  const [isLoadingJadwal, setIsLoadingJadwal] = useState(true);
  const [jadwalError, setJadwalError] = useState<string | null>(null);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  // Fetch jadwal jaga data from API
  useEffect(() => {
    const fetchJadwalJaga = async () => {
      try {
        setIsLoadingJadwal(true);
        setJadwalError(null);

        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch('/dokter/api/schedules', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'Accept': 'application/json'
          },
          credentials: 'same-origin'
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const schedules: any[] = await response.json();
        
        // Always clear error if API call succeeds
        setJadwalError(null);
        
        // Transform schedules to our format
        const weeklyJadwal = schedules.map((jadwal: any) => {
          return {
            id: jadwal.id.toString(),
            tanggal: jadwal.tanggal,
            waktu: jadwal.waktu,
            lokasi: jadwal.lokasi,
            jenis: jadwal.jenis as 'pagi' | 'siang' | 'malam',
            status: jadwal.status as 'scheduled' | 'completed' | 'missed',
            shift_nama: jadwal.shift_nama,
            status_jaga: jadwal.status_jaga,
            keterangan: jadwal.keterangan
          };
        });

        setJadwalMendatang(weeklyJadwal);

        // Set next schedule (first upcoming schedule)
        const upcomingSchedules = weeklyJadwal.filter(j => j.status === 'scheduled');
        if (upcomingSchedules.length > 0) {
          setNextSchedule(upcomingSchedules[0]);
        } else {
          // No upcoming schedules
          setNextSchedule(null);
        }
      } catch (error) {
        console.error('Error fetching jadwal jaga:', error);
        setJadwalError('Gagal memuat jadwal jaga');
        
        // Fallback to sample data
        const fallbackJadwal = [
          {
            id: '1',
            tanggal: new Date(Date.now() + 86400000).toISOString().split('T')[0], // Tomorrow
            waktu: '07:00 - 15:00',
            lokasi: 'IGD',
            jenis: 'pagi' as const,
            status: 'scheduled' as const,
            shift_nama: 'Pagi',
            status_jaga: 'Aktif'
          }
        ];
        setJadwalMendatang(fallbackJadwal);
        setNextSchedule(fallbackJadwal[0]);
      } finally {
        setIsLoadingJadwal(false);
      }
    };

    fetchJadwalJaga();
  }, []);


  const getShiftColor = (jenis: string) => {
    switch (jenis) {
      case 'pagi': return 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-white dark:from-yellow-500 dark:to-yellow-600';
      case 'siang': return 'bg-gradient-to-r from-orange-400 to-orange-500 text-white dark:from-orange-500 dark:to-orange-600';
      case 'malam': return 'bg-gradient-to-r from-purple-400 to-purple-500 text-white dark:from-purple-500 dark:to-purple-600';
      default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
    }
  };

  const formatTanggal = (tanggal: string) => {
    return new Date(tanggal).toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long'
    });
  };

  const getShiftIcon = (jenis: string) => {
    switch (jenis) {
      case 'pagi': return '☀️';
      case 'siang': return '🌤️';
      case 'malam': return '🌙';
      default: return '⏰';
    }
  };


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

  // State for dashboard data with API integration
  const [dashboardStats, setDashboardStats] = useState<any>(null);
  const [loadingStats, setLoadingStats] = useState(true);

  // Fetch real dashboard stats from API
  useEffect(() => {
    const fetchDashboardStats = async () => {
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
        
        const response = await fetch('/api/v2/dashboards/dokter/', {
          credentials: 'include',
          headers
        });
        if (response.ok) {
          const result = await response.json();
          if (result.success && result.data) {
            setDashboardStats(result.data);
            console.log('✅ Dokter Dashboard data loaded successfully');
          }
        } else {
          console.error('Failed to fetch dokter dashboard stats:', response.status, response.statusText);
          if (response.status === 401) {
            console.error('Authentication required. Please ensure you are logged in.');
          }
        }
      } catch (error) {
        console.error('Error fetching dokter dashboard stats:', error);
      } finally {
        setLoadingStats(false);
      }
    };

    fetchDashboardStats();
  }, []);

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
      lastMonth: 14200000,
      change: +9.2
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
  console.log('🎯 Dokter Stats object:', {
    attendance_current: stats.attendance.current,
    attendance_rate_raw: dashboardStats?.performance?.attendance_rate,
    performance_data: dashboardStats?.performance,
  });

  return (
    <motion.div 
      variants={container}
      initial="hidden"
      animate="show"
      className="space-y-6 theme-transition"
    >
      {/* Header Welcome */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 border-0 shadow-xl card-enhanced">
          <CardContent className="p-6">
            <div className="text-white">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <Activity className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white text-heading-mobile">Dashboard</h2>
                  <p className="text-blue-100 dark:text-blue-200 text-sm font-medium text-mobile-friendly">
                    {userData?.greeting || 'Selamat datang kembali'}, {userData?.name || 'Dokter'}
                  </p>
                </div>
              </div>
              
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-blue-100 dark:text-blue-200 text-sm font-medium text-mobile-friendly">Waktu Sekarang</p>
                  <p className="text-lg font-semibold text-white text-subheading-mobile">
                    {currentTime.toLocaleTimeString('id-ID', { 
                      hour: '2-digit', 
                      minute: '2-digit' 
                    })}
                  </p>
                </div>
                <div className="text-right">
                  <p className="text-blue-100 dark:text-blue-200 text-sm font-medium text-mobile-friendly">
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

      {/* Next Schedule Card */}
      <motion.div variants={item}>
        <Card className="shadow-xl border-0 bg-gradient-to-br from-white to-blue-50/50 dark:from-gray-900 dark:to-blue-950/30 backdrop-blur-sm overflow-hidden card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                  <Calendar className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-high-contrast text-subheading-mobile">Jadwal Jaga Berikutnya</h3>
                  <p className="text-sm text-medium-contrast font-medium text-mobile-friendly">Shift yang akan datang</p>
                </div>
              </div>
              <Badge variant="outline" className="border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 font-medium">
                Segera
              </Badge>
            </div>

            {isLoadingJadwal ? (
              <div className="bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 rounded-xl p-5 animate-pulse">
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                      <div className="space-y-2">
                        <div className="w-32 h-4 bg-gray-300 dark:bg-gray-600 rounded"></div>
                        <div className="w-20 h-3 bg-gray-300 dark:bg-gray-600 rounded"></div>
                      </div>
                    </div>
                    <div className="w-16 h-6 bg-gray-300 dark:bg-gray-600 rounded"></div>
                  </div>
                  <div className="space-y-2">
                    <div className="w-24 h-3 bg-gray-300 dark:bg-gray-600 rounded"></div>
                    <div className="w-20 h-3 bg-gray-300 dark:bg-gray-600 rounded"></div>
                  </div>
                </div>
              </div>
            ) : jadwalError ? (
              <div className="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-xl p-5">
                <div className="flex items-center gap-3 text-red-700 dark:text-red-400">
                  <AlertCircle className="w-5 h-5" />
                  <div>
                    <p className="font-medium">Gagal memuat jadwal</p>
                    <p className="text-sm text-red-600 dark:text-red-500">{jadwalError}</p>
                  </div>
                </div>
              </div>
            ) : nextSchedule ? (
              <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                transition={{ delay: 0.2 }}
                className="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl p-5 text-white relative overflow-hidden"
              >
                {/* Background decoration */}
                <div className="absolute top-0 right-0 w-24 h-24 bg-white/10 dark:bg-white/15 rounded-full -translate-y-12 translate-x-12" />
                <div className="absolute bottom-0 left-0 w-16 h-16 bg-white/5 dark:bg-white/10 rounded-full translate-y-8 -translate-x-8" />
                
                <div className="relative z-10">
                  <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center gap-3">
                      <div className="text-2xl">{getShiftIcon(nextSchedule.jenis)}</div>
                      <div>
                        <h4 className="text-lg font-semibold text-subheading-mobile">{formatTanggal(nextSchedule.tanggal)}</h4>
                        <p className="text-blue-100 dark:text-blue-200 text-sm font-medium text-mobile-friendly">Shift {nextSchedule.jenis}</p>
                      </div>
                    </div>
                    <Badge className={`${getShiftColor(nextSchedule.jenis)} text-xs font-semibold`}>
                      {nextSchedule.jenis.charAt(0).toUpperCase() + nextSchedule.jenis.slice(1)}
                    </Badge>
                  </div>
                  
                  <div className="space-y-3">
                    <div className="flex items-center gap-3">
                      <Clock className="w-4 h-4 text-blue-200 dark:text-blue-300" />
                      <span className="text-sm font-medium text-blue-100 dark:text-blue-200">
                        {nextSchedule.waktu}
                      </span>
                    </div>
                    <div className="flex items-center gap-3">
                      <MapPin className="w-4 h-4 text-blue-200 dark:text-blue-300" />
                      <span className="text-sm font-medium text-blue-100 dark:text-blue-200">
                        {nextSchedule.lokasi}
                      </span>
                    </div>
                  </div>
                </div>
              </motion.div>
            ) : (
              <div className="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                <div className="text-center text-gray-500 dark:text-gray-400">
                  <Calendar className="w-8 h-8 mx-auto mb-2 opacity-50" />
                  <p className="font-medium">Tidak ada jadwal mendatang</p>
                  <p className="text-sm">Jadwal akan muncul setelah diatur admin</p>
                </div>
              </div>
            )}

            <motion.div 
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.3 }}
              className="mt-4"
            >
              <Button 
                variant="outline" 
                className="w-full hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-200 dark:hover:border-blue-700 group font-medium"
              >
                <span>Lihat Semua Jadwal</span>
                <ArrowRight className="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
              </Button>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Performance Stats */}
      <motion.div variants={item} className="grid grid-cols-2 gap-4">
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardContent className="p-5">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                <CheckCircle className="w-5 h-5 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <h4 className="text-sm font-semibold text-high-contrast text-mobile-friendly">Tingkat Kehadiran</h4>
                <p className="text-2xl font-bold text-green-600 dark:text-green-400 text-heading-mobile">
                  {loadingStats ? (
                    <span className="animate-pulse">⏳</span>
                  ) : (
                    `${stats.attendance.current}%`
                  )}
                </p>
              </div>
            </div>
            <Progress value={stats.attendance.current} className="h-2 mb-2" />
            <div className="flex items-center gap-1 text-xs">
              <TrendingUp className="w-3 h-3 text-green-500 dark:text-green-400" />
              <span className="font-semibold text-green-600 dark:text-green-400">+{stats.attendance.change}%</span>
              <span className="text-medium-contrast font-medium">dari bulan lalu</span>
            </div>
          </CardContent>
        </Card>

        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardContent className="p-5">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                <Award className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <h4 className="text-sm font-semibold text-high-contrast text-mobile-friendly">Urutan Kehadiran</h4>
                <p className="text-2xl font-bold text-blue-600 dark:text-blue-400 text-heading-mobile">
                  {loadingStats ? (
                    <span className="animate-pulse">⏳</span>
                  ) : (
                    `#${stats.performance.attendance_rank || '--'}`
                  )}
                </p>
              </div>
            </div>
            <Progress value={stats.performance.attendance_rank ? Math.max(0, 100 - ((stats.performance.attendance_rank / stats.performance.total_staff) * 100)) : 0} className="h-2 mb-2" />
            <div className="flex items-center gap-1 text-xs">
              <Award className="w-3 h-3 text-blue-500 dark:text-blue-400" />
              <span className="font-semibold text-blue-600 dark:text-blue-400">
                dari {loadingStats ? '...' : (stats.performance.total_staff || 0)}
              </span>
              <span className="text-medium-contrast font-medium">dokter</span>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Jaspel Summary */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/50 rounded-full flex items-center justify-center">
                  <DollarSign className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold text-high-contrast text-subheading-mobile">Jaspel Bulan Ini</h3>
                  <p className="text-sm text-medium-contrast font-medium text-mobile-friendly">Pendapatan layanan medis</p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-2xl font-bold text-emerald-600 dark:text-emerald-400 text-heading-mobile">
                  Rp {stats.jaspel.thisMonth.toLocaleString('id-ID')}
                </p>
                <div className="flex items-center gap-1 text-xs">
                  <TrendingUp className="w-3 h-3 text-emerald-500 dark:text-emerald-400" />
                  <span className="font-semibold text-emerald-600 dark:text-emerald-400">+{stats.jaspel.change}%</span>
                </div>
              </div>
            </div>
            <div className="bg-emerald-50 dark:bg-emerald-950/30 rounded-lg p-4">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-emerald-700 dark:text-emerald-300">Bulan Lalu</span>
                <span className="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                  Rp {stats.jaspel.lastMonth.toLocaleString('id-ID')}
                </span>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Quick Actions */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-high-contrast">
              <Timer className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              Aksi Cepat
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-200 dark:hover:border-blue-700 font-medium">
                <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                  <Clock className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                </div>
                Check In/Out Sekarang
              </Button>
            </motion.div>
            
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 hover:border-emerald-200 dark:hover:border-emerald-700 font-medium">
                <div className="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/50 rounded-full flex items-center justify-center">
                  <Calendar className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                </div>
                Lihat Jadwal Minggu Ini
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

      {/* Upcoming Schedules Preview */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-high-contrast">
              <Calendar className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              Jadwal Minggu Ini
            </CardTitle>
          </CardHeader>
          <CardContent>
            {isLoadingJadwal ? (
              <div className="space-y-3">
                {[1, 2, 3].map((index) => (
                  <div key={index} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg animate-pulse">
                    <div className="flex items-center gap-3">
                      <div className="w-6 h-6 bg-gray-300 dark:bg-gray-600 rounded"></div>
                      <div className="space-y-1">
                        <div className="w-24 h-3 bg-gray-300 dark:bg-gray-600 rounded"></div>
                        <div className="w-20 h-2 bg-gray-300 dark:bg-gray-600 rounded"></div>
                      </div>
                    </div>
                    <div className="text-right space-y-1">
                      <div className="w-16 h-3 bg-gray-300 dark:bg-gray-600 rounded"></div>
                      <div className="w-12 h-4 bg-gray-300 dark:bg-gray-600 rounded"></div>
                    </div>
                  </div>
                ))}
              </div>
            ) : jadwalError ? (
              <div className="text-center py-6">
                <AlertCircle className="w-8 h-8 mx-auto mb-2 text-red-500 dark:text-red-400" />
                <p className="text-sm font-medium text-red-700 dark:text-red-400">Gagal memuat jadwal minggu ini</p>
                <p className="text-xs text-red-600 dark:text-red-500">{jadwalError}</p>
              </div>
            ) : jadwalMendatang.length > 0 ? (
              <div className="space-y-3">
                {jadwalMendatang.slice(0, 3).map((schedule, index) => (
                  <motion.div
                    key={schedule.id}
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: index * 0.1 }}
                    className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/70 transition-colors"
                  >
                    <div className="flex items-center gap-3">
                      <div className="text-lg">{getShiftIcon(schedule.jenis)}</div>
                      <div>
                        <p className="text-sm font-medium text-high-contrast">{formatTanggal(schedule.tanggal)}</p>
                        <p className="text-xs text-muted-foreground font-medium">{schedule.waktu}</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-medium text-high-contrast">{schedule.lokasi}</p>
                      <Badge 
                        variant="outline" 
                        className={`text-xs font-medium ${
                          schedule.jenis === 'pagi' ? 'border-yellow-300 dark:border-yellow-600 text-yellow-700 dark:text-yellow-400' :
                          schedule.jenis === 'siang' ? 'border-orange-300 dark:border-orange-600 text-orange-700 dark:text-orange-400' :
                          'border-purple-300 dark:border-purple-600 text-purple-700 dark:text-purple-400'
                        }`}
                      >
                        {schedule.jenis}
                      </Badge>
                    </div>
                  </motion.div>
                ))}
              </div>
            ) : (
              <div className="text-center py-6">
                <Calendar className="w-8 h-8 mx-auto mb-2 text-gray-400 dark:text-gray-600" />
                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada jadwal minggu ini</p>
                <p className="text-xs text-gray-400 dark:text-gray-500">Hubungi admin untuk pengaturan jadwal</p>
              </div>
            )}
          </CardContent>
        </Card>
      </motion.div>
    </motion.div>
  );
}
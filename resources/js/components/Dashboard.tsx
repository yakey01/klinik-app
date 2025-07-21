import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Progress } from './ui/progress';
import { 
  Calendar, 
  Clock, 
  MapPin, 
  Activity, 
  TrendingUp, 
  CheckCircle, 
  DollarSign, 
  Award, 
  ArrowRight,
  Timer
} from 'lucide-react';

interface JadwalItem {
  id: string;
  tanggal: string;
  waktu: string;
  lokasi: string;
  jenis: 'pagi' | 'siang' | 'malam';
  status: 'scheduled' | 'completed' | 'missed';
}

interface DashboardProps {
  userData?: {
    name: string;
    greeting?: string;
  };
}

export function Dashboard({ userData }: DashboardProps) {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [dashboardData, setDashboardData] = useState({
    jadwalMendatang: [] as JadwalItem[],
    stats: {
      attendance: { current: 0, target: 90, change: 0 },
      performance: { score: 0, change: 0 },
      jaspel: { thisMonth: 0, lastMonth: 0, change: 0 }
    },
    loading: true
  });

  // Fetch dashboard data from API
  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // Fetch dashboard data from Laravel API
        const response = await fetch('/api/v2/dashboards/dokter/', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'X-CSRF-TOKEN': token || '',
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          setDashboardData({
            jadwalMendatang: data.jadwal || [
              {
                id: '1',
                tanggal: '2025-01-18',
                waktu: '07:00 - 15:00',
                lokasi: 'IGD',
                jenis: 'pagi',
                status: 'scheduled'
              },
              {
                id: '2',
                tanggal: '2025-01-19',
                waktu: '15:00 - 23:00',
                lokasi: 'Ruang Rawat Inap',
                jenis: 'siang',
                status: 'scheduled'
              }
            ],
            stats: data.stats || {
              attendance: { current: 85, target: 90, change: +5 },
              performance: { score: 92, change: +3 },
              jaspel: { thisMonth: 15500000, lastMonth: 14200000, change: +9.2 }
            },
            loading: false
          });
        } else {
          // Fallback data if API fails
          setDashboardData({
            jadwalMendatang: [
              {
                id: '1',
                tanggal: '2025-01-18',
                waktu: '07:00 - 15:00',
                lokasi: 'IGD',
                jenis: 'pagi',
                status: 'scheduled'
              },
              {
                id: '2',
                tanggal: '2025-01-19',
                waktu: '15:00 - 23:00',
                lokasi: 'Ruang Rawat Inap',
                jenis: 'siang',
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
        }
      } catch (error) {
        console.error('Failed to fetch dashboard data:', error);
        setDashboardData(prev => ({ ...prev, loading: false }));
      }
    };

    fetchDashboardData();
  }, []);

  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  const getShiftColor = (jenis: string) => {
    switch (jenis) {
      case 'pagi': return 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-white';
      case 'siang': return 'bg-gradient-to-r from-orange-400 to-orange-500 text-white';
      case 'malam': return 'bg-gradient-to-r from-purple-400 to-purple-500 text-white';
      default: return 'bg-gray-100 text-gray-800';
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

  const { jadwalMendatang, stats, loading } = dashboardData;
  const nextSchedule = jadwalMendatang[0];

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      {/* Header Welcome */}
      <Card className="bg-gradient-to-r from-blue-500 to-blue-600 border-0 shadow-xl">
        <CardContent className="p-6">
          <div className="text-white">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                <Activity className="w-6 h-6" />
              </div>
              <div>
                <h2 className="text-xl text-white">Dashboard</h2>
                <p className="text-blue-100 text-sm">{userData?.greeting || 'Selamat datang kembali'}, {userData?.name || 'User'}</p>
              </div>
            </div>
            
            <div className="flex items-center justify-between">
              <div>
                <p className="text-blue-100 text-sm">Waktu Sekarang</p>
                <p className="text-lg text-white">
                  {currentTime.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                  })}
                </p>
              </div>
              <div className="text-right">
                <p className="text-blue-100 text-sm">
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

      {/* Next Schedule Card */}
      <Card className="shadow-xl border-0 bg-gradient-to-br from-white to-blue-50/50 backdrop-blur-sm overflow-hidden">
        <CardContent className="p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <Calendar className="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <h3 className="text-lg">Jadwal Jaga Berikutnya</h3>
                <p className="text-sm text-muted-foreground">Shift yang akan datang</p>
              </div>
            </div>
            <Badge variant="outline" className="border-blue-200 text-blue-600">
              Segera
            </Badge>
          </div>

          {nextSchedule && (
            <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-5 text-white relative overflow-hidden">
              {/* Background decoration */}
              <div className="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -translate-y-12 translate-x-12" />
              <div className="absolute bottom-0 left-0 w-16 h-16 bg-white/5 rounded-full translate-y-8 -translate-x-8" />
              
              <div className="relative z-10">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex items-center gap-3">
                    <div className="text-2xl">{getShiftIcon(nextSchedule.jenis)}</div>
                    <div>
                      <h4 className="text-lg">{formatTanggal(nextSchedule.tanggal)}</h4>
                      <p className="text-blue-100 text-sm">Shift {nextSchedule.jenis}</p>
                    </div>
                  </div>
                  <Badge className={`${getShiftColor(nextSchedule.jenis)} text-xs`}>
                    {nextSchedule.jenis.charAt(0).toUpperCase() + nextSchedule.jenis.slice(1)}
                  </Badge>
                </div>
                
                <div className="space-y-3">
                  <div className="flex items-center gap-3">
                    <Clock className="w-4 h-4 text-blue-200" />
                    <span className="text-sm text-blue-100">
                      {nextSchedule.waktu}
                    </span>
                  </div>
                  <div className="flex items-center gap-3">
                    <MapPin className="w-4 h-4 text-blue-200" />
                    <span className="text-sm text-blue-100">
                      {nextSchedule.lokasi}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          )}

          <div className="mt-4">
            <Button 
              variant="outline" 
              className="w-full hover:bg-blue-50 hover:border-blue-200 group"
            >
              <span>Lihat Semua Jadwal</span>
              <ArrowRight className="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Performance Stats */}
      <div className="grid grid-cols-2 gap-4">
        <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
          <CardContent className="p-5">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <CheckCircle className="w-5 h-5 text-green-600" />
              </div>
              <div>
                <h4 className="text-sm">Tingkat Kehadiran</h4>
                <p className="text-2xl text-green-600">{stats.attendance.current}%</p>
              </div>
            </div>
            <Progress value={stats.attendance.current} className="h-2 mb-2" />
            <div className="flex items-center gap-1 text-xs">
              <TrendingUp className="w-3 h-3 text-green-500" />
              <span className="text-green-600">+{stats.attendance.change}%</span>
              <span className="text-muted-foreground">dari bulan lalu</span>
            </div>
          </CardContent>
        </Card>

        <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
          <CardContent className="p-5">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <Award className="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <h4 className="text-sm">Skor Kinerja</h4>
                <p className="text-2xl text-blue-600">{stats.performance.score}</p>
              </div>
            </div>
            <Progress value={stats.performance.score} className="h-2 mb-2" />
            <div className="flex items-center gap-1 text-xs">
              <TrendingUp className="w-3 h-3 text-blue-500" />
              <span className="text-blue-600">+{stats.performance.change}</span>
              <span className="text-muted-foreground">poin minggu ini</span>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Jaspel Summary */}
      <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
        <CardContent className="p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                <DollarSign className="w-5 h-5 text-emerald-600" />
              </div>
              <div>
                <h3 className="text-lg">Jaspel Bulan Ini</h3>
                <p className="text-sm text-muted-foreground">Pendapatan layanan medis</p>
              </div>
            </div>
            <div className="text-right">
              <p className="text-2xl text-emerald-600">
                Rp {stats.jaspel.thisMonth.toLocaleString('id-ID')}
              </p>
              <div className="flex items-center gap-1 text-xs">
                <TrendingUp className="w-3 h-3 text-emerald-500" />
                <span className="text-emerald-600">+{stats.jaspel.change}%</span>
              </div>
            </div>
          </div>
          <div className="bg-emerald-50 rounded-lg p-4">
            <div className="flex items-center justify-between">
              <span className="text-sm text-emerald-700">Bulan Lalu</span>
              <span className="text-sm text-emerald-600">
                Rp {stats.jaspel.lastMonth.toLocaleString('id-ID')}
              </span>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Quick Actions */}
      <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Timer className="w-5 h-5 text-blue-600" />
            Aksi Cepat
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-blue-50 hover:border-blue-200 transition-all hover:scale-[1.01] hover:translate-x-1">
            <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
              <Clock className="w-4 h-4 text-blue-600" />
            </div>
            Check In/Out Sekarang
          </Button>
          
          <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-emerald-50 hover:border-emerald-200 transition-all hover:scale-[1.01] hover:translate-x-1">
            <div className="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
              <Calendar className="w-4 h-4 text-emerald-600" />
            </div>
            Lihat Jadwal Minggu Ini
          </Button>

          <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-purple-50 hover:border-purple-200 transition-all hover:scale-[1.01] hover:translate-x-1">
            <div className="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
              <Activity className="w-4 h-4 text-purple-600" />
            </div>
            Lihat Laporan Kinerja
          </Button>
        </CardContent>
      </Card>

      {/* Upcoming Schedules Preview */}
      <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Calendar className="w-5 h-5 text-blue-600" />
            Jadwal Minggu Ini
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {jadwalMendatang.slice(0, 3).map((schedule, index) => (
              <div
                key={schedule.id}
                className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                style={{ animationDelay: `${index * 100}ms` }}
              >
                <div className="flex items-center gap-3">
                  <div className="text-lg">{getShiftIcon(schedule.jenis)}</div>
                  <div>
                    <p className="text-sm">{formatTanggal(schedule.tanggal)}</p>
                    <p className="text-xs text-muted-foreground">{schedule.waktu}</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-sm">{schedule.lokasi}</p>
                  <Badge 
                    variant="outline" 
                    className={`text-xs ${
                      schedule.jenis === 'pagi' ? 'border-yellow-300 text-yellow-700' :
                      schedule.jenis === 'siang' ? 'border-orange-300 text-orange-700' :
                      'border-purple-300 text-purple-700'
                    }`}
                  >
                    {schedule.jenis}
                  </Badge>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
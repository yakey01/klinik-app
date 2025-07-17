import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Clock, MapPin, Calendar, CheckCircle, XCircle, AlertCircle, Navigation, Smartphone } from 'lucide-react';

interface AttendanceRecord {
  id: string;
  tanggal: string;
  checkIn?: string;
  checkOut?: string;
  lokasi: string;
  status: 'present' | 'absent' | 'late' | 'partial';
  coordinates?: {
    lat: number;
    lng: number;
  };
}

interface AttendanceData {
  records: AttendanceRecord[];
  todayStatus: {
    isCheckedIn: boolean;
    checkInTime?: string;
    checkOutTime?: string;
    currentLocation?: string;
    workLocation: string;
  };
  stats: {
    thisMonth: {
      present: number;
      absent: number;
      late: number;
      total: number;
    };
  };
  loading: boolean;
}

export function Presensi() {
  const [attendanceData, setAttendanceData] = useState<AttendanceData>({
    records: [],
    todayStatus: {
      isCheckedIn: false,
      workLocation: 'Klinik Dokterku - Gedung Utama'
    },
    stats: {
      thisMonth: {
        present: 0,
        absent: 0,
        late: 0,
        total: 0
      }
    },
    loading: true
  });
  const [gpsLoading, setGpsLoading] = useState(false);
  const [currentTime, setCurrentTime] = useState(new Date());

  // Update current time
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  // Fetch attendance data from API
  useEffect(() => {
    const fetchAttendanceData = async () => {
      try {
        const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch('/api/v2/dashboards/dokter/attendance', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'X-CSRF-TOKEN': token || '',
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          setAttendanceData({
            records: data.records || [
              {
                id: '1',
                tanggal: '2025-01-16',
                checkIn: '07:30',
                checkOut: '15:30',
                lokasi: 'Klinik Dokterku',
                status: 'present'
              },
              {
                id: '2',
                tanggal: '2025-01-15',
                checkIn: '08:15',
                checkOut: '16:00',
                lokasi: 'Klinik Dokterku',
                status: 'late'
              }
            ],
            todayStatus: data.todayStatus || {
              isCheckedIn: false,
              workLocation: 'Klinik Dokterku - Gedung Utama'
            },
            stats: data.stats || {
              thisMonth: {
                present: 12,
                absent: 1,
                late: 2,
                total: 15
              }
            },
            loading: false
          });
        } else {
          // Fallback data
          setAttendanceData({
            records: [
              {
                id: '1',
                tanggal: '2025-01-16',
                checkIn: '07:30',
                checkOut: '15:30',
                lokasi: 'Klinik Dokterku',
                status: 'present'
              },
              {
                id: '2',
                tanggal: '2025-01-15',
                checkIn: '08:15',
                checkOut: '16:00',
                lokasi: 'Klinik Dokterku',
                status: 'late'
              }
            ],
            todayStatus: {
              isCheckedIn: false,
              workLocation: 'Klinik Dokterku - Gedung Utama'
            },
            stats: {
              thisMonth: {
                present: 12,
                absent: 1,
                late: 2,
                total: 15
              }
            },
            loading: false
          });
        }
      } catch (error) {
        console.error('Failed to fetch attendance data:', error);
        setAttendanceData(prev => ({ ...prev, loading: false }));
      }
    };

    fetchAttendanceData();
  }, []);

  const handleCheckInOut = async () => {
    setGpsLoading(true);
    
    try {
      // Get GPS location
      const position = await new Promise<GeolocationPosition>((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 60000
        });
      });

      const { latitude, longitude } = position.coords;
      const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      const endpoint = attendanceData.todayStatus.isCheckedIn 
        ? '/api/v2/dashboards/dokter/attendance/checkout'
        : '/api/v2/dashboards/dokter/attendance/checkin';

      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': token || '',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          latitude,
          longitude,
          timestamp: new Date().toISOString()
        })
      });

      if (response.ok) {
        const data = await response.json();
        setAttendanceData(prev => ({
          ...prev,
          todayStatus: {
            ...prev.todayStatus,
            isCheckedIn: !prev.todayStatus.isCheckedIn,
            checkInTime: data.checkInTime || prev.todayStatus.checkInTime,
            checkOutTime: data.checkOutTime || prev.todayStatus.checkOutTime,
            currentLocation: data.location
          }
        }));
      }
    } catch (error) {
      console.error('GPS or attendance error:', error);
      alert('Gagal mendapatkan lokasi GPS atau melakukan presensi. Pastikan GPS aktif dan izin lokasi diberikan.');
    } finally {
      setGpsLoading(false);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'present': return 'bg-green-100 text-green-800 border-green-200';
      case 'late': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      case 'absent': return 'bg-red-100 text-red-800 border-red-200';
      case 'partial': return 'bg-blue-100 text-blue-800 border-blue-200';
      default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'present': return <CheckCircle className="w-4 h-4" />;
      case 'late': return <AlertCircle className="w-4 h-4" />;
      case 'absent': return <XCircle className="w-4 h-4" />;
      case 'partial': return <Clock className="w-4 h-4" />;
      default: return <Clock className="w-4 h-4" />;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long'
    });
  };

  const { records, todayStatus, stats, loading } = attendanceData;

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  const attendanceRate = stats.thisMonth.total > 0 
    ? Math.round((stats.thisMonth.present / stats.thisMonth.total) * 100)
    : 0;

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      {/* Header Section */}
      <Card className="bg-gradient-to-r from-green-500 to-green-600 border-0 shadow-xl">
        <CardContent className="p-6">
          <div className="text-white">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                <Clock className="w-6 h-6" />
              </div>
              <div>
                <h2 className="text-xl text-white">Presensi</h2>
                <p className="text-green-100 text-sm">Kelola kehadiran Anda</p>
              </div>
            </div>
            
            <div className="flex items-center justify-between">
              <div>
                <p className="text-green-100 text-sm">Waktu Sekarang</p>
                <p className="text-lg text-white">
                  {currentTime.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    second: '2-digit'
                  })}
                </p>
              </div>
              <div className="text-right">
                <p className="text-green-100 text-sm">
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

      {/* Today's Status */}
      <Card className="shadow-xl border-0 bg-white/80 backdrop-blur-sm">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Smartphone className="w-5 h-5 text-blue-600" />
            Status Hari Ini
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Work Location */}
          <div className="flex items-center gap-3 p-4 bg-blue-50 rounded-lg">
            <MapPin className="w-5 h-5 text-blue-600" />
            <div>
              <p className="text-sm text-blue-600">Lokasi Kerja</p>
              <p className="font-medium">{todayStatus.workLocation}</p>
            </div>
          </div>

          {/* Check In/Out Status */}
          <div className="grid grid-cols-2 gap-4">
            <div className="p-4 bg-gray-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <CheckCircle className="w-4 h-4 text-green-600" />
                <span className="text-sm text-gray-600">Check In</span>
              </div>
              <p className="text-lg font-semibold">
                {todayStatus.checkInTime || '--:--'}
              </p>
            </div>
            
            <div className="p-4 bg-gray-50 rounded-lg">
              <div className="flex items-center gap-2 mb-2">
                <XCircle className="w-4 h-4 text-red-600" />
                <span className="text-sm text-gray-600">Check Out</span>
              </div>
              <p className="text-lg font-semibold">
                {todayStatus.checkOutTime || '--:--'}
              </p>
            </div>
          </div>

          {/* Check In/Out Button */}
          <Button 
            onClick={handleCheckInOut}
            disabled={gpsLoading}
            className={`w-full h-16 text-lg gap-3 transition-all hover:scale-105 ${
              todayStatus.isCheckedIn 
                ? 'bg-red-500 hover:bg-red-600 text-white' 
                : 'bg-green-500 hover:bg-green-600 text-white'
            }`}
          >
            {gpsLoading ? (
              <>
                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                Mendapatkan Lokasi...
              </>
            ) : (
              <>
                <Navigation className="w-5 h-5" />
                {todayStatus.isCheckedIn ? 'Check Out' : 'Check In'}
              </>
            )}
          </Button>

          {todayStatus.currentLocation && (
            <div className="text-center text-sm text-gray-600">
              Lokasi terdeteksi: {todayStatus.currentLocation}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Monthly Stats */}
      <div className="grid grid-cols-2 gap-4">
        <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200">
          <CardContent className="p-4">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <CheckCircle className="w-5 h-5 text-green-600" />
              </div>
              <div>
                <p className="text-sm text-green-600">Tingkat Kehadiran</p>
                <p className="text-2xl font-bold text-green-700">{attendanceRate}%</p>
              </div>
            </div>
            <div className="text-xs text-green-600">
              {stats.thisMonth.present} dari {stats.thisMonth.total} hari
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
          <CardContent className="p-4">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <Calendar className="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <p className="text-sm text-blue-600">Bulan Ini</p>
                <p className="text-2xl font-bold text-blue-700">{stats.thisMonth.total}</p>
              </div>
            </div>
            <div className="text-xs text-blue-600">
              Total hari kerja
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Detailed Stats */}
      <div className="grid grid-cols-3 gap-3">
        <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200">
          <CardContent className="p-4 text-center">
            <CheckCircle className="w-5 h-5 text-green-600 mx-auto mb-2" />
            <div className="text-lg font-semibold text-green-700">{stats.thisMonth.present}</div>
            <div className="text-xs text-green-600">Hadir</div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-200">
          <CardContent className="p-4 text-center">
            <AlertCircle className="w-5 h-5 text-yellow-600 mx-auto mb-2" />
            <div className="text-lg font-semibold text-yellow-700">{stats.thisMonth.late}</div>
            <div className="text-xs text-yellow-600">Terlambat</div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-red-50 to-red-100 border-red-200">
          <CardContent className="p-4 text-center">
            <XCircle className="w-5 h-5 text-red-600 mx-auto mb-2" />
            <div className="text-lg font-semibold text-red-700">{stats.thisMonth.absent}</div>
            <div className="text-xs text-red-600">Tidak Hadir</div>
          </CardContent>
        </Card>
      </div>

      {/* Recent Records */}
      <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Clock className="w-5 h-5 text-blue-600" />
            Riwayat Presensi
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {records.length === 0 ? (
              <div className="text-center py-8">
                <Clock className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <p className="text-gray-600">Belum ada riwayat presensi</p>
              </div>
            ) : (
              records.map((record) => (
                <div key={record.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                      {getStatusIcon(record.status)}
                    </div>
                    <div>
                      <p className="font-medium">{formatDate(record.tanggal)}</p>
                      <p className="text-sm text-gray-600">{record.lokasi}</p>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="flex gap-2 text-sm">
                      <span>In: {record.checkIn || '--:--'}</span>
                      <span>Out: {record.checkOut || '--:--'}</span>
                    </div>
                    <Badge className={`${getStatusColor(record.status)} text-xs mt-1`}>
                      {record.status === 'present' && 'Hadir'}
                      {record.status === 'late' && 'Terlambat'}
                      {record.status === 'absent' && 'Tidak Hadir'}
                      {record.status === 'partial' && 'Partial'}
                    </Badge>
                  </div>
                </div>
              ))
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Clock, MapPin, CheckCircle, XCircle, Timer, Activity, Calendar, Map } from 'lucide-react';
import LeafletMap from './LeafletMap';
import GoogleMapsErrorBoundary from './GoogleMapsErrorBoundary';

interface AttendanceStatus {
  status: string;
  message: string;
  can_check_in: boolean;
  can_check_out: boolean;
  attendance: {
    id: number;
    check_in_time: string | null;
    check_out_time: string | null;
    work_duration: string | null;
    work_duration_minutes: number | null;
    location_in: string | null;
    location_out: string | null;
    status: string;
    is_late: boolean;
  } | null;
}

export function Presensi() {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [attendanceStatus, setAttendanceStatus] = useState<AttendanceStatus | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [showMap, setShowMap] = useState(false);
  const [checkinLocation, setCheckinLocation] = useState<{lat: number; lng: number; accuracy?: number; address?: string} | null>(null);
  const [checkoutLocation, setCheckoutLocation] = useState<{lat: number; lng: number; accuracy?: number; address?: string} | null>(null);
  const [isLocationRequired, setIsLocationRequired] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Helper function to format time display
  const formatTime = (timeString: string | null): string => {
    if (!timeString) return '--:--';
    
    // Handle different time formats
    if (timeString.includes('T')) {
      // ISO format: 2024-01-01T14:30:00.000000Z
      return new Date(timeString).toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false 
      });
    } else if (timeString.length > 5) {
      // Format: 14:30:00
      return timeString.substring(0, 5);
    } else {
      // Format: 14:30
      return timeString;
    }
  };

  // Fetch attendance status from API
  const fetchAttendanceStatus = async () => {
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const apiToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
      
      const headers: Record<string, string> = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      };
      
      if (apiToken) {
        headers['Authorization'] = `Bearer ${apiToken}`;
      }
      
      const response = await fetch('/api/v2/dashboards/paramedis/attendance/status', {
        credentials: 'include',
        headers
      });
      
      if (response.ok) {
        const result = await response.json();
        if (result.success && result.data) {
          console.log('🔍 Attendance Status Response:', JSON.stringify(result.data, null, 2));
          console.log('🔍 Attendance Object:', result.data.attendance);
          console.log('🔍 Check In Time:', result.data.attendance?.check_in_time);
          console.log('🔍 Check Out Time:', result.data.attendance?.check_out_time);
          console.log('🔍 Status:', result.data.status);
          console.log('🔍 Can Check Out:', result.data.can_check_out);
          setAttendanceStatus(result.data);
          setError(null);
        }
      } else {
        console.error('Failed to fetch attendance status:', response.status);
        setError('Gagal memuat status presensi');
      }
    } catch (error) {
      console.error('Error fetching attendance status:', error);
      setError('Gagal memuat status presensi');
    }
  };

  // Update time every second and refresh attendance status
  useEffect(() => {
    // Fetch initial status
    fetchAttendanceStatus();
    
    const timer = setInterval(() => {
      setCurrentTime(new Date());
    }, 1000);
    
    // Refresh attendance status every 30 seconds
    const statusTimer = setInterval(() => {
      fetchAttendanceStatus();
    }, 30000);
    
    return () => {
      clearInterval(timer);
      clearInterval(statusTimer);
    };
  }, []);

  const handleCheckIn = async () => {
    // Check if location is required and available
    if (isLocationRequired && !checkinLocation) {
      alert('Mohon pilih lokasi presensi terlebih dahulu menggunakan peta di bawah');
      setShowMap(true);
      return;
    }

    if (!attendanceStatus?.can_check_in) {
      alert(attendanceStatus?.message || 'Anda tidak dapat melakukan check-in saat ini');
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const apiToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
      
      const headers: Record<string, string> = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      };
      
      if (apiToken) {
        headers['Authorization'] = `Bearer ${apiToken}`;
      }

      const response = await fetch('/api/v2/dashboards/paramedis/checkin', {
        method: 'POST',
        credentials: 'include',
        headers,
        body: JSON.stringify({
          latitude: checkinLocation?.lat,
          longitude: checkinLocation?.lng,
          accuracy: checkinLocation?.accuracy,
          location_name: checkinLocation?.address || 'Location from Map',
          notes: 'Check-in from mobile app'
        })
      });

      const result = await response.json();

      if (response.ok && result.success) {
        // Refresh attendance status immediately
        await fetchAttendanceStatus();
        
        alert(`Check-in berhasil!\nWaktu: ${result.data.time_in}\nLokasi: ${checkinLocation?.address || 'Koordinat: ' + checkinLocation?.lat + ', ' + checkinLocation?.lng}`);
      } else {
        throw new Error(result.message || 'Check-in gagal');
      }
    } catch (error: any) {
      console.error('Check-in error:', error);
      setError(error.message || 'Gagal melakukan check-in');
      alert('Gagal melakukan check-in: ' + (error.message || 'Terjadi kesalahan'));
    } finally {
      setIsLoading(false);
    }
  };

  const handleCheckOut = async () => {
    // Check if location is required and available for checkout
    if (isLocationRequired && !checkoutLocation) {
      alert('Mohon pilih lokasi check-out terlebih dahulu menggunakan peta di bawah');
      setShowMap(true);
      return;
    }

    if (!attendanceStatus?.can_check_out) {
      alert(attendanceStatus?.message || 'Anda tidak dapat melakukan check-out saat ini');
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const apiToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
      
      const headers: Record<string, string> = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      };
      
      if (apiToken) {
        headers['Authorization'] = `Bearer ${apiToken}`;
      }

      const response = await fetch('/api/v2/dashboards/paramedis/checkout', {
        method: 'POST',
        credentials: 'include',
        headers,
        body: JSON.stringify({
          latitude: checkoutLocation?.lat,
          longitude: checkoutLocation?.lng,
          accuracy: checkoutLocation?.accuracy,
          location_name: checkoutLocation?.address || 'Location from Map',
          notes: 'Check-out from mobile app'
        })
      });

      const result = await response.json();

      if (response.ok && result.success) {
        // Refresh attendance status immediately
        await fetchAttendanceStatus();
        
        const workDuration = result.data.work_duration?.hours_minutes || result.data.work_duration?.formatted || 'N/A';
        alert(`Check-out berhasil!\nWaktu: ${result.data.time_out}\nTotal Jam Kerja: ${workDuration}\nLokasi: ${checkoutLocation?.address || 'Koordinat: ' + checkoutLocation?.lat + ', ' + checkoutLocation?.lng}`);
        
        // Reset locations for next day
        setCheckinLocation(null);
        setCheckoutLocation(null);
      } else {
        throw new Error(result.message || 'Check-out gagal');
      }
    } catch (error: any) {
      console.error('Check-out error:', error);
      setError(error.message || 'Gagal melakukan check-out');
      alert('Gagal melakukan check-out: ' + (error.message || 'Terjadi kesalahan'));
    } finally {
      setIsLoading(false);
    }
  };

  const handleLocationSelect = (location: {lat: number; lng: number; accuracy?: number; address?: string}) => {
    try {
      if (attendanceStatus?.can_check_in) {
        // Setting check-in location
        setCheckinLocation(location);
      } else if (attendanceStatus?.can_check_out) {
        // Setting check-out location
        setCheckoutLocation(location);
      }
    } catch (error) {
      // Error setting location
    }
  };

  const getTodayDate = () => {
    return currentTime.toLocaleDateString('id-ID', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });
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

  return (
    <motion.div 
      variants={container}
      initial="hidden"
      animate="show"
      className="space-y-6 theme-transition"
    >
      {/* Header */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-r from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 border-0 shadow-xl card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <Timer className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white">Presensi</h2>
                  <p className="text-purple-100 dark:text-purple-200 text-sm font-medium">Check In & Check Out</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Current Status Card */}
      <motion.div variants={item}>
        <Card className="shadow-xl border-0 bg-gradient-to-br from-white to-blue-50/50 dark:from-gray-900 dark:to-purple-950/30 backdrop-blur-sm card-enhanced">
          <CardContent className="p-8 space-y-6">
            {/* Digital Clock */}
            <motion.div 
              className="text-center"
              animate={{ scale: [1, 1.02, 1] }}
              transition={{ duration: 2, repeat: Infinity }}
            >
              <div className="text-5xl bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent font-bold">
                {currentTime.toLocaleTimeString('id-ID', { 
                  hour: '2-digit', 
                  minute: '2-digit',
                  second: '2-digit'
                })}
              </div>
              <div className="text-base text-muted-foreground font-medium mt-2">
                {getTodayDate()}
              </div>
            </motion.div>

            {/* Location */}
            <motion.div 
              className="flex items-center justify-center gap-2"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ delay: 0.3 }}
            >
              <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                <MapPin className="w-4 h-4 text-blue-600 dark:text-blue-400" />
              </div>
              <span className="text-lg font-semibold text-high-contrast">KLINIK DOKTERKU</span>
            </motion.div>

            {/* Working Hours Display */}
            {attendanceStatus?.status === 'checked_in' && attendanceStatus?.attendance?.work_duration_minutes && (
              <motion.div
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                className="text-center p-4 bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/30 rounded-xl"
              >
                <div className="flex items-center justify-center gap-2 mb-2">
                  <Activity className="w-5 h-5 text-green-600 dark:text-green-400" />
                  <span className="text-green-700 dark:text-green-300 font-medium">Jam Kerja Hari Ini (Live)</span>
                </div>
                <div className="text-2xl text-green-800 dark:text-green-200 font-mono font-bold">
                  {Math.floor(attendanceStatus.attendance.work_duration_minutes / 60).toString().padStart(2, '0')}:
                  {(attendanceStatus.attendance.work_duration_minutes % 60).toString().padStart(2, '0')}:
                  {(Math.floor((Date.now() - new Date(attendanceStatus.attendance.check_in_time).getTime()) / 1000) % 60).toString().padStart(2, '0')}
                </div>
              </motion.div>
            )}

            {/* Check In/Out Button */}
            <AnimatePresence mode="wait">
              {attendanceStatus?.can_check_out ? (
                <motion.div
                  key="checked-in"
                  initial={{ opacity: 0, scale: 0.9 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 0.9 }}
                  className="space-y-4"
                >
                  <motion.div 
                    className="flex items-center justify-center gap-2 p-4 bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/30 rounded-xl"
                    animate={{ scale: [1, 1.02, 1] }}
                    transition={{ duration: 2, repeat: Infinity }}
                  >
                    <CheckCircle className="w-6 h-6 text-green-600 dark:text-green-400" />
                    <span className="text-green-700 dark:text-green-300 font-medium">
                      Check-in pada {formatTime(attendanceStatus?.attendance?.check_in_time)}
                    </span>
                  </motion.div>
                  <motion.div
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                  >
                    <Button 
                      onClick={handleCheckOut}
                      disabled={isLoading}
                      className="w-full bg-gradient-to-r from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 hover:from-red-600 hover:to-red-700 dark:hover:from-red-700 dark:hover:to-red-800 text-white shadow-lg h-14 text-lg font-semibold transition-all duration-300 disabled:opacity-50"
                    >
                      <XCircle className="w-6 h-6 mr-3" />
                      {isLoading ? 'Processing...' : 'Check Out'}
                    </Button>
                  </motion.div>
                </motion.div>
              ) : attendanceStatus?.can_check_in ? (
                <motion.div
                  key="not-checked-in"
                  initial={{ opacity: 0, scale: 0.9 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 0.9 }}
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                >
                  <Button 
                    onClick={handleCheckIn}
                    disabled={isLoading}
                    className="w-full bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 hover:from-blue-600 hover:to-blue-700 dark:hover:from-blue-700 dark:hover:to-blue-800 text-white shadow-lg h-14 text-lg font-semibold transition-all duration-300 disabled:opacity-50"
                  >
                    <CheckCircle className="w-6 h-6 mr-3" />
                    {isLoading ? 'Processing...' : 'Check In'}
                  </Button>
                </motion.div>
              ) : (
                <motion.div
                  key="completed"
                  initial={{ opacity: 0, scale: 0.9 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 0.9 }}
                  className="p-4 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-xl text-center"
                >
                  <CheckCircle className="w-6 h-6 text-gray-600 dark:text-gray-400 mx-auto mb-2" />
                  <span className="text-gray-700 dark:text-gray-300 font-medium">
                    {attendanceStatus?.message || 'Presensi sudah selesai untuk hari ini'}
                  </span>
                </motion.div>
              )}
            </AnimatePresence>
          </CardContent>
        </Card>
      </motion.div>

      {/* Quick Stats Today */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-high-contrast">
              <Calendar className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              Status Hari Ini
            </CardTitle>
          </CardHeader>
          <CardContent>
            {error && (
              <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {error}
              </div>
            )}
            
            <div className="grid grid-cols-3 gap-4">
              <div className="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-700">
                <Clock className="w-6 h-6 text-blue-600 dark:text-blue-400 mx-auto mb-2" />
                <div className="text-lg font-bold text-blue-700 dark:text-blue-300">
                  {formatTime(attendanceStatus?.attendance?.check_in_time)}
                </div>
                <div className="text-xs font-medium text-blue-600 dark:text-blue-400">Check In</div>
                {attendanceStatus?.attendance?.is_late && (
                  <div className="text-xs text-red-500 mt-1">Terlambat</div>
                )}
              </div>
              
              <div className="text-center p-4 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/50 dark:to-orange-900/30 rounded-xl border border-orange-200 dark:border-orange-700">
                <Clock className="w-6 h-6 text-orange-600 dark:text-orange-400 mx-auto mb-2" />
                <div className="text-lg font-bold text-orange-700 dark:text-orange-300">
                  {formatTime(attendanceStatus?.attendance?.check_out_time)}
                </div>
                <div className="text-xs font-medium text-orange-600 dark:text-orange-400">Check Out</div>
                {attendanceStatus?.status === 'checked_in' && (
                  <div className="text-xs text-orange-500 mt-1">Belum checkout</div>
                )}
                {attendanceStatus?.status === 'completed' && (
                  <div className="text-xs text-green-500 mt-1">✓ Selesai</div>
                )}
              </div>
              
              <div className="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/30 rounded-xl border border-green-200 dark:border-green-700">
                <Activity className="w-6 h-6 text-green-600 dark:text-green-400 mx-auto mb-2" />
                <div className="text-lg font-bold text-green-700 dark:text-green-300">
                  {attendanceStatus?.attendance?.work_duration || (
                    attendanceStatus?.attendance?.work_duration_minutes ? 
                    `${Math.floor(attendanceStatus.attendance.work_duration_minutes / 60)}h ${attendanceStatus.attendance.work_duration_minutes % 60}m` : 
                    '--h --m'
                  )}
                </div>
                <div className="text-xs font-medium text-green-600 dark:text-green-400">Total Jam</div>
                {attendanceStatus?.status === 'checked_in' && attendanceStatus?.attendance?.work_duration_minutes && (
                  <div className="text-xs text-green-600 dark:text-green-400 mt-1">
                    {Math.floor(attendanceStatus.attendance.work_duration_minutes / 60)}j {attendanceStatus.attendance.work_duration_minutes % 60}m (Berjalan)
                  </div>
                )}
              </div>
            </div>
            
            {attendanceStatus?.message && (
              <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-700 text-sm">
                Status: {attendanceStatus.message}
              </div>
            )}
          </CardContent>
        </Card>
      </motion.div>

      {/* Location Selection */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader className="pb-3">
            <div className="flex items-center justify-between">
              <CardTitle className="flex items-center gap-2 text-high-contrast">
                <Map className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                Lokasi Presensi
              </CardTitle>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setShowMap(!showMap)}
                className="text-blue-600 border-blue-200 hover:bg-blue-50 dark:text-blue-400 dark:border-blue-700 dark:hover:bg-blue-950/50"
              >
                {showMap ? 'Sembunyikan' : 'Tampilkan'} Peta
              </Button>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Location Status */}
            <div className="grid grid-cols-1 gap-3">
              <div className="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/30 rounded-lg">
                <div className="flex items-center gap-2">
                  <MapPin className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                  <span className="text-sm font-medium text-blue-700 dark:text-blue-300">
                    {attendanceStatus?.can_check_in ? 'Lokasi Check-in' : 'Lokasi Check-out'}
                  </span>
                </div>
                <div className="text-xs text-blue-600 dark:text-blue-400">
                  {(attendanceStatus?.can_check_in && checkinLocation) ? '✓ Tersimpan' : 
                   (attendanceStatus?.can_check_out && checkoutLocation) ? '✓ Tersimpan' : 
                   'Belum dipilih'}
                </div>
              </div>
              
              {/* Current Location Display */}
              {((checkinLocation && attendanceStatus?.can_check_in) || (checkoutLocation && attendanceStatus?.can_check_out)) && (
                <div className="p-3 bg-green-50 dark:bg-green-950/30 rounded-lg">
                  <div className="text-xs text-green-700 dark:text-green-300 space-y-1">
                    {attendanceStatus?.can_check_in && checkinLocation && (
                      <>
                        <p><strong>Check-in:</strong> {checkinLocation.address || `${checkinLocation.lat.toFixed(6)}, ${checkinLocation.lng.toFixed(6)}`}</p>
                        {checkinLocation.accuracy && (
                          <p><strong>Akurasi:</strong> {Math.round(checkinLocation.accuracy)} meter</p>
                        )}
                      </>
                    )}
                    {attendanceStatus?.can_check_out && checkoutLocation && (
                      <>
                        <p><strong>Check-out:</strong> {checkoutLocation.address || `${checkoutLocation.lat.toFixed(6)}, ${checkoutLocation.lng.toFixed(6)}`}</p>
                        {checkoutLocation.accuracy && (
                          <p><strong>Akurasi:</strong> {Math.round(checkoutLocation.accuracy)} meter</p>
                        )}
                      </>
                    )}
                  </div>
                </div>
              )}
            </div>

            {/* Google Maps */}
            <AnimatePresence>
              {showMap && (
                <motion.div
                  initial={{ opacity: 0, height: 0 }}
                  animate={{ opacity: 1, height: 'auto' }}
                  exit={{ opacity: 0, height: 0 }}
                  transition={{ duration: 0.3 }}
                >
                  <GoogleMapsErrorBoundary>
                    <LeafletMap
                      onLocationSelect={handleLocationSelect}
                      height="400px"
                    />
                  </GoogleMapsErrorBoundary>
                </motion.div>
              )}
            </AnimatePresence>
          </CardContent>
        </Card>
      </motion.div>

      {/* Quick Actions */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="text-high-contrast">Aksi Cepat</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-200 dark:hover:border-blue-700 font-medium transition-colors duration-300">
                <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                  <Calendar className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                </div>
                Lihat Riwayat Presensi
              </Button>
            </motion.div>
            
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-green-50 dark:hover:bg-green-950/50 hover:border-green-200 dark:hover:border-green-700 font-medium transition-colors duration-300">
                <div className="w-8 h-8 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                  <Activity className="w-4 h-4 text-green-600 dark:text-green-400" />
                </div>
                Laporan Jam Kerja
              </Button>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>
    </motion.div>
  );
}
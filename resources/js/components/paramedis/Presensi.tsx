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
  const [workDurationData, setWorkDurationData] = useState<{ timeString: string; hoursMinutes: string; totalMinutes: number; isActive: boolean }>({
    timeString: '00:00:00',
    hoursMinutes: '0j 0m',
    totalMinutes: 0,
    isActive: false
  });

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

  // Helper function to parse date strings more robustly
  const parseDateTime = (dateInput: string | Date): Date => {
    if (dateInput instanceof Date) {
      return dateInput;
    }
    
    if (typeof dateInput !== 'string') {
      throw new Error('Invalid date input type');
    }
    
    // Try multiple parsing strategies
    const strategies = [
      // Standard ISO format
      () => new Date(dateInput),
      // Replace space with T for ISO format
      () => new Date(dateInput.replace(' ', 'T')),
      // Add timezone if missing
      () => new Date(dateInput + (dateInput.includes('Z') || dateInput.includes('+') ? '' : 'Z')),
      // Handle local timezone format "2025-01-25 10:30:00"
      () => {
        const normalized = dateInput.replace(' ', 'T');
        return new Date(normalized + (normalized.includes('Z') || normalized.includes('+') ? '' : '+07:00'));
      },
      // Handle time-only format like "14:30:00" - combine with today's date
      () => {
        if (/^\d{2}:\d{2}:\d{2}$/.test(dateInput)) {
          const today = new Date().toISOString().split('T')[0];
          return new Date(`${today}T${dateInput}`);
        }
        throw new Error('Not time-only format');
      },
      // Handle time-only format like "14:30" - combine with today's date
      () => {
        if (/^\d{2}:\d{2}$/.test(dateInput)) {
          const today = new Date().toISOString().split('T')[0];
          return new Date(`${today}T${dateInput}:00`);
        }
        throw new Error('Not time-only format');  
      }
    ];
    
    for (const strategy of strategies) {
      try {
        const result = strategy();
        if (!isNaN(result.getTime())) {
          console.log('🔍 Successful parse strategy for:', dateInput, '→', result.toISOString());
          return result;
        }
      } catch (e) {
        // Try next strategy
      }
    }
    
    throw new Error(`Unable to parse date: ${dateInput}`);
  };

  // Helper function to calculate work minutes between two times
  const calculateWorkMinutes = (checkInTime: string | Date, checkOutTime?: string | Date | null): number => {
    try {
      console.log('🔍 calculateWorkMinutes input:', { checkInTime, checkOutTime });
      
      const startTime = parseDateTime(checkInTime);
      const endTime = checkOutTime ? parseDateTime(checkOutTime) : new Date();
      
      console.log('🔍 Parsed times:', { 
        startTimeStr: startTime.toString(),
        endTimeStr: endTime.toString(),
        startTime: startTime.toISOString(), 
        endTime: endTime.toISOString(),
        startTimeValue: startTime.getTime(),
        endTimeValue: endTime.getTime()
      });
      
      // Calculate total minutes with guaranteed non-negative integer result
      const diffMs = endTime.getTime() - startTime.getTime();
      const totalMinutes = diffMs / 1000 / 60;
      
      // Ensure result is always a positive integer (no decimals, no negatives)
      const result = Math.max(0, Math.floor(Math.abs(totalMinutes)));
      
      console.log('🔍 Calculation result:', { 
        diffMs, 
        totalMinutes, 
        result,
        diffInSeconds: diffMs / 1000,
        diffInHours: diffMs / 1000 / 60 / 60,
        isNegativeDiff: diffMs < 0
      });
      
      return result;
    } catch (error) {
      console.error('❌ Error calculating work minutes:', error, 'Input:', { checkInTime, checkOutTime });
      return 0;
    }
  };

  // CREATIVE APPROACH: Multi-method work duration calculation with extensive debugging
  const calculateRealTimeWorkDuration = (): { timeString: string; hoursMinutes: string; totalMinutes: number; isActive: boolean; debugInfo: any } => {
    const debugInfo: any = {
      timestamp: new Date().toISOString(),
      attendanceStatus: attendanceStatus?.attendance,
      hasCheckIn: !!attendanceStatus?.attendance?.check_in_time,
      rawCheckInTime: attendanceStatus?.attendance?.check_in_time,
      methods: {}
    };

    // Validation fallback for empty Check In
    if (!attendanceStatus?.attendance?.check_in_time) {
      console.log('🚨 CREATIVE DEBUG: No check-in time available', debugInfo);
      return { timeString: '00:00:00', hoursMinutes: '0j 0m', totalMinutes: 0, isActive: false, debugInfo };
    }

    // CREATIVE APPROACH: Try multiple calculation methods
    const calculationMethods = {
      // Method 1: Standard parsing
      standard: () => {
        try {
          const checkInTime = parseDateTime(attendanceStatus.attendance.check_in_time);
          const currentTime = Date.now();
          const checkOutTime = attendanceStatus.attendance.check_out_time 
            ? parseDateTime(attendanceStatus.attendance.check_out_time).getTime()
            : currentTime;
          
          const durationMs = checkOutTime - checkInTime.getTime();
          return Math.max(0, Math.floor(durationMs / 1000 / 60));
        } catch (e) {
          debugInfo.methods.standard = { error: e.message };
          return null;
        }
      },

      // Method 2: Direct Date parsing with today fallback
      directToday: () => {
        try {
          const timeStr = attendanceStatus.attendance.check_in_time;
          let checkInDate;
          
          if (timeStr.includes('T') || timeStr.includes(' ')) {
            // Full datetime
            checkInDate = new Date(timeStr.replace(' ', 'T'));
          } else {
            // Time only - combine with today
            const today = new Date().toISOString().split('T')[0];
            checkInDate = new Date(`${today}T${timeStr}`);
          }
          
          const now = new Date();
          const checkOutDate = attendanceStatus.attendance.check_out_time 
            ? new Date(attendanceStatus.attendance.check_out_time.replace(' ', 'T'))
            : now;
          
          const diffMs = checkOutDate.getTime() - checkInDate.getTime();
          return Math.max(0, Math.floor(diffMs / 1000 / 60));
        } catch (e) {
          debugInfo.methods.directToday = { error: e.message };
          return null;
        }
      },

      // Method 3: Server work_duration_minutes fallback
      serverDuration: () => {
        try {
          const serverMinutes = attendanceStatus?.attendance?.work_duration_minutes;
          if (typeof serverMinutes === 'number' && serverMinutes >= 0) {
            return Math.floor(serverMinutes);
          }
          return null;
        } catch (e) {
          debugInfo.methods.serverDuration = { error: e.message };
          return null;
        }
      },

      // Method 4: Manual time parsing for today
      manualToday: () => {
        try {
          const timeStr = attendanceStatus.attendance.check_in_time;
          const match = timeStr.match(/(\d{1,2}):(\d{2})(?::(\d{2}))?/);
          if (!match) return null;
          
          const hours = parseInt(match[1]);
          const minutes = parseInt(match[2]);
          
          const today = new Date();
          const checkInTime = new Date(today.getFullYear(), today.getMonth(), today.getDate(), hours, minutes, 0);
          
          const now = new Date();
          const diffMs = now.getTime() - checkInTime.getTime();
          return Math.max(0, Math.floor(diffMs / 1000 / 60));
        } catch (e) {
          debugInfo.methods.manualToday = { error: e.message };
          return null;
        }
      }
    };

    // Try each method until one works
    let totalMinutes = 0;
    let successfulMethod = 'none';

    for (const [methodName, method] of Object.entries(calculationMethods)) {
      const result = method();
      debugInfo.methods[methodName] = { result };
      
      if (result !== null && result >= 0) {
        totalMinutes = result;
        successfulMethod = methodName;
        break;
      }
    }

    // Format the results
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    const seconds = attendanceStatus.attendance.check_out_time ? 0 : (Math.floor(Date.now() / 1000) % 60);
    
    const timeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    const hoursMinutes = `${hours}j ${minutes}m`;
    const isActive = !attendanceStatus.attendance.check_out_time;

    debugInfo.result = {
      totalMinutes,
      timeString,
      hoursMinutes,
      isActive,
      successfulMethod
    };

    // Enhanced logging
    console.log('🎨 CREATIVE CALCULATION RESULT:', {
      successfulMethod,
      totalMinutes,
      timeString,
      hoursMinutes,
      debugInfo
    });

    return { timeString, hoursMinutes, totalMinutes, isActive, debugInfo };
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
          console.log('🔍 Check In Time Raw:', result.data.attendance?.check_in_time);
          console.log('🔍 Check In Time Type:', typeof result.data.attendance?.check_in_time);
          console.log('🔍 Check Out Time Raw:', result.data.attendance?.check_out_time);
          console.log('🔍 Status:', result.data.status);
          console.log('🔍 Can Check Out:', result.data.can_check_out);
          
          // Test the calculation immediately after fetching
          if (result.data.attendance?.check_in_time) {
            const testMinutes = calculateWorkMinutes(result.data.attendance.check_in_time);
            console.log('🔍 Test calculation after fetch:', testMinutes, 'minutes');
          }
          
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
      // Update real-time work duration every second
      const newDurationData = calculateRealTimeWorkDuration();
      setWorkDurationData(newDurationData);
    }, 1000);
    
    // Refresh attendance status every 30 seconds
    const statusTimer = setInterval(() => {
      fetchAttendanceStatus();
    }, 30000);
    
    return () => {
      clearInterval(timer);
      clearInterval(statusTimer);
    };
  }, []); // Remove attendanceStatus dependency to prevent timer restart

  // Initialize real-time duration when attendance status changes
  useEffect(() => {
    if (attendanceStatus?.attendance?.check_in_time) {
      const initialDurationData = calculateRealTimeWorkDuration();
      setWorkDurationData(initialDurationData);
      
      // Manual test to verify calculation logic
      console.log('🧪 Manual test case:');
      const now = new Date();
      const testCheckIn = new Date(now.getTime() - (17 * 60 * 1000)); // 17 minutes ago
      const testResult = calculateWorkMinutes(testCheckIn);
      console.log('🧪 Test: 17 minutes ago should give ~17:', testResult);
      
      // Test with different string formats
      const testFormats = [
        testCheckIn.toISOString(), // "2025-01-25T10:30:00.000Z"
        testCheckIn.toISOString().replace('T', ' ').replace('.000Z', ''), // "2025-01-25 10:30:00"
        testCheckIn.toLocaleString('sv-SE') // Swedish locale gives "2025-01-25 10:30:00"
      ];
      
      testFormats.forEach((format, index) => {
        const result = calculateWorkMinutes(format);
        console.log(`🧪 Test format ${index + 1} (${format}) should give ~17:`, result);
      });
    }
  }, [attendanceStatus?.attendance?.check_in_time, attendanceStatus?.attendance?.check_out_time]);

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

    // Log debugging info but let server handle validation
    if (attendanceStatus?.attendance?.check_in_time) {
      console.log('🔍 Check-out attempt - attendance data:', {
        check_in_time: attendanceStatus.attendance.check_in_time,
        check_out_time: attendanceStatus.attendance.check_out_time,
        status: attendanceStatus.status
      });
      
      // Calculate work minutes for debugging only (no validation)
      const workMinutes = Math.max(0, Math.floor(calculateWorkMinutes(attendanceStatus.attendance.check_in_time)));
      console.log('🔍 Client calculated work minutes:', workMinutes, '(server will validate)');
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
        // Handle server-side validation errors with proper duration calculation
        if (result.message && (result.message.includes('minimal') || result.message.includes('minimum') || result.message.includes('terlalu cepat'))) {
          // Calculate actual work minutes using helper function with guaranteed positive integer
          const actualMinutes = attendanceStatus?.attendance?.check_in_time 
            ? Math.max(0, Math.floor(calculateWorkMinutes(attendanceStatus.attendance.check_in_time)))
            : 0;
          
          // Extract minimum minutes from server message or use default
          let minimumMinutes = 30;
          const minMatch = result.message.match(/minimal(?:.*?)(\d+)(?:.*?)menit/i) || 
                          result.message.match(/(\d+)(?:.*?)menit/i);
          if (minMatch && minMatch[1]) {
            minimumMinutes = parseInt(minMatch[1]);
          }
          
          // Show clean formatted error message without decimals or negatives
          alert(`Gagal melakukan check-out: Minimal bekerja ${minimumMinutes} menit. Anda baru bekerja ${actualMinutes} menit.`);
        } else {
          throw new Error(result.message || 'Check-out gagal');
        }
      }
    } catch (error: any) {
      console.error('Check-out error:', error);
      setError(error.message || 'Gagal melakukan check-out');
      
      // Handle error messages with duration calculation
      let errorMessage = error.message || 'Terjadi kesalahan';
      if (errorMessage.includes('minimal') || errorMessage.includes('minimum') || errorMessage.includes('terlalu cepat')) {
        // If it's a duration-related error, ensure we show the correct format with positive integers only
        const actualMinutes = attendanceStatus?.attendance?.check_in_time 
          ? Math.max(0, Math.floor(calculateWorkMinutes(attendanceStatus.attendance.check_in_time)))
          : 0;
        
        // Extract minimum minutes from error message or use default
        let minimumMinutes = 30;
        const minMatch = errorMessage.match(/minimal(?:.*?)(\d+)(?:.*?)menit/i) || 
                        errorMessage.match(/(\d+)(?:.*?)menit/i);
        if (minMatch && minMatch[1]) {
          minimumMinutes = parseInt(minMatch[1]);
        }
        
        errorMessage = `Minimal bekerja ${minimumMinutes} menit. Anda baru bekerja ${actualMinutes} menit.`;
      }
      
      alert('Gagal melakukan check-out: ' + errorMessage);
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

            {/* World-Class Working Hours Display */}
            {attendanceStatus?.attendance?.check_in_time && (
              <motion.div
                initial={{ opacity: 0, scale: 0.9, y: 20 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                className={`relative overflow-hidden rounded-2xl backdrop-blur-xl border transition-all duration-500 ${
                  workDurationData.isActive 
                    ? 'bg-gradient-to-br from-green-400/20 via-emerald-300/15 to-green-500/20 border-green-300/30 shadow-green-500/20' 
                    : 'bg-gradient-to-br from-gray-400/20 via-slate-300/15 to-gray-500/20 border-gray-300/30 shadow-gray-500/20'
                } shadow-2xl`}
                style={{
                  boxShadow: workDurationData.isActive 
                    ? '0 25px 50px -12px rgba(34, 197, 94, 0.25), 0 0 0 1px rgba(34, 197, 94, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1)' 
                    : '0 25px 50px -12px rgba(148, 163, 184, 0.25), 0 0 0 1px rgba(148, 163, 184, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1)'
                }}
              >
                {/* Animated background gradient */}
                <div className={`absolute inset-0 opacity-80 ${
                  workDurationData.isActive 
                    ? 'bg-gradient-to-br from-green-600 via-emerald-700 to-green-800' 
                    : 'bg-gradient-to-br from-gray-600 via-slate-700 to-gray-800'
                } animate-pulse`} />
                
                {/* Dark overlay for better text contrast */}
                <div className="absolute inset-0 bg-black/20 backdrop-blur-sm" />
                
                <div className="relative z-10 p-6">
                  <div className="flex items-center justify-center gap-3 mb-4">
                    <motion.div
                      animate={{ 
                        rotate: workDurationData.isActive ? [0, 360] : 0,
                        scale: workDurationData.isActive ? [1, 1.1, 1] : 1
                      }}
                      transition={{ 
                        rotate: { duration: 8, repeat: Infinity, ease: "linear" },
                        scale: { duration: 2, repeat: Infinity }
                      }}
                      className={`w-10 h-10 rounded-full flex items-center justify-center ${
                        workDurationData.isActive 
                          ? 'bg-green-500/30 border-2 border-green-400/50' 
                          : 'bg-gray-500/30 border-2 border-gray-400/50'
                      } backdrop-blur-md shadow-xl`}
                    >
                      <span className="text-2xl">🕒</span>
                    </motion.div>
                    <div className="text-center">
                      <h3 className={`font-black text-xl ${
                        workDurationData.isActive 
                          ? 'text-white drop-shadow-lg' 
                          : 'text-white drop-shadow-lg'
                      }`}
                      style={{
                        textShadow: '0 2px 4px rgba(0, 0, 0, 0.8)'
                      }}>
                        {workDurationData.isActive ? 'Jam Kerja Live' : 'Total Jam Kerja'}
                      </h3>
                      <p className={`text-base font-bold ${
                        workDurationData.isActive 
                          ? 'text-white drop-shadow-lg' 
                          : 'text-white drop-shadow-lg'
                      }`}
                      style={{
                        textShadow: '0 1px 2px rgba(0, 0, 0, 0.8)'
                      }}>
                        Hari Ini
                      </p>
                    </div>
                  </div>
                  
                  {/* Time Display */}
                  <div className="text-center space-y-3">
                    <motion.div 
                      className={`text-5xl font-mono font-black tracking-wider ${
                        workDurationData.isActive 
                          ? 'text-white drop-shadow-2xl' 
                          : 'text-white drop-shadow-2xl'
                      }`}
                      style={{
                        textShadow: workDurationData.isActive 
                          ? '0 4px 8px rgba(0, 0, 0, 0.8), 0 2px 4px rgba(16, 128, 96, 0.6)' 
                          : '0 4px 8px rgba(0, 0, 0, 0.8), 0 2px 4px rgba(128, 128, 128, 0.6)'
                      }}
                      animate={{ 
                        scale: workDurationData.isActive ? [1, 1.02, 1] : 1 
                      }}
                      transition={{ duration: 1, repeat: Infinity }}
                    >
                      {(() => {
                        // CREATIVE MAIN TIMER: Use fresh calculation instead of stale state
                        const currentData = calculateRealTimeWorkDuration();
                        console.log('🎨 CREATIVE MAIN TIMER:', currentData.timeString);
                        return currentData.timeString || '00:00:00';
                      })()}
                    </motion.div>
                    
                    <div className={`text-xl font-black ${
                      workDurationData.isActive 
                        ? 'text-white drop-shadow-lg' 
                        : 'text-white drop-shadow-lg'
                    }`}
                    style={{
                      textShadow: '0 2px 4px rgba(0, 0, 0, 0.8)'
                    }}>
                      {(() => {
                        // CREATIVE MAIN TIMER: Use fresh calculation for hours/minutes
                        const currentData = calculateRealTimeWorkDuration();
                        console.log('🎨 CREATIVE MAIN HOURS:', currentData.hoursMinutes);
                        return currentData.hoursMinutes || '0j 0m';
                      })()}
                    </div>
                    
                    {workDurationData.isActive && (
                      <motion.div
                        initial={{ opacity: 0 }}
                        animate={{ opacity: [0.7, 1, 0.7] }}
                        transition={{ duration: 2, repeat: Infinity }}
                        className="flex items-center justify-center gap-2 text-white text-base font-bold"
                        style={{
                          textShadow: '0 2px 4px rgba(0, 0, 0, 0.9)'
                        }}
                      >
                        <div className="w-3 h-3 rounded-full bg-white animate-pulse shadow-lg" />
                        🔴 LIVE - Berjalan Real-Time
                        <div className="w-3 h-3 rounded-full bg-white animate-pulse shadow-lg" />
                        {(() => {
                          // CREATIVE VISUAL INDICATOR: Show which calculation method succeeded
                          const currentData = calculateRealTimeWorkDuration();
                          const methodEmojis = {
                            standard: '🎯',
                            directToday: '📅', 
                            serverDuration: '🖥️',
                            manualToday: '🔧',
                            none: '❌'
                          };
                          const methodNames = {
                            standard: 'Standard',
                            directToday: 'Direct',
                            serverDuration: 'Server', 
                            manualToday: 'Manual',
                            none: 'Failed'
                          };
                          return (
                            <div className="text-xs opacity-80">
                              {methodEmojis[currentData.debugInfo?.result?.successfulMethod || 'none']} {methodNames[currentData.debugInfo?.result?.successfulMethod || 'none']}
                            </div>
                          );
                        })()}
                      </motion.div>
                    )}
                  </div>
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
                      Check-in pada {formatTime(attendanceStatus?.attendance?.check_in_time || null)}
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
                  {formatTime(attendanceStatus?.attendance?.check_in_time || null)}
                </div>
                <div className="text-xs font-medium text-blue-600 dark:text-blue-400">Check In</div>
                {attendanceStatus?.attendance?.is_late && (
                  <div className="text-xs text-red-500 mt-1">Terlambat</div>
                )}
              </div>
              
              <div className="text-center p-4 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/50 dark:to-orange-900/30 rounded-xl border border-orange-200 dark:border-orange-700">
                <Clock className="w-6 h-6 text-orange-600 dark:text-orange-400 mx-auto mb-2" />
                <div className="text-lg font-bold text-orange-700 dark:text-orange-300">
                  {formatTime(attendanceStatus?.attendance?.check_out_time || null)}
                </div>
                <div className="text-xs font-medium text-orange-600 dark:text-orange-400">Check Out</div>
                {attendanceStatus?.status === 'checked_in' && (
                  <div className="text-xs text-orange-500 mt-1">Belum checkout</div>
                )}
                {attendanceStatus?.status === 'completed' && (
                  <div className="text-xs text-green-500 mt-1">✓ Selesai</div>
                )}
              </div>
              
              <div className={`text-center p-4 rounded-xl border transition-all duration-300 ${
                workDurationData?.isActive
                  ? 'bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/30 border-green-200 dark:border-green-700 shadow-green-500/20'
                  : 'bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-950/50 dark:to-gray-900/30 border-gray-200 dark:border-gray-700 shadow-gray-500/20'
              } backdrop-blur-sm shadow-lg`}>
                <div className={`w-6 h-6 mx-auto mb-2 ${
                  workDurationData?.isActive 
                    ? 'text-green-600 dark:text-green-400' 
                    : 'text-gray-600 dark:text-gray-400'
                }`}>
                  {workDurationData?.isActive ? (
                    <motion.div animate={{ rotate: [0, 360] }} transition={{ duration: 8, repeat: Infinity, ease: "linear" }}>
                      <Activity className="w-6 h-6" />
                    </motion.div>
                  ) : (
                    <Activity className="w-6 h-6" />
                  )}
                </div>
                <div className={`text-xl font-black font-mono ${
                  workDurationData?.isActive 
                    ? 'text-green-800 dark:text-green-200' 
                    : 'text-gray-800 dark:text-gray-200'
                }`}
                style={{
                  textShadow: workDurationData?.isActive 
                    ? '0 1px 2px rgba(0, 0, 0, 0.3), 0 0 4px rgba(16, 128, 96, 0.3)' 
                    : '0 1px 2px rgba(0, 0, 0, 0.3)'
                }}>
                  {(() => {
                    // CREATIVE DEBUG: Show calculation method and result
                    const currentData = calculateRealTimeWorkDuration();
                    console.log('🎨 CREATIVE QUICK STATS:', currentData);
                    
                    // Use the freshly calculated data instead of stale state
                    return currentData.hoursMinutes || '0j 0m';
                  })()}
                </div>
                <div className={`text-xs font-bold ${
                  workDurationData?.isActive 
                    ? 'text-green-600 dark:text-green-400' 
                    : 'text-gray-600 dark:text-gray-400'
                }`}>
                  Total Jam Kerja
                </div>
                {workDurationData?.isActive && (
                  <div className="text-xs text-green-600 dark:text-green-400 mt-1 flex items-center justify-center gap-1">
                    <div className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" />
                    Live
                  </div>
                )}
                {attendanceStatus?.status === 'completed' && (
                  <div className="text-xs text-green-500 mt-1">✓ Selesai</div>
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
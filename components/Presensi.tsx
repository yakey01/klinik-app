import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Badge } from './ui/badge';
import { Clock, MapPin, CheckCircle, XCircle, Timer, Activity, Calendar } from 'lucide-react';

export function Presensi() {
  const [currentTime, setCurrentTime] = useState(new Date());
  const [isCheckedIn, setIsCheckedIn] = useState(false);
  const [checkedInAt, setCheckedInAt] = useState<string | null>(null);
  const [workingHours, setWorkingHours] = useState<string>('00:00:00');

  // Update time every second
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
      
      // Update working hours if checked in
      if (isCheckedIn && checkedInAt) {
        const checkedInTime = new Date();
        const [hours, minutes] = checkedInAt.split(':');
        checkedInTime.setHours(parseInt(hours), parseInt(minutes), 0);
        
        const diff = new Date().getTime() - checkedInTime.getTime();
        const workHours = Math.floor(diff / (1000 * 60 * 60));
        const workMinutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const workSeconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        setWorkingHours(`${workHours.toString().padStart(2, '0')}:${workMinutes.toString().padStart(2, '0')}:${workSeconds.toString().padStart(2, '0')}`);
      }
    }, 1000);
    
    return () => clearInterval(timer);
  }, [isCheckedIn, checkedInAt]);

  const handleCheckIn = () => {
    const now = new Date();
    setIsCheckedIn(true);
    setCheckedInAt(now.toLocaleTimeString('id-ID', { 
      hour: '2-digit', 
      minute: '2-digit' 
    }));
  };

  const handleCheckOut = () => {
    setIsCheckedIn(false);
    setCheckedInAt(null);
    setWorkingHours('00:00:00');
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
      className="space-y-6"
    >
      {/* Header */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-r from-purple-500 to-purple-600 border-0 shadow-xl">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                  <Timer className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl text-white">Presensi</h2>
                  <p className="text-purple-100 text-sm">Check In & Check Out</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Current Status Card */}
      <motion.div variants={item}>
        <Card className="shadow-xl border-0 bg-gradient-to-br from-white to-blue-50/50 backdrop-blur-sm">
          <CardContent className="p-8 space-y-6">
            {/* Digital Clock */}
            <motion.div 
              className="text-center"
              animate={{ scale: [1, 1.02, 1] }}
              transition={{ duration: 2, repeat: Infinity }}
            >
              <div className="text-5xl bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                {currentTime.toLocaleTimeString('id-ID', { 
                  hour: '2-digit', 
                  minute: '2-digit',
                  second: '2-digit'
                })}
              </div>
              <div className="text-base text-muted-foreground mt-2">
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
              <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <MapPin className="w-4 h-4 text-blue-600" />
              </div>
              <span className="text-lg">KLINIK DOKTERKU</span>
            </motion.div>

            {/* Working Hours Display */}
            {isCheckedIn && (
              <motion.div
                initial={{ opacity: 0, scale: 0.9 }}
                animate={{ opacity: 1, scale: 1 }}
                className="text-center p-4 bg-gradient-to-r from-green-100 to-green-200 rounded-xl"
              >
                <div className="flex items-center justify-center gap-2 mb-2">
                  <Activity className="w-5 h-5 text-green-600" />
                  <span className="text-green-700">Jam Kerja Hari Ini</span>
                </div>
                <div className="text-2xl text-green-800 font-mono">{workingHours}</div>
              </motion.div>
            )}

            {/* Check In/Out Button */}
            <AnimatePresence mode="wait">
              {isCheckedIn ? (
                <motion.div
                  key="checked-in"
                  initial={{ opacity: 0, scale: 0.9 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 0, scale: 0.9 }}
                  className="space-y-4"
                >
                  <motion.div 
                    className="flex items-center justify-center gap-2 p-4 bg-gradient-to-r from-green-100 to-green-200 rounded-xl"
                    animate={{ scale: [1, 1.02, 1] }}
                    transition={{ duration: 2, repeat: Infinity }}
                  >
                    <CheckCircle className="w-6 h-6 text-green-600" />
                    <span className="text-green-700">Check-in pada {checkedInAt}</span>
                  </motion.div>
                  <motion.div
                    whileHover={{ scale: 1.02 }}
                    whileTap={{ scale: 0.98 }}
                  >
                    <Button 
                      onClick={handleCheckOut}
                      className="w-full bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white shadow-lg h-14 text-lg"
                    >
                      <XCircle className="w-6 h-6 mr-3" />
                      Check Out
                    </Button>
                  </motion.div>
                </motion.div>
              ) : (
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
                    className="w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white shadow-lg h-14 text-lg"
                  >
                    <CheckCircle className="w-6 h-6 mr-3" />
                    Check In
                  </Button>
                </motion.div>
              )}
            </AnimatePresence>
          </CardContent>
        </Card>
      </motion.div>

      {/* Quick Stats Today */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Calendar className="w-5 h-5 text-blue-600" />
              Status Hari Ini
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-3 gap-4">
              <div className="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200">
                <Clock className="w-6 h-6 text-blue-600 mx-auto mb-2" />
                <div className="text-lg text-blue-700">
                  {isCheckedIn ? checkedInAt || '--:--' : '--:--'}
                </div>
                <div className="text-xs text-blue-600">Check In</div>
              </div>
              
              <div className="text-center p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl border border-orange-200">
                <Clock className="w-6 h-6 text-orange-600 mx-auto mb-2" />
                <div className="text-lg text-orange-700">
                  {!isCheckedIn && checkedInAt ? currentTime.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : '--:--'}
                </div>
                <div className="text-xs text-orange-600">Check Out</div>
              </div>
              
              <div className="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200">
                <Activity className="w-6 h-6 text-green-600 mx-auto mb-2" />
                <div className="text-lg text-green-700">{workingHours.split(':')[0]}h</div>
                <div className="text-xs text-green-600">Total Jam</div>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Quick Actions */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
          <CardHeader>
            <CardTitle>Aksi Cepat</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-blue-50 hover:border-blue-200">
                <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                  <Calendar className="w-4 h-4 text-blue-600" />
                </div>
                Lihat Riwayat Presensi
              </Button>
            </motion.div>
            
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-green-50 hover:border-green-200">
                <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                  <Activity className="w-4 h-4 text-green-600" />
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
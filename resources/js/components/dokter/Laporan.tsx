import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../ui/tabs';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Calendar, Clock, Download, BarChart3, Activity, Target, CheckCircle, XCircle, AlertTriangle } from 'lucide-react';

export function Laporan() {
  // Dynamic data - will be replaced with API calls in future
  const attendanceData = [
    {
      id: '1',
      tanggal: '2025-01-17',
      hari: 'Kamis',
      masuk: '07:00',
      keluar: '15:00',
      totalJam: '8 jam',
      status: 'present',
      shift: 'Pagi',
      lokasi: 'Unit Kerja' // Changed from hardcoded 'IGD'
    },
    {
      id: '2',
      tanggal: '2025-01-16',
      hari: 'Rabu',
      masuk: '07:15',
      keluar: '15:05',
      totalJam: '7 jam 50 menit',
      status: 'late',
      shift: 'Pagi',
      lokasi: 'Dokter Jaga' // Changed to valid unit kerja
    },
    {
      id: '3',
      tanggal: '2025-01-15',
      hari: 'Selasa',
      masuk: '15:00',
      keluar: '23:00',
      totalJam: '8 jam',
      status: 'present',
      shift: 'Siang',
      lokasi: 'Pelayanan' // Changed to valid unit kerja
    },
    {
      id: '4',
      tanggal: '2025-01-14',
      hari: 'Senin',
      masuk: '-',
      keluar: '-',
      totalJam: '0 jam',
      status: 'absent',
      shift: 'Pagi',
      lokasi: 'Pendaftaran' // Changed to valid unit kerja
    }
  ];

  const monthlyStats = {
    totalHadir: 18,
    totalTerlambat: 3,
    totalTidakHadir: 2,
    totalJamKerja: 144,
    rataRataJamPerHari: 8.0,
    tingkatKehadiran: 78
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'present': return 'bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/50 dark:to-green-800/50 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700';
      case 'late': return 'bg-gradient-to-r from-yellow-100 to-yellow-200 dark:from-yellow-900/50 dark:to-yellow-800/50 text-yellow-800 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700';
      case 'absent': return 'bg-gradient-to-r from-red-100 to-red-200 dark:from-red-900/50 dark:to-red-800/50 text-red-800 dark:text-red-300 border-red-200 dark:border-red-700';
      default: return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'present': return <CheckCircle className="w-4 h-4" />;
      case 'late': return <AlertTriangle className="w-4 h-4" />;
      case 'absent': return <XCircle className="w-4 h-4" />;
      default: return <Clock className="w-4 h-4" />;
    }
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short'
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
        <Card className="bg-gradient-to-r from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-700 border-0 shadow-xl card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <BarChart3 className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white text-heading-mobile">Laporan Presensi</h2>
                  <p className="text-indigo-100 dark:text-indigo-200 text-sm font-medium text-mobile-friendly">Riwayat & analisis kehadiran</p>
                </div>
              </div>
              <motion.div
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                <Button 
                  variant="ghost" 
                  size="sm" 
                  className="text-white hover:bg-white/20 dark:hover:bg-white/25 gap-2 backdrop-blur-sm border border-white/30 dark:border-white/40 font-medium transition-colors duration-300"
                >
                  <Download className="w-4 h-4" />
                  Unduh
                </Button>
              </motion.div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Stats Overview */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-high-contrast">
              <Target className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              Ringkasan Bulan Ini
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-3 gap-4 mb-6">
              <motion.div 
                className="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/30 rounded-xl border border-green-200 dark:border-green-700"
                whileHover={{ scale: 1.05 }}
                transition={{ duration: 0.2 }}
              >
                <CheckCircle className="w-6 h-6 text-green-600 dark:text-green-400 mx-auto mb-2" />
                <div className="text-2xl font-bold text-green-600 dark:text-green-400">{monthlyStats.totalHadir}</div>
                <div className="text-sm text-muted-foreground font-medium">Hadir</div>
              </motion.div>
              
              <motion.div 
                className="text-center p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-950/50 dark:to-yellow-900/30 rounded-xl border border-yellow-200 dark:border-yellow-700"
                whileHover={{ scale: 1.05 }}
                transition={{ duration: 0.2 }}
              >
                <AlertTriangle className="w-6 h-6 text-yellow-600 dark:text-yellow-400 mx-auto mb-2" />
                <div className="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{monthlyStats.totalTerlambat}</div>
                <div className="text-sm text-muted-foreground font-medium">Terlambat</div>
              </motion.div>
              
              <motion.div 
                className="text-center p-4 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-950/50 dark:to-red-900/30 rounded-xl border border-red-200 dark:border-red-700"
                whileHover={{ scale: 1.05 }}
                transition={{ duration: 0.2 }}
              >
                <XCircle className="w-6 h-6 text-red-600 dark:text-red-400 mx-auto mb-2" />
                <div className="text-2xl font-bold text-red-600 dark:text-red-400">{monthlyStats.totalTidakHadir}</div>
                <div className="text-sm text-muted-foreground font-medium">Tidak Hadir</div>
              </motion.div>
            </div>

            {/* Attendance Rate */}
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="font-medium text-high-contrast">Tingkat Kehadiran</span>
                <span className="text-lg font-bold text-high-contrast">{monthlyStats.tingkatKehadiran}%</span>
              </div>
              <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                <motion.div 
                  className="bg-gradient-to-r from-green-500 to-green-600 dark:from-green-400 dark:to-green-500 h-3 rounded-full"
                  initial={{ width: 0 }}
                  animate={{ width: `${monthlyStats.tingkatKehadiran}%` }}
                  transition={{ duration: 1.5, delay: 0.5 }}
                />
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Additional Stats */}
      <motion.div variants={item} className="grid grid-cols-2 gap-4">
        <Card className="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 border-0 shadow-lg card-enhanced">
          <CardContent className="p-4">
            <div className="flex items-center gap-3 text-white">
              <div className="w-10 h-10 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                <Clock className="w-5 h-5" />
              </div>
              <div>
                <p className="text-sm text-blue-100 dark:text-blue-200 font-medium">Total Jam</p>
                <p className="text-xl font-semibold">{monthlyStats.totalJamKerja}h</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 border-0 shadow-lg card-enhanced">
          <CardContent className="p-4">
            <div className="flex items-center gap-3 text-white">
              <div className="w-10 h-10 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                <Activity className="w-5 h-5" />
              </div>
              <div>
                <p className="text-sm text-purple-100 dark:text-purple-200 font-medium">Rata-rata/Hari</p>
                <p className="text-xl font-semibold">{monthlyStats.rataRataJamPerHari}h</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Attendance History */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-high-contrast">
              <Calendar className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              Riwayat Presensi
            </CardTitle>
          </CardHeader>
          <CardContent>
            <motion.div variants={container} className="space-y-3">
              {attendanceData.map((record, index) => (
                <motion.div
                  key={record.id}
                  variants={item}
                  whileHover={{ scale: 1.01, y: -1 }}
                  transition={{ duration: 0.2 }}
                >
                  <Card className="border border-gray-100 dark:border-gray-700 hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-800/50">
                    <CardContent className="p-4">
                      <div className="flex justify-between items-start mb-3">
                        <div>
                          <div className="flex items-center gap-2 mb-1">
                            <h4 className="text-base font-semibold text-high-contrast">{record.hari}</h4>
                            <span className="text-sm text-muted-foreground font-medium">{formatDate(record.tanggal)}</span>
                          </div>
                          <div className="flex items-center gap-2 text-sm text-muted-foreground font-medium">
                            <span>{record.shift}</span>
                            <span>•</span>
                            <span>{record.lokasi}</span>
                          </div>
                        </div>
                        <Badge className={`${getStatusColor(record.status)} border flex items-center gap-1 font-medium`}>
                          {getStatusIcon(record.status)}
                          {record.status === 'present' && 'Hadir'}
                          {record.status === 'late' && 'Terlambat'}
                          {record.status === 'absent' && 'Tidak Hadir'}
                        </Badge>
                      </div>
                      
                      <div className="grid grid-cols-3 gap-4 text-sm">
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                            <Clock className="w-3 h-3 text-blue-600 dark:text-blue-400" />
                          </div>
                          <div>
                            <p className="text-xs text-muted-foreground font-medium">Masuk</p>
                            <p className="font-semibold text-high-contrast">{record.masuk}</p>
                          </div>
                        </div>
                        
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 bg-orange-100 dark:bg-orange-900/50 rounded-full flex items-center justify-center">
                            <Clock className="w-3 h-3 text-orange-600 dark:text-orange-400" />
                          </div>
                          <div>
                            <p className="text-xs text-muted-foreground font-medium">Keluar</p>
                            <p className="font-semibold text-high-contrast">{record.keluar}</p>
                          </div>
                        </div>
                        
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                            <Activity className="w-3 h-3 text-green-600 dark:text-green-400" />
                          </div>
                          <div>
                            <p className="text-xs text-muted-foreground font-medium">Total</p>
                            <p className="font-semibold text-high-contrast">{record.totalJam}</p>
                          </div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </motion.div>
              ))}
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>
    </motion.div>
  );
}
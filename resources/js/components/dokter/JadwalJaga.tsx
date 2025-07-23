import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Calendar, Clock, MapPin, Plus, ChevronRight, Activity, Edit, X } from 'lucide-react';

interface JadwalItem {
  id: string;
  tanggal: string;
  waktu: string;
  lokasi: string;
  jenis: 'pagi' | 'siang' | 'malam';
  status: 'scheduled' | 'completed' | 'missed';
}

export function JadwalJaga() {
  const [jadwal, setJadwal] = useState<JadwalItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Fetch dynamic schedule data from API
  useEffect(() => {
    const fetchJadwalData = async () => {
      try {
        setIsLoading(true);
        setError(null);

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
        
        // Transform schedules to our format
        const dynamicJadwal = schedules.map((jadwal: any) => ({
          id: jadwal.id.toString(),
          tanggal: jadwal.tanggal,
          waktu: jadwal.waktu,
          lokasi: jadwal.lokasi,
          jenis: jadwal.jenis as 'pagi' | 'siang' | 'malam',
          status: jadwal.status as 'scheduled' | 'completed' | 'missed'
        }));

        setJadwal(dynamicJadwal);
      } catch (error) {
        console.error('Error fetching jadwal data:', error);
        setError('Gagal memuat data jadwal');
        // No fallback data - keep empty array
        setJadwal([]);
      } finally {
        setIsLoading(false);
      }
    };

    fetchJadwalData();
  }, []);

  const handleEditSchedule = (id: string) => {
    console.log('Edit schedule:', id);
    // Add edit functionality here
  };

  const handleCancelSchedule = (id: string) => {
    console.log('Cancel schedule:', id);
    setJadwal(prev => prev.map(item => 
      item.id === id ? { ...item, status: 'missed' as const } : item
    ));
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'scheduled': return 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-700';
      case 'completed': return 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700';
      case 'missed': return 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300 border-red-200 dark:border-red-700';
      default: return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border-gray-200 dark:border-gray-700';
    }
  };

  const getShiftColor = (jenis: string) => {
    switch (jenis) {
      case 'pagi': return 'bg-gradient-to-r from-yellow-400 to-yellow-500 dark:from-yellow-500 dark:to-yellow-600 text-white';
      case 'siang': return 'bg-gradient-to-r from-orange-400 to-orange-500 dark:from-orange-500 dark:to-orange-600 text-white';
      case 'malam': return 'bg-gradient-to-r from-purple-400 to-purple-500 dark:from-purple-500 dark:to-purple-600 text-white';
      default: return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
    }
  };

  const formatTanggal = (tanggal: string) => {
    return new Date(tanggal).toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
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

  const scheduledCount = jadwal.filter(item => item.status === 'scheduled').length;
  const completedCount = jadwal.filter(item => item.status === 'completed').length;
  const missedCount = jadwal.filter(item => item.status === 'missed').length;

  return (
    <motion.div 
      variants={container}
      initial="hidden"
      animate="show"
      className="space-y-6 theme-transition"
    >
      {/* Header Section */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 border-0 shadow-xl card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <Calendar className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white text-heading-mobile">Jadwal Jaga</h2>
                  <p className="text-blue-100 dark:text-blue-200 text-sm font-medium text-mobile-friendly">Kelola jadwal kerja Anda</p>
                </div>
              </div>
              <motion.div
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                <Button 
                  size="sm" 
                  className="bg-white/20 dark:bg-white/25 hover:bg-white/30 dark:hover:bg-white/35 border-white/30 dark:border-white/40 text-white gap-2 backdrop-blur-sm transition-colors duration-300"
                >
                  <Plus className="w-4 h-4" />
                  Tambah
                </Button>
              </motion.div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Quick Stats */}
      <motion.div variants={item} className="grid grid-cols-3 gap-3">
        <Card className="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/30 border-blue-200 dark:border-blue-700 card-enhanced">
          <CardContent className="p-4 text-center">
            <Activity className="w-5 h-5 text-blue-600 dark:text-blue-400 mx-auto mb-2" />
            <div className="text-lg font-bold text-blue-700 dark:text-blue-300">{scheduledCount}</div>
            <div className="text-xs font-medium text-blue-600 dark:text-blue-400">Dijadwalkan</div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-950/50 dark:to-emerald-900/30 border-emerald-200 dark:border-emerald-700 card-enhanced">
          <CardContent className="p-4 text-center">
            <Clock className="w-5 h-5 text-emerald-600 dark:text-emerald-400 mx-auto mb-2" />
            <div className="text-lg font-bold text-emerald-700 dark:text-emerald-300">{completedCount}</div>
            <div className="text-xs font-medium text-emerald-600 dark:text-emerald-400">Selesai</div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-950/50 dark:to-red-900/30 border-red-200 dark:border-red-700 card-enhanced">
          <CardContent className="p-4 text-center">
            <MapPin className="w-5 h-5 text-red-600 dark:text-red-400 mx-auto mb-2" />
            <div className="text-lg font-bold text-red-700 dark:text-red-300">{missedCount}</div>
            <div className="text-xs font-medium text-red-600 dark:text-red-400">Terlewat</div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Schedule List */}
      <motion.div variants={container} className="space-y-4">
        {isLoading ? (
          // Loading state
          <div className="space-y-4">
            {[1, 2, 3].map((index) => (
              <motion.div key={index} variants={item}>
                <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
                  <CardContent className="p-6">
                    <div className="animate-pulse">
                      <div className="flex justify-between items-start mb-4">
                        <div>
                          <div className="h-6 bg-gray-300 dark:bg-gray-600 rounded w-48 mb-2"></div>
                          <div className="h-4 bg-gray-300 dark:bg-gray-600 rounded w-24"></div>
                        </div>
                        <div className="h-6 bg-gray-300 dark:bg-gray-600 rounded w-20"></div>
                      </div>
                      <div className="space-y-3">
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-lg"></div>
                          <div className="h-4 bg-gray-300 dark:bg-gray-600 rounded flex-1"></div>
                        </div>
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-lg"></div>
                          <div className="h-4 bg-gray-300 dark:bg-gray-600 rounded flex-1"></div>
                        </div>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </motion.div>
            ))}
          </div>
        ) : error ? (
          // Error state
          <motion.div variants={item}>
            <Card className="shadow-lg border-0 bg-red-50 dark:bg-red-950/30 backdrop-blur-sm card-enhanced">
              <CardContent className="p-6 text-center">
                <div className="w-16 h-16 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                  <X className="w-8 h-8 text-red-600 dark:text-red-400" />
                </div>
                <h3 className="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
                  Gagal Memuat Jadwal
                </h3>
                <p className="text-red-600 dark:text-red-400 mb-4">{error}</p>
                <Button 
                  onClick={() => window.location.reload()} 
                  variant="outline"
                  className="border-red-300 dark:border-red-700 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/50"
                >
                  Coba Lagi
                </Button>
              </CardContent>
            </Card>
          </motion.div>
        ) : jadwal.length === 0 ? (
          // Empty state with orange warning color - Production deployed
          <motion.div variants={item}>
            <Card className="shadow-lg border-0 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-950/50 dark:to-orange-900/30 backdrop-blur-sm border-2 border-orange-200 dark:border-orange-700 card-enhanced">
              <CardContent className="p-6 text-center">
                <div className="w-16 h-16 bg-gradient-to-br from-orange-400 to-orange-500 dark:from-orange-500 dark:to-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                  <Calendar className="w-8 h-8 text-white" />
                </div>
                <h3 className="text-lg font-semibold text-orange-800 dark:text-orange-200 mb-2">
                  Belum Ada Jadwal Jaga
                </h3>
                <p className="text-orange-600 dark:text-orange-300 mb-4">
                  Jadwal jaga akan muncul setelah diatur oleh admin atau manajer
                </p>
                <div className="inline-flex items-center gap-2 px-4 py-2 bg-orange-100 dark:bg-orange-900/50 rounded-full border border-orange-200 dark:border-orange-700">
                  <Clock className="w-4 h-4 text-orange-600 dark:text-orange-400" />
                  <span className="text-sm font-medium text-orange-700 dark:text-orange-300">
                    Menunggu Penjadwalan
                  </span>
                </div>
              </CardContent>
            </Card>
          </motion.div>
        ) : (
          jadwal.map((scheduleItem, index) => (
          <motion.div
            key={scheduleItem.id}
            variants={item}
            whileHover={{ scale: 1.01, y: -2 }}
            transition={{ duration: 0.2 }}
          >
            <Card className="shadow-lg hover:shadow-xl transition-all duration-300 border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
              <CardContent className="p-6">
                <div className="flex justify-between items-start mb-4">
                  <div>
                    <h4 className="text-lg font-semibold text-high-contrast">{formatTanggal(scheduleItem.tanggal)}</h4>
                    <p className="text-sm text-muted-foreground font-medium">Shift {scheduleItem.jenis}</p>
                  </div>
                  <Badge className={`${getStatusColor(scheduleItem.status)} border`}>
                    {scheduleItem.status === 'scheduled' && 'Dijadwalkan'}
                    {scheduleItem.status === 'completed' && 'Selesai'}
                    {scheduleItem.status === 'missed' && 'Terlewat'}
                  </Badge>
                </div>
                
                <div className="space-y-3">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                      <Clock className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                    </div>
                    <span className="text-sm font-medium text-high-contrast">{scheduleItem.waktu}</span>
                    <Badge className={`${getShiftColor(scheduleItem.jenis)} text-xs font-semibold ml-auto`}>
                      {scheduleItem.jenis.charAt(0).toUpperCase() + scheduleItem.jenis.slice(1)}
                    </Badge>
                  </div>
                  
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg flex items-center justify-center">
                      <MapPin className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <span className="text-sm font-medium text-high-contrast flex-1">{scheduleItem.lokasi}</span>
                    <ChevronRight className="w-4 h-4 text-muted-foreground" />
                  </div>
                </div>
                
                {/* Action Buttons - Always visible for scheduled items */}
                {scheduleItem.status === 'scheduled' && (
                  <motion.div 
                    initial={{ opacity: 0, height: 0 }}
                    animate={{ opacity: 1, height: 'auto' }}
                    transition={{ duration: 0.3 }}
                    className="flex gap-3 pt-4 mt-4 border-t border-gray-100 dark:border-gray-700"
                  >
                    <motion.div
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      className="flex-1"
                    >
                      <Button 
                        variant="outline" 
                        size="sm" 
                        onClick={() => handleEditSchedule(scheduleItem.id)}
                        className="w-full border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-300 dark:hover:border-blue-600 gap-2 font-medium transition-colors duration-300"
                      >
                        <Edit className="w-4 h-4" />
                        Ubah
                      </Button>
                    </motion.div>
                    <motion.div
                      whileHover={{ scale: 1.02 }}
                      whileTap={{ scale: 0.98 }}
                      className="flex-1"
                    >
                      <Button 
                        variant="outline" 
                        size="sm" 
                        onClick={() => handleCancelSchedule(scheduleItem.id)}
                        className="w-full border-red-200 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 hover:border-red-300 dark:hover:border-red-600 gap-2 font-medium transition-colors duration-300"
                      >
                        <X className="w-4 h-4" />
                        Batalkan
                      </Button>
                    </motion.div>
                  </motion.div>
                )}
              </CardContent>
            </Card>
          </motion.div>
        ))
        )}
      </motion.div>

      {/* Add Schedule Button */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-dashed border-2 border-blue-200 dark:border-blue-700 card-enhanced">
          <CardContent className="p-6">
            <motion.div
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              className="text-center"
            >
              <Button 
                variant="ghost" 
                className="w-full h-16 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 gap-3 text-base font-medium transition-colors duration-300"
              >
                <Plus className="w-5 h-5" />
                Tambah Jadwal Baru
              </Button>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>
    </motion.div>
  );
}
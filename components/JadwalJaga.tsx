import { useState } from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
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
  const [jadwal, setJadwal] = useState<JadwalItem[]>([
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
    },
    {
      id: '3',
      tanggal: '2025-01-20',
      waktu: '23:00 - 07:00',
      lokasi: 'ICU',
      jenis: 'malam',
      status: 'scheduled'
    },
    {
      id: '4',
      tanggal: '2025-01-16',
      waktu: '23:00 - 07:00',
      lokasi: 'ICU',
      jenis: 'malam',
      status: 'completed'
    }
  ]);

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
      case 'scheduled': return 'bg-blue-100 text-blue-800 border-blue-200';
      case 'completed': return 'bg-green-100 text-green-800 border-green-200';
      case 'missed': return 'bg-red-100 text-red-800 border-red-200';
      default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

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
      className="space-y-6"
    >
      {/* Header Section */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-r from-blue-500 to-blue-600 border-0 shadow-xl">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                  <Calendar className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl text-white">Jadwal Jaga</h2>
                  <p className="text-blue-100 text-sm">Kelola jadwal kerja Anda</p>
                </div>
              </div>
              <motion.div
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                <Button 
                  size="sm" 
                  className="bg-white/20 hover:bg-white/30 border-white/30 text-white gap-2 backdrop-blur-sm"
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
        <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
          <CardContent className="p-4 text-center">
            <Activity className="w-5 h-5 text-blue-600 mx-auto mb-2" />
            <div className="text-lg text-blue-700">{scheduledCount}</div>
            <div className="text-xs text-blue-600">Dijadwalkan</div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-200">
          <CardContent className="p-4 text-center">
            <Clock className="w-5 h-5 text-emerald-600 mx-auto mb-2" />
            <div className="text-lg text-emerald-700">{completedCount}</div>
            <div className="text-xs text-emerald-600">Selesai</div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-red-50 to-red-100 border-red-200">
          <CardContent className="p-4 text-center">
            <MapPin className="w-5 h-5 text-red-600 mx-auto mb-2" />
            <div className="text-lg text-red-700">{missedCount}</div>
            <div className="text-xs text-red-600">Terlewat</div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Schedule List */}
      <motion.div variants={container} className="space-y-4">
        {jadwal.map((scheduleItem, index) => (
          <motion.div
            key={scheduleItem.id}
            variants={item}
            whileHover={{ scale: 1.01, y: -2 }}
            transition={{ duration: 0.2 }}
          >
            <Card className="shadow-lg hover:shadow-xl transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm">
              <CardContent className="p-6">
                <div className="flex justify-between items-start mb-4">
                  <div>
                    <h4 className="text-lg">{formatTanggal(scheduleItem.tanggal)}</h4>
                    <p className="text-sm text-muted-foreground">Shift {scheduleItem.jenis}</p>
                  </div>
                  <Badge className={`${getStatusColor(scheduleItem.status)} border`}>
                    {scheduleItem.status === 'scheduled' && 'Dijadwalkan'}
                    {scheduleItem.status === 'completed' && 'Selesai'}
                    {scheduleItem.status === 'missed' && 'Terlewat'}
                  </Badge>
                </div>
                
                <div className="space-y-3">
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                      <Clock className="w-4 h-4 text-blue-600" />
                    </div>
                    <span className="text-sm">{scheduleItem.waktu}</span>
                    <Badge className={`${getShiftColor(scheduleItem.jenis)} text-xs ml-auto`}>
                      {scheduleItem.jenis.charAt(0).toUpperCase() + scheduleItem.jenis.slice(1)}
                    </Badge>
                  </div>
                  
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                      <MapPin className="w-4 h-4 text-emerald-600" />
                    </div>
                    <span className="text-sm flex-1">{scheduleItem.lokasi}</span>
                    <ChevronRight className="w-4 h-4 text-muted-foreground" />
                  </div>
                </div>
                
                {/* Action Buttons - Always visible for scheduled items */}
                {scheduleItem.status === 'scheduled' && (
                  <motion.div 
                    initial={{ opacity: 0, height: 0 }}
                    animate={{ opacity: 1, height: 'auto' }}
                    transition={{ duration: 0.3 }}
                    className="flex gap-3 pt-4 mt-4 border-t border-gray-100"
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
                        className="w-full border-blue-200 text-blue-600 hover:bg-blue-50 hover:border-blue-300 gap-2"
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
                        className="w-full border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300 gap-2"
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
        ))}
      </motion.div>

      {/* Add Schedule Button */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm border-dashed border-2 border-blue-200">
          <CardContent className="p-6">
            <motion.div
              whileHover={{ scale: 1.02 }}
              whileTap={{ scale: 0.98 }}
              className="text-center"
            >
              <Button 
                variant="ghost" 
                className="w-full h-16 text-blue-600 hover:bg-blue-50 gap-3 text-base"
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
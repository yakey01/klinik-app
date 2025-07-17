import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Calendar, Clock, MapPin, Plus, ChevronRight, Activity, Edit, X, Filter } from 'lucide-react';

interface JadwalItem {
  id: string;
  tanggal: string;
  waktu: string;
  lokasi: string;
  jenis: 'pagi' | 'siang' | 'malam';
  status: 'scheduled' | 'completed' | 'missed';
}

interface JadwalData {
  jadwal: JadwalItem[];
  stats: {
    scheduled: number;
    completed: number;
    missed: number;
    thisWeek: number;
    thisMonth: number;
  };
  loading: boolean;
}

export function JadwalJaga() {
  const [jadwalData, setJadwalData] = useState<JadwalData>({
    jadwal: [],
    stats: {
      scheduled: 0,
      completed: 0,
      missed: 0,
      thisWeek: 0,
      thisMonth: 0
    },
    loading: true
  });
  const [filter, setFilter] = useState<'all' | 'scheduled' | 'completed' | 'missed'>('all');

  // Fetch jadwal data from API
  useEffect(() => {
    const fetchJadwalData = async () => {
      try {
        const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch('/api/v2/dashboards/dokter/jadwal', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'X-CSRF-TOKEN': token || '',
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          setJadwalData({
            jadwal: data.jadwal || [
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
            ],
            stats: data.stats || {
              scheduled: 3,
              completed: 8,
              missed: 1,
              thisWeek: 5,
              thisMonth: 12
            },
            loading: false
          });
        } else {
          // Fallback data
          setJadwalData({
            jadwal: [
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
            ],
            stats: {
              scheduled: 3,
              completed: 8,
              missed: 1,
              thisWeek: 5,
              thisMonth: 12
            },
            loading: false
          });
        }
      } catch (error) {
        console.error('Failed to fetch jadwal data:', error);
        setJadwalData(prev => ({ ...prev, loading: false }));
      }
    };

    fetchJadwalData();
  }, []);

  const handleEditSchedule = (id: string) => {
    console.log('Edit schedule:', id);
    // TODO: Implement edit functionality
  };

  const handleCancelSchedule = async (id: string) => {
    try {
      const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      const response = await fetch(`/api/v2/dashboards/dokter/jadwal/${id}/cancel`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': token || '',
          'Content-Type': 'application/json'
        }
      });
      
      if (response.ok) {
        setJadwalData(prev => ({
          ...prev,
          jadwal: prev.jadwal.map(item => 
            item.id === id ? { ...item, status: 'missed' as const } : item
          )
        }));
      }
    } catch (error) {
      console.error('Failed to cancel schedule:', error);
    }
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

  const getShiftIcon = (jenis: string) => {
    switch (jenis) {
      case 'pagi': return '☀️';
      case 'siang': return '🌤️';
      case 'malam': return '🌙';
      default: return '⏰';
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

  const filteredJadwal = jadwalData.jadwal.filter(item => 
    filter === 'all' || item.status === filter
  );

  const { stats, loading } = jadwalData;

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      {/* Header Section */}
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
            <Button 
              size="sm" 
              className="bg-white/20 hover:bg-white/30 border-white/30 text-white gap-2 backdrop-blur-sm transition-all hover:scale-105"
            >
              <Plus className="w-4 h-4" />
              Tambah
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Summary Stats */}
      <div className="grid grid-cols-2 gap-4 mb-4">
        <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                <Calendar className="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <p className="text-sm text-blue-600">Minggu Ini</p>
                <p className="text-2xl font-semibold text-blue-700">{stats.thisWeek}</p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-200">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                <Activity className="w-5 h-5 text-emerald-600" />
              </div>
              <div>
                <p className="text-sm text-emerald-600">Bulan Ini</p>
                <p className="text-2xl font-semibold text-emerald-700">{stats.thisMonth}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-3 gap-3">
        <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
          <CardContent className="p-4 text-center">
            <Clock className="w-5 h-5 text-blue-600 mx-auto mb-2" />
            <div className="text-lg font-semibold text-blue-700">{stats.scheduled}</div>
            <div className="text-xs text-blue-600">Dijadwalkan</div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-200">
          <CardContent className="p-4 text-center">
            <Activity className="w-5 h-5 text-emerald-600 mx-auto mb-2" />
            <div className="text-lg font-semibold text-emerald-700">{stats.completed}</div>
            <div className="text-xs text-emerald-600">Selesai</div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-red-50 to-red-100 border-red-200">
          <CardContent className="p-4 text-center">
            <X className="w-5 h-5 text-red-600 mx-auto mb-2" />
            <div className="text-lg font-semibold text-red-700">{stats.missed}</div>
            <div className="text-xs text-red-600">Terlewat</div>
          </CardContent>
        </Card>
      </div>

      {/* Filter Tabs */}
      <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
        <CardContent className="p-4">
          <div className="flex gap-2 overflow-x-auto">
            {[
              { key: 'all', label: 'Semua', count: jadwalData.jadwal.length },
              { key: 'scheduled', label: 'Dijadwalkan', count: stats.scheduled },
              { key: 'completed', label: 'Selesai', count: stats.completed },
              { key: 'missed', label: 'Terlewat', count: stats.missed }
            ].map((tab) => (
              <Button
                key={tab.key}
                variant={filter === tab.key ? "default" : "outline"}
                size="sm"
                onClick={() => setFilter(tab.key as any)}
                className={`whitespace-nowrap transition-all ${
                  filter === tab.key 
                    ? 'bg-blue-600 text-white hover:bg-blue-700' 
                    : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'
                }`}
              >
                {tab.label} ({tab.count})
              </Button>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Schedule List */}
      <div className="space-y-4">
        {filteredJadwal.length === 0 ? (
          <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
            <CardContent className="p-8 text-center">
              <Calendar className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600">Tidak ada jadwal {filter === 'all' ? '' : `yang ${filter}`}</p>
            </CardContent>
          </Card>
        ) : (
          filteredJadwal.map((scheduleItem, index) => (
            <Card 
              key={scheduleItem.id} 
              className="shadow-lg hover:shadow-xl transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm hover:scale-[1.01] hover:-translate-y-1"
            >
              <CardContent className="p-6">
                <div className="flex justify-between items-start mb-4">
                  <div className="flex items-center gap-3">
                    <div className="text-2xl">{getShiftIcon(scheduleItem.jenis)}</div>
                    <div>
                      <h4 className="text-lg font-medium">{formatTanggal(scheduleItem.tanggal)}</h4>
                      <p className="text-sm text-muted-foreground">Shift {scheduleItem.jenis}</p>
                    </div>
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
                    <span className="text-sm flex-1">{scheduleItem.waktu}</span>
                    <Badge className={`${getShiftColor(scheduleItem.jenis)} text-xs`}>
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
                
                {/* Action Buttons for scheduled items */}
                {scheduleItem.status === 'scheduled' && (
                  <div className="flex gap-3 pt-4 mt-4 border-t border-gray-100">
                    <Button 
                      variant="outline" 
                      size="sm" 
                      onClick={() => handleEditSchedule(scheduleItem.id)}
                      className="flex-1 border-blue-200 text-blue-600 hover:bg-blue-50 hover:border-blue-300 gap-2 transition-all hover:scale-105"
                    >
                      <Edit className="w-4 h-4" />
                      Ubah
                    </Button>
                    <Button 
                      variant="outline" 
                      size="sm" 
                      onClick={() => handleCancelSchedule(scheduleItem.id)}
                      className="flex-1 border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300 gap-2 transition-all hover:scale-105"
                    >
                      <X className="w-4 h-4" />
                      Batalkan
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>
          ))
        )}
      </div>

      {/* Add Schedule Button */}
      <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm border-dashed border-2 border-blue-200 hover:border-blue-300 transition-all">
        <CardContent className="p-6">
          <Button 
            variant="ghost" 
            className="w-full h-16 text-blue-600 hover:bg-blue-50 gap-3 text-base transition-all hover:scale-105"
          >
            <Plus className="w-5 h-5" />
            Tambah Jadwal Baru
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
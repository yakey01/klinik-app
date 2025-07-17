import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from './ui/tabs';
import { DollarSign, Calendar, TrendingUp, Eye, Wallet, CreditCard, PiggyBank, FileText, Download } from 'lucide-react';

interface JaspelItem {
  id: string;
  tanggal: string;
  jenis: string;
  jumlah: number;
  status: 'pending' | 'paid' | 'rejected';
  keterangan?: string;
}

interface JaspelData {
  jaspel: JaspelItem[];
  summary: {
    thisMonth: number;
    lastMonth: number;
    pending: number;
    paid: number;
    rejected: number;
    yearToDate: number;
  };
  loading: boolean;
}

export function Jaspel() {
  const [jaspelData, setJaspelData] = useState<JaspelData>({
    jaspel: [],
    summary: {
      thisMonth: 0,
      lastMonth: 0,
      pending: 0,
      paid: 0,
      rejected: 0,
      yearToDate: 0
    },
    loading: true
  });
  const [activeTab, setActiveTab] = useState('overview');

  // Fetch jaspel data from API
  useEffect(() => {
    const fetchJaspelData = async () => {
      try {
        const token = localStorage.getItem('auth_token') || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch('/api/v2/dashboards/dokter/jaspel', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'X-CSRF-TOKEN': token || '',
            'Content-Type': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          setJaspelData({
            jaspel: data.jaspel || [
              {
                id: '1',
                tanggal: '2025-01-16',
                jenis: 'Jaga Malam',
                jumlah: 150000,
                status: 'paid',
                keterangan: 'ICU - 8 jam'
              },
              {
                id: '2',
                tanggal: '2025-01-15',
                jenis: 'Operasi',
                jumlah: 300000,
                status: 'paid',
                keterangan: 'Bedah Umum - 4 jam'
              },
              {
                id: '3',
                tanggal: '2025-01-17',
                jenis: 'Konsultasi',
                jumlah: 75000,
                status: 'pending',
                keterangan: 'Rawat Jalan'
              },
              {
                id: '4',
                tanggal: '2025-01-14',
                jenis: 'Jaga Siang',
                jumlah: 125000,
                status: 'paid',
                keterangan: 'UGD - 8 jam'
              }
            ],
            summary: data.summary || {
              thisMonth: 15500000,
              lastMonth: 14200000,
              pending: 75000,
              paid: 15425000,
              rejected: 0,
              yearToDate: 15500000
            },
            loading: false
          });
        } else {
          // Fallback data
          setJaspelData({
            jaspel: [
              {
                id: '1',
                tanggal: '2025-01-16',
                jenis: 'Jaga Malam',
                jumlah: 150000,
                status: 'paid',
                keterangan: 'ICU - 8 jam'
              },
              {
                id: '2',
                tanggal: '2025-01-15',
                jenis: 'Operasi',
                jumlah: 300000,
                status: 'paid',
                keterangan: 'Bedah Umum - 4 jam'
              },
              {
                id: '3',
                tanggal: '2025-01-17',
                jenis: 'Konsultasi',
                jumlah: 75000,
                status: 'pending',
                keterangan: 'Rawat Jalan'
              },
              {
                id: '4',
                tanggal: '2025-01-14',
                jenis: 'Jaga Siang',
                jumlah: 125000,
                status: 'paid',
                keterangan: 'UGD - 8 jam'
              }
            ],
            summary: {
              thisMonth: 15500000,
              lastMonth: 14200000,
              pending: 75000,
              paid: 15425000,
              rejected: 0,
              yearToDate: 15500000
            },
            loading: false
          });
        }
      } catch (error) {
        console.error('Failed to fetch jaspel data:', error);
        setJaspelData(prev => ({ ...prev, loading: false }));
      }
    };

    fetchJaspelData();
  }, []);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'paid': return 'bg-green-100 text-green-800 border-green-200';
      case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      case 'rejected': return 'bg-red-100 text-red-800 border-red-200';
      default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0
    }).format(amount);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  const { jaspel, summary, loading } = jaspelData;

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  const changePercent = summary.lastMonth > 0 
    ? ((summary.thisMonth - summary.lastMonth) / summary.lastMonth * 100).toFixed(1)
    : '0';

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      {/* Header Section */}
      <Card className="bg-gradient-to-r from-emerald-500 to-emerald-600 border-0 shadow-xl">
        <CardContent className="p-6">
          <div className="flex items-center justify-between text-white">
            <div className="flex items-center gap-3">
              <div className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                <DollarSign className="w-6 h-6" />
              </div>
              <div>
                <h2 className="text-xl text-white">Jaspel</h2>
                <p className="text-emerald-100 text-sm">Jasa Pelayanan Medis</p>
              </div>
            </div>
            <Button 
              size="sm" 
              className="bg-white/20 hover:bg-white/30 border-white/30 text-white gap-2 backdrop-blur-sm transition-all hover:scale-105"
            >
              <Download className="w-4 h-4" />
              Unduh
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Summary Cards */}
      <div className="grid grid-cols-2 gap-4">
        <Card className="bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-200">
          <CardContent className="p-6">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                <Wallet className="w-6 h-6 text-emerald-600" />
              </div>
              <div>
                <p className="text-sm text-emerald-600">Bulan Ini</p>
                <p className="text-2xl font-bold text-emerald-700">
                  {formatCurrency(summary.thisMonth)}
                </p>
              </div>
            </div>
            <div className="flex items-center gap-1 text-sm">
              <TrendingUp className="w-4 h-4 text-emerald-500" />
              <span className="text-emerald-600">+{changePercent}%</span>
              <span className="text-emerald-600">dari bulan lalu</span>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
          <CardContent className="p-6">
            <div className="flex items-center gap-3 mb-3">
              <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <PiggyBank className="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <p className="text-sm text-blue-600">Total Tahun Ini</p>
                <p className="text-2xl font-bold text-blue-700">
                  {formatCurrency(summary.yearToDate)}
                </p>
              </div>
            </div>
            <div className="text-sm text-blue-600">
              Akumulasi dari Januari
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Status Overview */}
      <div className="grid grid-cols-3 gap-3">
        <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200">
          <CardContent className="p-4 text-center">
            <CreditCard className="w-5 h-5 text-green-600 mx-auto mb-2" />
            <div className="text-lg font-semibold text-green-700">{formatCurrency(summary.paid)}</div>
            <div className="text-xs text-green-600">Sudah Dibayar</div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-yellow-50 to-yellow-100 border-yellow-200">
          <CardContent className="p-4 text-center">
            <Calendar className="w-5 h-5 text-yellow-600 mx-auto mb-2" />
            <div className="text-lg font-semibold text-yellow-700">{formatCurrency(summary.pending)}</div>
            <div className="text-xs text-yellow-600">Menunggu</div>
          </CardContent>
        </Card>
        
        <Card className="bg-gradient-to-br from-red-50 to-red-100 border-red-200">
          <CardContent className="p-4 text-center">
            <FileText className="w-5 h-5 text-red-600 mx-auto mb-2" />
            <div className="text-lg font-semibold text-red-700">{formatCurrency(summary.rejected)}</div>
            <div className="text-xs text-red-600">Ditolak</div>
          </CardContent>
        </Card>
      </div>

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="overview">Ringkasan</TabsTrigger>
          <TabsTrigger value="details">Detail</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="space-y-4">
          {/* Monthly Comparison */}
          <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <TrendingUp className="w-5 h-5 text-emerald-600" />
                Perbandingan Bulanan
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex items-center justify-between p-4 bg-emerald-50 rounded-lg">
                  <div>
                    <p className="text-sm text-emerald-600">Bulan Ini</p>
                    <p className="text-lg font-semibold text-emerald-700">{formatCurrency(summary.thisMonth)}</p>
                  </div>
                  <div className="text-right">
                    <Badge className="bg-emerald-100 text-emerald-700">
                      +{changePercent}%
                    </Badge>
                  </div>
                </div>
                
                <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                  <div>
                    <p className="text-sm text-gray-600">Bulan Lalu</p>
                    <p className="text-lg font-semibold text-gray-700">{formatCurrency(summary.lastMonth)}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Recent Transactions */}
          <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <FileText className="w-5 h-5 text-blue-600" />
                Transaksi Terakhir
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {jaspel.slice(0, 3).map((item) => (
                  <div key={item.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                        <DollarSign className="w-5 h-5 text-emerald-600" />
                      </div>
                      <div>
                        <p className="text-sm font-medium">{item.jenis}</p>
                        <p className="text-xs text-gray-600">{formatDate(item.tanggal)}</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-semibold">{formatCurrency(item.jumlah)}</p>
                      <Badge className={`${getStatusColor(item.status)} text-xs`}>
                        {item.status === 'paid' && 'Dibayar'}
                        {item.status === 'pending' && 'Menunggu'}
                        {item.status === 'rejected' && 'Ditolak'}
                      </Badge>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="details" className="space-y-4">
          {jaspel.map((item) => (
            <Card key={item.id} className="shadow-lg hover:shadow-xl transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm hover:scale-[1.01] hover:-translate-y-1">
              <CardContent className="p-6">
                <div className="flex justify-between items-start mb-4">
                  <div>
                    <h4 className="text-lg font-medium">{item.jenis}</h4>
                    <p className="text-sm text-gray-600">{formatDate(item.tanggal)}</p>
                  </div>
                  <Badge className={`${getStatusColor(item.status)}`}>
                    {item.status === 'paid' && 'Dibayar'}
                    {item.status === 'pending' && 'Menunggu'}
                    {item.status === 'rejected' && 'Ditolak'}
                  </Badge>
                </div>
                
                <div className="space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-gray-600">Jumlah:</span>
                    <span className="text-lg font-semibold text-emerald-600">{formatCurrency(item.jumlah)}</span>
                  </div>
                  
                  {item.keterangan && (
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600">Keterangan:</span>
                      <span className="text-sm">{item.keterangan}</span>
                    </div>
                  )}
                </div>
                
                <div className="flex gap-3 pt-4 mt-4 border-t border-gray-100">
                  <Button 
                    variant="outline" 
                    size="sm" 
                    className="flex-1 border-blue-200 text-blue-600 hover:bg-blue-50 hover:border-blue-300 gap-2 transition-all hover:scale-105"
                  >
                    <Eye className="w-4 h-4" />
                    Detail
                  </Button>
                  <Button 
                    variant="outline" 
                    size="sm" 
                    className="flex-1 border-emerald-200 text-emerald-600 hover:bg-emerald-50 hover:border-emerald-300 gap-2 transition-all hover:scale-105"
                  >
                    <Download className="w-4 h-4" />
                    Unduh
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </TabsContent>
      </Tabs>
    </div>
  );
}
import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Badge } from '../ui/badge';
import { Button } from '../ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../ui/tabs';
import { DollarSign, Calendar, TrendingUp, Eye, Wallet, CreditCard, PiggyBank, RefreshCw, AlertCircle } from 'lucide-react';

interface JaspelItem {
  id: string;
  tanggal: string;
  jenis: string;
  jumlah: number;
  status: 'pending' | 'paid' | 'rejected';
  keterangan?: string;
  validated_by?: string;
  validated_at?: string;
}

interface JaspelSummary {
  total_paid: number;
  total_pending: number;
  total_rejected: number;
  count_paid: number;
  count_pending: number;
  count_rejected: number;
}

interface JaspelApiResponse {
  success: boolean;
  message: string;
  data: {
    jaspel_items: JaspelItem[];
    summary: JaspelSummary;
  };
  meta: {
    month: number;
    year: number;
    user_name: string;
  };
}

export function Jaspel() {
  const [jaspelData, setJaspelData] = useState<JaspelItem[]>([]);
  const [summary, setSummary] = useState<JaspelSummary>({
    total_paid: 0,
    total_pending: 0,
    total_rejected: 0,
    count_paid: 0,
    count_pending: 0,
    count_rejected: 0
  });
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [currentMonth] = useState(new Date().getMonth() + 1);
  const [currentYear] = useState(new Date().getFullYear());

  // Fetch Jaspel data from API - WORLD-CLASS implementation for dokter
  const fetchJaspelData = async (month?: number, year?: number) => {
    console.log('🔍 [DOKTER JASPEL DEBUG] Starting fetchJaspelData...', { month, year });
    setIsLoading(true);
    setError(null);
    
    try {
      // Try multiple token sources
      const token = localStorage.getItem('token') || 
                   sessionStorage.getItem('token') || 
                   document.querySelector('meta[name="api-token"]')?.getAttribute('content');
      
      console.log('🔑 [DOKTER JASPEL DEBUG] Token found:', !!token, token ? token.substring(0, 10) + '...' : 'null');

      const params = new URLSearchParams();
      if (month) params.append('month', month.toString());
      if (year) params.append('year', year.toString());

      // Use dokter-specific Jaspel endpoint matching routes/api.php line 319
      const url = `/api/v2/dashboards/dokter/jaspel?${params}`;
      console.log('📡 [DOKTER JASPEL DEBUG] Calling API:', url);

      const headers: any = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      };

      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      console.log('📋 [DOKTER JASPEL DEBUG] Request headers:', headers);

      const response = await fetch(url, {
        method: 'GET',
        headers,
        credentials: 'include', // Include cookies for session auth
      });

      console.log('📊 [DOKTER JASPEL DEBUG] Response status:', response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error('❌ [DOKTER JASPEL DEBUG] API Error:', response.status, errorText);
        throw new Error(`API Error: ${response.status} - ${errorText}`);
      }

      const result: JaspelApiResponse = await response.json();
      console.log('✅ [DOKTER JASPEL DEBUG] API Success:', result);

      if (result.success) {
        setJaspelData(result.data.jaspel_items || []);
        setSummary(result.data.summary || {
          total_paid: 0,
          total_pending: 0,
          total_rejected: 0,
          count_paid: 0,
          count_pending: 0,
          count_rejected: 0
        });
        console.log('💾 [DOKTER JASPEL DEBUG] Data updated:', {
          items: result.data.jaspel_items?.length || 0,
          summary: result.data.summary
        });
      } else {
        throw new Error(result.message || 'Unknown API error');
      }

    } catch (err) {
      console.error('💥 [DOKTER JASPEL DEBUG] Fetch error:', err);
      setError(err instanceof Error ? err.message : 'Gagal memuat data jaspel');
      
      // Set fallback data for development
      setJaspelData([]);
      setSummary({
        total_paid: 0,
        total_pending: 0,
        total_rejected: 0,
        count_paid: 0,
        count_pending: 0,
        count_rejected: 0
      });
    } finally {
      setIsLoading(false);
    }
  };

  // Load data on component mount
  useEffect(() => {
    console.log('🚀 [DOKTER JASPEL DEBUG] Component mounted, fetching data...');
    fetchJaspelData(currentMonth, currentYear);
  }, [currentMonth, currentYear]);

  const totalPending = summary.total_pending;
  const totalPaid = summary.total_paid;

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'bg-gradient-to-r from-yellow-100 to-yellow-200 dark:from-yellow-900/50 dark:to-yellow-800/50 text-yellow-800 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700';
      case 'paid': return 'bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/50 dark:to-green-800/50 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700';
      case 'rejected': return 'bg-gradient-to-r from-red-100 to-red-200 dark:from-red-900/50 dark:to-red-800/50 text-red-800 dark:text-red-300 border-red-200 dark:border-red-700';
      default: return 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200';
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
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

  // Loading state
  if (isLoading) {
    return (
      <div className="space-y-6 theme-transition">
        <Card className="bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 border-0 shadow-xl card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <RefreshCw className="w-6 h-6 animate-spin" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white text-heading-mobile">Jaspel</h2>
                  <p className="text-emerald-100 dark:text-emerald-200 text-sm font-medium text-mobile-friendly">Memuat data jaspel...</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
        <div className="grid grid-cols-2 gap-4">
          <Card className="animate-pulse">
            <CardContent className="p-4 h-20 bg-gray-200 dark:bg-gray-700"></CardContent>
          </Card>
          <Card className="animate-pulse">
            <CardContent className="p-4 h-20 bg-gray-200 dark:bg-gray-700"></CardContent>
          </Card>
        </div>
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="space-y-6 theme-transition">
        <Card className="bg-gradient-to-r from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 border-0 shadow-xl card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <AlertCircle className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white text-heading-mobile">Jaspel</h2>
                  <p className="text-red-100 dark:text-red-200 text-sm font-medium text-mobile-friendly">Error: {error}</p>
                </div>
              </div>
              <Button 
                variant="outline" 
                size="sm" 
                onClick={() => fetchJaspelData(currentMonth, currentYear)}
                className="bg-white/20 border-white/30 text-white hover:bg-white/30"
              >
                <RefreshCw className="w-4 h-4 mr-2" />
                Coba Lagi
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <motion.div 
      variants={container}
      initial="hidden"
      animate="show"
      className="space-y-6 theme-transition"
    >
      {/* Header */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-r from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 border-0 shadow-xl card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <Wallet className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white text-heading-mobile">Jaspel</h2>
                  <p className="text-emerald-100 dark:text-emerald-200 text-sm font-medium text-mobile-friendly">Kelola pendapatan jasa medis</p>
                </div>
              </div>
              <Button 
                variant="outline" 
                size="sm" 
                onClick={() => fetchJaspelData(currentMonth, currentYear)}
                className="bg-white/20 border-white/30 text-white hover:bg-white/30"
                disabled={isLoading}
              >
                <RefreshCw className={`w-4 h-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
                Refresh
              </Button>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Summary Cards */}
      <motion.div variants={item} className="grid grid-cols-2 gap-4">
        <motion.div
          whileHover={{ scale: 1.02, y: -2 }}
          transition={{ duration: 0.2 }}
        >
          <Card className="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 border-0 shadow-lg card-enhanced">
            <CardContent className="p-4">
              <div className="flex items-center gap-3 text-white">
                <div className="w-10 h-10 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <CreditCard className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm text-green-100 dark:text-green-200 font-medium">Total Dibayar</p>
                  <p className="text-lg font-semibold">{formatCurrency(totalPaid)}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>

        <motion.div
          whileHover={{ scale: 1.02, y: -2 }}
          transition={{ duration: 0.2 }}
        >
          <Card className="bg-gradient-to-br from-yellow-500 to-yellow-600 dark:from-yellow-600 dark:to-yellow-700 border-0 shadow-lg card-enhanced">
            <CardContent className="p-4">
              <div className="flex items-center gap-3 text-white">
                <div className="w-10 h-10 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <PiggyBank className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm text-yellow-100 dark:text-yellow-200 font-medium">Pending</p>
                  <p className="text-lg font-semibold">{formatCurrency(totalPending)}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      </motion.div>

      {/* Tabs */}
      <motion.div variants={item}>
        <Tabs defaultValue="semua" className="w-full">
          <TabsList className="grid w-full grid-cols-3 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm border border-gray-200 dark:border-gray-700">
            <TabsTrigger value="semua" className="data-[state=active]:bg-white dark:data-[state=active]:bg-gray-900 data-[state=active]:shadow-sm font-medium transition-colors duration-300">
              Semua
            </TabsTrigger>
            <TabsTrigger value="pending" className="data-[state=active]:bg-white dark:data-[state=active]:bg-gray-900 data-[state=active]:shadow-sm font-medium transition-colors duration-300">
              Pending
            </TabsTrigger>
            <TabsTrigger value="paid" className="data-[state=active]:bg-white dark:data-[state=active]:bg-gray-900 data-[state=active]:shadow-sm font-medium transition-colors duration-300">
              Dibayar
            </TabsTrigger>
          </TabsList>

          <TabsContent value="semua" className="space-y-4 mt-4">
            <motion.div variants={container} className="space-y-3">
              {jaspelData.map((dataItem, index) => (
                <motion.div
                  key={dataItem.id}
                  variants={item}
                  whileHover={{ scale: 1.01, y: -1 }}
                  whileTap={{ scale: 0.99 }}
                  transition={{ duration: 0.2 }}
                >
                  <Card className="shadow-md hover:shadow-lg transition-all duration-300 border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
                    <CardContent className="p-4">
                      <div className="flex justify-between items-start mb-3">
                        <div>
                          <h4 className="text-base font-semibold text-high-contrast">{dataItem.jenis}</h4>
                          <p className="text-sm text-muted-foreground font-medium">{dataItem.keterangan}</p>
                        </div>
                        <Badge className={`${getStatusColor(dataItem.status)} border font-medium`}>
                          {dataItem.status === 'pending' && 'Pending'}
                          {dataItem.status === 'paid' && 'Dibayar'}
                          {dataItem.status === 'rejected' && 'Ditolak'}
                        </Badge>
                      </div>
                      
                      <div className="flex justify-between items-center">
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                            <Calendar className="w-3 h-3 text-blue-600 dark:text-blue-400" />
                          </div>
                          <span className="text-sm text-muted-foreground font-medium">{formatDate(dataItem.tanggal)}</span>
                        </div>
                        <div className="flex items-center gap-3">
                          <span className="text-lg font-semibold text-high-contrast">{formatCurrency(dataItem.jumlah)}</span>
                          <motion.div
                            whileHover={{ scale: 1.1 }}
                            whileTap={{ scale: 0.9 }}
                          >
                            <Button variant="outline" size="sm" className="w-8 h-8 p-0 rounded-full hover:bg-blue-50 dark:hover:bg-blue-950/50 transition-colors duration-300">
                              <Eye className="w-4 h-4" />
                            </Button>
                          </motion.div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </motion.div>
              ))}
            </motion.div>
          </TabsContent>

          <TabsContent value="pending" className="space-y-4 mt-4">
            <motion.div variants={container} className="space-y-3">
              {jaspelData
                .filter(dataItem => dataItem.status === 'pending')
                .map((dataItem, index) => (
                  <motion.div
                    key={dataItem.id}
                    variants={item}
                    whileHover={{ scale: 1.01, y: -1 }}
                    transition={{ duration: 0.2 }}
                  >
                    <Card className="shadow-md hover:shadow-lg transition-all duration-300 border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
                      <CardContent className="p-4">
                        <div className="flex justify-between items-start mb-3">
                          <div>
                            <h4 className="text-base font-semibold text-high-contrast">{dataItem.jenis}</h4>
                            <p className="text-sm text-muted-foreground font-medium">{dataItem.keterangan}</p>
                          </div>
                          <Badge className={`${getStatusColor(dataItem.status)} font-medium`}>Pending</Badge>
                        </div>
                        
                        <div className="flex justify-between items-center">
                          <div className="flex items-center gap-2">
                            <Calendar className="w-4 h-4 text-muted-foreground" />
                            <span className="text-sm font-medium">{formatDate(dataItem.tanggal)}</span>
                          </div>
                          <span className="text-lg font-semibold text-high-contrast">{formatCurrency(dataItem.jumlah)}</span>
                        </div>
                      </CardContent>
                    </Card>
                  </motion.div>
                ))}
            </motion.div>
          </TabsContent>

          <TabsContent value="paid" className="space-y-4 mt-4">
            <motion.div variants={container} className="space-y-3">
              {jaspelData
                .filter(dataItem => dataItem.status === 'paid')
                .map((dataItem, index) => (
                  <motion.div
                    key={dataItem.id}
                    variants={item}
                    whileHover={{ scale: 1.01, y: -1 }}
                    transition={{ duration: 0.2 }}
                  >
                    <Card className="shadow-md hover:shadow-lg transition-all duration-300 border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
                      <CardContent className="p-4">
                        <div className="flex justify-between items-start mb-3">
                          <div>
                            <h4 className="text-base font-semibold text-high-contrast">{dataItem.jenis}</h4>
                            <p className="text-sm text-muted-foreground font-medium">{dataItem.keterangan}</p>
                          </div>
                          <Badge className={`${getStatusColor(dataItem.status)} font-medium`}>Dibayar</Badge>
                        </div>
                        
                        <div className="flex justify-between items-center">
                          <div className="flex items-center gap-2">
                            <Calendar className="w-4 h-4 text-muted-foreground" />
                            <span className="text-sm font-medium">{formatDate(dataItem.tanggal)}</span>
                          </div>
                          <span className="text-lg font-semibold text-high-contrast">{formatCurrency(dataItem.jumlah)}</span>
                        </div>
                      </CardContent>
                    </Card>
                  </motion.div>
                ))}
            </motion.div>
          </TabsContent>
        </Tabs>
      </motion.div>
    </motion.div>
  );
}
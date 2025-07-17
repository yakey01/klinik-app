import { useState } from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from './ui/tabs';
import { DollarSign, Calendar, TrendingUp, Eye, Wallet, CreditCard, PiggyBank } from 'lucide-react';

interface JaspelItem {
  id: string;
  tanggal: string;
  jenis: string;
  jumlah: number;
  status: 'pending' | 'paid' | 'rejected';
  keterangan?: string;
}

export function Jaspel() {
  const [jaspelData] = useState<JaspelItem[]>([
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
    }
  ]);

  const totalPending = jaspelData
    .filter(item => item.status === 'pending')
    .reduce((sum, item) => sum + item.jumlah, 0);

  const totalPaid = jaspelData
    .filter(item => item.status === 'paid')
    .reduce((sum, item) => sum + item.jumlah, 0);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800 border-yellow-200';
      case 'paid': return 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border-green-200';
      case 'rejected': return 'bg-gradient-to-r from-red-100 to-red-200 text-red-800 border-red-200';
      default: return 'bg-gray-100 text-gray-800';
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

  return (
    <motion.div 
      variants={container}
      initial="hidden"
      animate="show"
      className="space-y-6"
    >
      {/* Header */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-r from-emerald-500 to-emerald-600 border-0 shadow-xl">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                  <Wallet className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl text-white">Jaspel</h2>
                  <p className="text-emerald-100 text-sm">Kelola pendapatan jasa medis</p>
                </div>
              </div>
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
          <Card className="bg-gradient-to-br from-green-500 to-green-600 border-0 shadow-lg">
            <CardContent className="p-4">
              <div className="flex items-center gap-3 text-white">
                <div className="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                  <CreditCard className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm text-green-100">Total Dibayar</p>
                  <p className="text-lg">{formatCurrency(totalPaid)}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>

        <motion.div
          whileHover={{ scale: 1.02, y: -2 }}
          transition={{ duration: 0.2 }}
        >
          <Card className="bg-gradient-to-br from-yellow-500 to-yellow-600 border-0 shadow-lg">
            <CardContent className="p-4">
              <div className="flex items-center gap-3 text-white">
                <div className="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                  <PiggyBank className="w-5 h-5" />
                </div>
                <div>
                  <p className="text-sm text-yellow-100">Pending</p>
                  <p className="text-lg">{formatCurrency(totalPending)}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </motion.div>
      </motion.div>

      {/* Tabs */}
      <motion.div variants={item}>
        <Tabs defaultValue="semua" className="w-full">
          <TabsList className="grid w-full grid-cols-3 bg-white/50 backdrop-blur-sm border border-gray-200">
            <TabsTrigger value="semua" className="data-[state=active]:bg-white data-[state=active]:shadow-sm">
              Semua
            </TabsTrigger>
            <TabsTrigger value="pending" className="data-[state=active]:bg-white data-[state=active]:shadow-sm">
              Pending
            </TabsTrigger>
            <TabsTrigger value="paid" className="data-[state=active]:bg-white data-[state=active]:shadow-sm">
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
                  <Card className="shadow-md hover:shadow-lg transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm">
                    <CardContent className="p-4">
                      <div className="flex justify-between items-start mb-3">
                        <div>
                          <h4 className="text-base">{dataItem.jenis}</h4>
                          <p className="text-sm text-muted-foreground">{dataItem.keterangan}</p>
                        </div>
                        <Badge className={`${getStatusColor(dataItem.status)} border`}>
                          {dataItem.status === 'pending' && 'Pending'}
                          {dataItem.status === 'paid' && 'Dibayar'}
                          {dataItem.status === 'rejected' && 'Ditolak'}
                        </Badge>
                      </div>
                      
                      <div className="flex justify-between items-center">
                        <div className="flex items-center gap-2">
                          <div className="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                            <Calendar className="w-3 h-3 text-blue-600" />
                          </div>
                          <span className="text-sm text-muted-foreground">{formatDate(dataItem.tanggal)}</span>
                        </div>
                        <div className="flex items-center gap-3">
                          <span className="text-lg">{formatCurrency(dataItem.jumlah)}</span>
                          <motion.div
                            whileHover={{ scale: 1.1 }}
                            whileTap={{ scale: 0.9 }}
                          >
                            <Button variant="outline" size="sm" className="w-8 h-8 p-0 rounded-full">
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
                    <Card className="shadow-md hover:shadow-lg transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm">
                      <CardContent className="p-4">
                        <div className="flex justify-between items-start mb-3">
                          <div>
                            <h4 className="text-base">{dataItem.jenis}</h4>
                            <p className="text-sm text-muted-foreground">{dataItem.keterangan}</p>
                          </div>
                          <Badge className={getStatusColor(dataItem.status)}>Pending</Badge>
                        </div>
                        
                        <div className="flex justify-between items-center">
                          <div className="flex items-center gap-2">
                            <Calendar className="w-4 h-4 text-muted-foreground" />
                            <span className="text-sm">{formatDate(dataItem.tanggal)}</span>
                          </div>
                          <span className="text-lg">{formatCurrency(dataItem.jumlah)}</span>
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
                    <Card className="shadow-md hover:shadow-lg transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm">
                      <CardContent className="p-4">
                        <div className="flex justify-between items-start mb-3">
                          <div>
                            <h4 className="text-base">{dataItem.jenis}</h4>
                            <p className="text-sm text-muted-foreground">{dataItem.keterangan}</p>
                          </div>
                          <Badge className={getStatusColor(dataItem.status)}>Dibayar</Badge>
                        </div>
                        
                        <div className="flex justify-between items-center">
                          <div className="flex items-center gap-2">
                            <Calendar className="w-4 h-4 text-muted-foreground" />
                            <span className="text-sm">{formatDate(dataItem.tanggal)}</span>
                          </div>
                          <span className="text-lg">{formatCurrency(dataItem.jumlah)}</span>
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
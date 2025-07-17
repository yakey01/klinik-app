import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Badge } from './ui/badge';
import { Separator } from './ui/separator';
import { 
  User, 
  Mail, 
  Phone, 
  MapPin, 
  Calendar, 
  Shield, 
  Settings, 
  Bell, 
  LogOut,
  Edit,
  Award,
  Clock,
  Star,
  Briefcase
} from 'lucide-react';

export function Profil() {
  const userProfile = {
    nama: 'Dr. Ahmad Fauzi',
    email: 'ahmad.fauzi@klinkdokterku.com',
    telefon: '+62 812-3456-7890',
    spesialisasi: 'Dokter Umum',
    rumahSakit: 'KLINIK DOKTERKU',
    nomorSip: 'SIP.123.456.789',
    tanggalBergabung: '2020-01-15',
    status: 'Aktif'
  };

  const stats = {
    totalJam: 2040,
    totalJaspel: 24500000,
    ratingKinerja: 4.8,
    pengalamanTahun: 5
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
        <Card className="bg-gradient-to-r from-teal-500 to-teal-600 border-0 shadow-xl">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                  <User className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl text-white">Profil</h2>
                  <p className="text-teal-100 text-sm">Kelola informasi pribadi</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Profile Header */}
      <motion.div variants={item}>
        <Card className="shadow-xl border-0 bg-gradient-to-br from-white to-blue-50/50 backdrop-blur-sm">
          <CardContent className="p-6">
            <div className="flex items-center space-x-4">
              <motion.div 
                className="relative"
                whileHover={{ scale: 1.05 }}
                transition={{ duration: 0.2 }}
              >
                <div className="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-full flex items-center justify-center shadow-lg">
                  <User className="w-10 h-10" />
                </div>
                <div className="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 border-2 border-white rounded-full flex items-center justify-center">
                  <div className="w-2 h-2 bg-white rounded-full animate-pulse" />
                </div>
              </motion.div>
              <div className="flex-1">
                <h3 className="text-xl">{userProfile.nama}</h3>
                <p className="text-sm text-muted-foreground">{userProfile.spesialisasi}</p>
                <div className="flex items-center gap-2 mt-2">
                  <Badge className="bg-gradient-to-r from-green-100 to-green-200 text-green-800 border-green-200">
                    {userProfile.status}
                  </Badge>
                  <div className="flex items-center gap-1">
                    <Star className="w-4 h-4 text-yellow-500 fill-current" />
                    <span className="text-sm text-muted-foreground">{stats.ratingKinerja}</span>
                  </div>
                </div>
              </div>
              <motion.div
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                <Button variant="outline" size="sm" className="gap-2">
                  <Edit className="w-4 h-4" />
                  Edit
                </Button>
              </motion.div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Personal Information */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Briefcase className="w-5 h-5 text-blue-600" />
              Informasi Personal
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <motion.div 
              className="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 transition-colors duration-200"
              whileHover={{ x: 5 }}
              transition={{ duration: 0.2 }}
            >
              <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <Mail className="w-4 h-4 text-blue-600" />
              </div>
              <span className="text-sm">{userProfile.email}</span>
            </motion.div>
            
            <motion.div 
              className="flex items-center gap-3 p-3 rounded-lg hover:bg-green-50 transition-colors duration-200"
              whileHover={{ x: 5 }}
              transition={{ duration: 0.2 }}
            >
              <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                <Phone className="w-4 h-4 text-green-600" />
              </div>
              <span className="text-sm">{userProfile.telefon}</span>
            </motion.div>
            
            <motion.div 
              className="flex items-center gap-3 p-3 rounded-lg hover:bg-purple-50 transition-colors duration-200"
              whileHover={{ x: 5 }}
              transition={{ duration: 0.2 }}
            >
              <div className="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                <MapPin className="w-4 h-4 text-purple-600" />
              </div>
              <span className="text-sm">{userProfile.rumahSakit}</span>
            </motion.div>
            
            <motion.div 
              className="flex items-center gap-3 p-3 rounded-lg hover:bg-orange-50 transition-colors duration-200"
              whileHover={{ x: 5 }}
              transition={{ duration: 0.2 }}
            >
              <div className="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                <Shield className="w-4 h-4 text-orange-600" />
              </div>
              <span className="text-sm">SIP: {userProfile.nomorSip}</span>
            </motion.div>
            
            <motion.div 
              className="flex items-center gap-3 p-3 rounded-lg hover:bg-teal-50 transition-colors duration-200"
              whileHover={{ x: 5 }}
              transition={{ duration: 0.2 }}
            >
              <div className="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center">
                <Calendar className="w-4 h-4 text-teal-600" />
              </div>
              <span className="text-sm">Bergabung: {formatDate(userProfile.tanggalBergabung)}</span>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Statistics */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Award className="w-5 h-5 text-yellow-600" />
              Statistik Karir
            </CardTitle>
          </CardHeader>
          <CardContent>
            <motion.div variants={container} className="grid grid-cols-2 gap-4">
              <motion.div 
                variants={item}
                className="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border border-blue-200"
                whileHover={{ scale: 1.02, y: -2 }}
                transition={{ duration: 0.2 }}
              >
                <Clock className="w-6 h-6 text-blue-600 mx-auto mb-2" />
                <div className="text-lg text-blue-700">{stats.totalJam.toLocaleString()}</div>
                <div className="text-sm text-muted-foreground">Total Jam Kerja</div>
              </motion.div>
              
              <motion.div 
                variants={item}
                className="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border border-green-200"
                whileHover={{ scale: 1.02, y: -2 }}
                transition={{ duration: 0.2 }}
              >
                <Award className="w-6 h-6 text-green-600 mx-auto mb-2" />
                <div className="text-lg text-green-700">{stats.ratingKinerja}/5</div>
                <div className="text-sm text-muted-foreground">Rating Kinerja</div>
              </motion.div>
              
              <motion.div 
                variants={item}
                className="text-center p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl border border-yellow-200"
                whileHover={{ scale: 1.02, y: -2 }}
                transition={{ duration: 0.2 }}
              >
                <Calendar className="w-6 h-6 text-yellow-600 mx-auto mb-2" />
                <div className="text-lg text-yellow-700">{stats.pengalamanTahun}</div>
                <div className="text-sm text-muted-foreground">Tahun Pengalaman</div>
              </motion.div>
              
              <motion.div 
                variants={item}
                className="text-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border border-purple-200"
                whileHover={{ scale: 1.02, y: -2 }}
                transition={{ duration: 0.2 }}
              >
                <span className="text-2xl">💰</span>
                <div className="text-sm text-purple-700">{formatCurrency(stats.totalJaspel)}</div>
                <div className="text-xs text-muted-foreground">Total Jaspel</div>
              </motion.div>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Settings */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 backdrop-blur-sm">
          <CardHeader>
            <CardTitle>Pengaturan</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-blue-50 hover:border-blue-200">
                <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                  <Settings className="w-4 h-4 text-blue-600" />
                </div>
                Pengaturan Akun
              </Button>
            </motion.div>
            
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-orange-50 hover:border-orange-200">
                <div className="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                  <Bell className="w-4 h-4 text-orange-600" />
                </div>
                Notifikasi
              </Button>
            </motion.div>
            
            <Separator className="my-4" />
            
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 text-red-600 hover:bg-red-50 hover:border-red-200">
                <div className="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                  <LogOut className="w-4 h-4 text-red-600" />
                </div>
                Keluar
              </Button>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>
    </motion.div>
  );
}
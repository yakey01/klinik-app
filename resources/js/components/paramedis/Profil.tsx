import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';
import { Button } from '../ui/button';
import { Badge } from '../ui/badge';
import { Separator } from '../ui/separator';
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

interface ProfilProps {
  userData?: {
    name: string;
    email?: string;
    jabatan?: string;
  };
}

export function Profil({ userData }: ProfilProps) {
  const userProfile = {
    nama: userData?.name || 'Paramedis',
    email: userData?.email || 'paramedis@klinikdokterku.com',
    telefon: '+62 812-3456-7890',
    spesialisasi: userData?.jabatan || 'Paramedis',
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
      className="space-y-6 theme-transition"
    >
      {/* Header */}
      <motion.div variants={item}>
        <Card className="bg-gradient-to-r from-teal-500 to-teal-600 dark:from-teal-600 dark:to-teal-700 border-0 shadow-xl card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center justify-between text-white">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-white/20 dark:bg-white/30 rounded-full flex items-center justify-center">
                  <User className="w-6 h-6" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-white text-heading-mobile">Profil</h2>
                  <p className="text-teal-100 dark:text-teal-200 text-sm font-medium text-mobile-friendly">Kelola informasi pribadi</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Profile Header */}
      <motion.div variants={item}>
        <Card className="shadow-xl border-0 bg-gradient-to-br from-white to-blue-50/50 dark:from-gray-900 dark:to-blue-950/30 backdrop-blur-sm card-enhanced">
          <CardContent className="p-6">
            <div className="flex items-center space-x-4">
              <motion.div 
                className="relative"
                whileHover={{ scale: 1.05 }}
                transition={{ duration: 0.2 }}
              >
                <div className="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 text-white rounded-full flex items-center justify-center shadow-lg">
                  <User className="w-10 h-10" />
                </div>
                <div className="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 dark:bg-green-400 border-2 border-white dark:border-gray-900 rounded-full flex items-center justify-center">
                  <div className="w-2 h-2 bg-white rounded-full animate-pulse" />
                </div>
              </motion.div>
              <div className="flex-1">
                <h3 className="text-xl font-semibold text-high-contrast text-subheading-mobile">{userProfile.nama}</h3>
                <p className="text-sm text-medium-contrast font-medium text-mobile-friendly">{userProfile.spesialisasi}</p>
                <div className="flex items-center gap-2 mt-2">
                  <Badge className="bg-gradient-to-r from-green-100 to-green-200 dark:from-green-900/50 dark:to-green-800/50 text-green-800 dark:text-green-300 border-green-200 dark:border-green-700">
                    {userProfile.status}
                  </Badge>
                  <div className="flex items-center gap-1">
                    <Star className="w-4 h-4 text-yellow-500 dark:text-yellow-400 fill-current" />
                    <span className="text-sm text-medium-contrast font-medium">{stats.ratingKinerja}</span>
                  </div>
                </div>
              </div>
              <motion.div
                whileHover={{ scale: 1.05 }}
                whileTap={{ scale: 0.95 }}
              >
                <Button variant="outline" size="sm" className="gap-2 border-blue-200 dark:border-blue-700 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 font-medium">
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
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-high-contrast">
              <Briefcase className="w-5 h-5 text-blue-600 dark:text-blue-400" />
              Informasi Personal
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <motion.div 
              className="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-950/50 transition-colors duration-200"
              whileHover={{ x: 5 }}
              transition={{ duration: 0.2 }}
            >
              <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                <Mail className="w-4 h-4 text-blue-600 dark:text-blue-400" />
              </div>
              <span className="text-sm text-high-contrast font-medium text-mobile-friendly">{userProfile.email}</span>
            </motion.div>
            
            <motion.div 
              className="flex items-center gap-3 p-3 rounded-lg hover:bg-green-50 dark:hover:bg-green-950/50 transition-colors duration-200"
              whileHover={{ x: 5 }}
              transition={{ duration: 0.2 }}
            >
              <div className="w-8 h-8 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                <Phone className="w-4 h-4 text-green-600 dark:text-green-400" />
              </div>
              <span className="text-sm text-high-contrast font-medium text-mobile-friendly">{userProfile.telefon}</span>
            </motion.div>
            
            <motion.div 
              className="flex items-center gap-3 p-3 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-950/50 transition-colors duration-200"
              whileHover={{ x: 5 }}
              transition={{ duration: 0.2 }}
            >
              <div className="w-8 h-8 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center">
                <MapPin className="w-4 h-4 text-purple-600 dark:text-purple-400" />
              </div>
              <span className="text-sm text-high-contrast font-medium text-mobile-friendly">{userProfile.rumahSakit}</span>
            </motion.div>
            
            <motion.div 
              className="flex items-center gap-3 p-3 rounded-lg hover:bg-orange-50 dark:hover:bg-orange-950/50 transition-colors duration-200"
              whileHover={{ x: 5 }}
              transition={{ duration: 0.2 }}
            >
              <div className="w-8 h-8 bg-orange-100 dark:bg-orange-900/50 rounded-full flex items-center justify-center">
                <Shield className="w-4 h-4 text-orange-600 dark:text-orange-400" />
              </div>
              <span className="text-sm text-high-contrast font-medium text-mobile-friendly">SIP: {userProfile.nomorSip}</span>
            </motion.div>
            
            <motion.div 
              className="flex items-center gap-3 p-3 rounded-lg hover:bg-teal-50 dark:hover:bg-teal-950/50 transition-colors duration-200"
              whileHover={{ x: 5 }}
              transition={{ duration: 0.2 }}
            >
              <div className="w-8 h-8 bg-teal-100 dark:bg-teal-900/50 rounded-full flex items-center justify-center">
                <Calendar className="w-4 h-4 text-teal-600 dark:text-teal-400" />
              </div>
              <span className="text-sm text-high-contrast font-medium text-mobile-friendly">Bergabung: {formatDate(userProfile.tanggalBergabung)}</span>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Statistics */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-high-contrast">
              <Award className="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
              Statistik Karir
            </CardTitle>
          </CardHeader>
          <CardContent>
            <motion.div variants={container} className="grid grid-cols-2 gap-4">
              <motion.div 
                variants={item}
                className="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-950/50 dark:to-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-700"
                whileHover={{ scale: 1.02, y: -2 }}
                transition={{ duration: 0.2 }}
              >
                <Clock className="w-6 h-6 text-blue-600 dark:text-blue-400 mx-auto mb-2" />
                <div className="text-lg font-bold text-blue-700 dark:text-blue-300 text-heading-mobile">{stats.totalJam.toLocaleString()}</div>
                <div className="text-sm text-medium-contrast font-medium text-mobile-friendly">Total Jam Kerja</div>
              </motion.div>
              
              <motion.div 
                variants={item}
                className="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/50 dark:to-green-900/30 rounded-xl border border-green-200 dark:border-green-700"
                whileHover={{ scale: 1.02, y: -2 }}
                transition={{ duration: 0.2 }}
              >
                <Award className="w-6 h-6 text-green-600 dark:text-green-400 mx-auto mb-2" />
                <div className="text-lg font-bold text-green-700 dark:text-green-300 text-heading-mobile">{stats.ratingKinerja}/5</div>
                <div className="text-sm text-medium-contrast font-medium text-mobile-friendly">Rating Kinerja</div>
              </motion.div>
              
              <motion.div 
                variants={item}
                className="text-center p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-950/50 dark:to-yellow-900/30 rounded-xl border border-yellow-200 dark:border-yellow-700"
                whileHover={{ scale: 1.02, y: -2 }}
                transition={{ duration: 0.2 }}
              >
                <Calendar className="w-6 h-6 text-yellow-600 dark:text-yellow-400 mx-auto mb-2" />
                <div className="text-lg font-bold text-yellow-700 dark:text-yellow-300 text-heading-mobile">{stats.pengalamanTahun}</div>
                <div className="text-sm text-medium-contrast font-medium text-mobile-friendly">Tahun Pengalaman</div>
              </motion.div>
              
              <motion.div 
                variants={item}
                className="text-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-950/50 dark:to-purple-900/30 rounded-xl border border-purple-200 dark:border-purple-700"
                whileHover={{ scale: 1.02, y: -2 }}
                transition={{ duration: 0.2 }}
              >
                <span className="text-2xl">💰</span>
                <div className="text-sm font-bold text-purple-700 dark:text-purple-300 text-mobile-friendly">{formatCurrency(stats.totalJaspel)}</div>
                <div className="text-xs text-medium-contrast font-medium">Total Jaspel</div>
              </motion.div>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>

      {/* Settings */}
      <motion.div variants={item}>
        <Card className="shadow-lg border-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm card-enhanced">
          <CardHeader>
            <CardTitle className="text-high-contrast">Pengaturan</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-blue-50 dark:hover:bg-blue-950/50 hover:border-blue-200 dark:hover:border-blue-700 font-medium transition-colors duration-300">
                <div className="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                  <Settings className="w-4 h-4 text-blue-600 dark:text-blue-400" />
                </div>
                <span className="text-high-contrast">Pengaturan Akun</span>
              </Button>
            </motion.div>
            
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 hover:bg-orange-50 dark:hover:bg-orange-950/50 hover:border-orange-200 dark:hover:border-orange-700 font-medium transition-colors duration-300">
                <div className="w-8 h-8 bg-orange-100 dark:bg-orange-900/50 rounded-full flex items-center justify-center">
                  <Bell className="w-4 h-4 text-orange-600 dark:text-orange-400" />
                </div>
                <span className="text-high-contrast">Notifikasi</span>
              </Button>
            </motion.div>
            
            <Separator className="my-4 bg-gray-200 dark:bg-gray-700" />
            
            <motion.div
              whileHover={{ scale: 1.01, x: 5 }}
              whileTap={{ scale: 0.99 }}
              transition={{ duration: 0.2 }}
            >
              <Button variant="outline" className="w-full justify-start gap-3 h-12 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/50 hover:border-red-200 dark:hover:border-red-700 font-medium transition-colors duration-300">
                <div className="w-8 h-8 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center">
                  <LogOut className="w-4 h-4 text-red-600 dark:text-red-400" />
                </div>
                <span>Keluar</span>
              </Button>
            </motion.div>
          </CardContent>
        </Card>
      </motion.div>
    </motion.div>
  );
}
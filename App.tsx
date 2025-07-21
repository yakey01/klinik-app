import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  Calendar, 
  DollarSign, 
  Clock, 
  FileText, 
  User, 
  Bell, 
  LogOut, 
  Stethoscope, 
  ArrowRight, 
  Eye, 
  EyeOff,
  Home
} from 'lucide-react';
import { Dashboard } from './components/Dashboard';
import { JadwalJaga } from './components/JadwalJaga';
import { Jaspel } from './components/Jaspel';
import { Presensi } from './components/Presensi';
import { Laporan } from './components/Laporan';
import { Profil } from './components/Profil';
import { Button } from './components/ui/button';
import { Card, CardContent, CardHeader } from './components/ui/card';
import { Input } from './components/ui/input';
import { Label } from './components/ui/label';

export default function App() {
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [activeTab, setActiveTab] = useState('dashboard');
  const [showPassword, setShowPassword] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [userData, setUserData] = useState<any>(null);

  // Get user data from meta tag
  useEffect(() => {
    const userDataMeta = document.querySelector('meta[name="user-data"]');
    if (userDataMeta) {
      try {
        const data = JSON.parse(userDataMeta.getAttribute('content') || '{}');
        setUserData(data);
        if (data.name) {
          setIsLoggedIn(true); // Auto login if user data exists
        }
      } catch (e) {
        console.error('Error parsing user data:', e);
      }
    }
  }, []);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    
    // Simulate login process
    await new Promise(resolve => setTimeout(resolve, 1500));
    
    setIsLoading(false);
    setIsLoggedIn(true);
  };

  const handleLogout = () => {
    setIsLoggedIn(false);
    setActiveTab('dashboard');
  };

  const handleTabChange = (tabId: string) => {
    setActiveTab(tabId);
  };

  const handleProfileClick = () => {
    setActiveTab('profil');
  };

  // Bottom navigation tabs (Profile removed, moved to header)
  const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: Home, component: Dashboard },
    { id: 'jadwal', label: 'Jadwal', icon: Calendar, component: JadwalJaga },
    { id: 'jaspel', label: 'Jaspel', icon: DollarSign, component: Jaspel },
    { id: 'presensi', label: 'Presensi', icon: Clock, component: Presensi },
    { id: 'laporan', label: 'Laporan', icon: FileText, component: Laporan },
  ];

  // All components including Profile for routing
  const allComponents = [
    ...tabs,
    { id: 'profil', label: 'Profil', icon: User, component: Profil }
  ];

  const ActiveComponent = allComponents.find(tab => tab.id === activeTab)?.component || Dashboard;

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

  if (!isLoggedIn) {
    return (
      <motion.div
        initial={{ opacity: 0, x: -100 }}
        animate={{ opacity: 1, x: 0 }}
        exit={{ opacity: 0, x: 100 }}
        transition={{ duration: 0.5, ease: "easeInOut" }}
        className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-blue-50 flex flex-col max-w-md mx-auto relative overflow-hidden"
      >
        {/* Background Pattern */}
        <div className="absolute inset-0 bg-gradient-to-br from-blue-600/5 via-transparent to-blue-600/5 pointer-events-none" />
        <div className="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-200/20 to-purple-200/20 rounded-full -translate-y-32 translate-x-32" />
        <div className="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr from-green-200/20 to-blue-200/20 rounded-full translate-y-24 -translate-x-24" />

        <motion.div
          variants={container}
          initial="hidden"
          animate="show"
          className="flex-1 p-6 relative z-10 flex flex-col justify-center"
        >
          {/* Logo and Branding */}
          <motion.div 
            variants={item}
            className="text-center mb-12"
          >
            <motion.div
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              transition={{ delay: 0.3, duration: 0.6, type: "spring" }}
              className="w-24 h-24 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl mx-auto mb-8 flex items-center justify-center shadow-xl"
            >
              <Stethoscope className="w-12 h-12 text-white" />
            </motion.div>
            
            <motion.h1 
              className="text-3xl bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent tracking-wide mb-3"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: 0.4, duration: 0.6 }}
            >
              KLINIK DOKTERKU
            </motion.h1>
            
            <motion.p 
              className="text-muted-foreground text-lg"
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ delay: 0.5, duration: 0.6 }}
            >
              Sahabat Menuju Sehat
            </motion.p>
          </motion.div>

          {/* Login Form */}
          <motion.div variants={item} className="mb-8">
            <Card className="shadow-2xl border-0 bg-white/80 backdrop-blur-sm">
              <CardHeader className="pb-4">
                <motion.h2 
                  className="text-center"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: 0.6 }}
                >
                  Masuk ke Akun Anda
                </motion.h2>
              </CardHeader>
              <CardContent className="space-y-6">
                <form onSubmit={handleLogin} className="space-y-4">
                  <motion.div
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: 0.7 }}
                  >
                    <Label htmlFor="email">Email atau Username</Label>
                    <Input
                      id="email"
                      type="text"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="dr.ahmad atau ahmad@klinikdokterku.com"
                      className="mt-2 h-12 bg-white/70 border-gray-200 focus:border-blue-400 focus:ring-blue-400/20"
                      required
                    />
                  </motion.div>

                  <motion.div
                    initial={{ opacity: 0, x: -20 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ delay: 0.8 }}
                  >
                    <Label htmlFor="password">Password</Label>
                    <div className="relative mt-2">
                      <Input
                        id="password"
                        type={showPassword ? 'text' : 'password'}
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        placeholder="Masukkan password"
                        className="h-12 bg-white/70 border-gray-200 focus:border-blue-400 focus:ring-blue-400/20 pr-12"
                        required
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="absolute right-2 top-1/2 -translate-y-1/2 h-8 w-8 p-0"
                        onClick={() => setShowPassword(!showPassword)}
                      >
                        {showPassword ? (
                          <EyeOff className="w-4 h-4" />
                        ) : (
                          <Eye className="w-4 h-4" />
                        )}
                      </Button>
                    </div>
                  </motion.div>

                  <motion.div
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.9 }}
                    className="pt-2"
                  >
                    <Button
                      type="submit"
                      disabled={isLoading}
                      className="w-full h-12 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white shadow-lg text-base"
                    >
                      {isLoading ? (
                        <motion.div
                          className="flex items-center gap-2"
                          initial={{ opacity: 0 }}
                          animate={{ opacity: 1 }}
                        >
                          <motion.div
                            className="w-4 h-4 border-2 border-white border-t-transparent rounded-full"
                            animate={{ rotate: 360 }}
                            transition={{ duration: 1, repeat: Infinity, ease: "linear" }}
                          />
                          Masuk...
                        </motion.div>
                      ) : (
                        <div className="flex items-center gap-2">
                          Masuk
                          <ArrowRight className="w-4 h-4" />
                        </div>
                      )}
                    </Button>
                  </motion.div>
                </form>

                <motion.div
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: 1.0 }}
                  className="text-center"
                >
                  <Button variant="ghost" className="text-sm text-blue-600 hover:text-blue-700 hover:bg-blue-50">
                    Lupa password?
                  </Button>
                </motion.div>
              </CardContent>
            </Card>
          </motion.div>

          {/* Footer */}
          <motion.div
            variants={item}
            className="text-center mt-8 pt-6 border-t border-gray-200"
          >
            <p className="text-xs text-muted-foreground">
              © 2025 Klinik Dokterku. Semua hak dilindungi.
            </p>
            <p className="text-xs text-muted-foreground mt-1">
              Versi 1.0.0
            </p>
          </motion.div>
        </motion.div>
      </motion.div>
    );
  }

  return (
    <motion.div
      initial={{ opacity: 0, x: 100 }}
      animate={{ opacity: 1, x: 0 }}
      exit={{ opacity: 0, x: -100 }}
      transition={{ duration: 0.5, ease: "easeInOut" }}
      className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-blue-50 flex flex-col max-w-md mx-auto relative overflow-hidden"
    >
      {/* Background Pattern */}
      <div className="absolute inset-0 bg-gradient-to-br from-blue-600/5 via-transparent to-blue-600/5 pointer-events-none" />
      
      {/* Header */}
      <motion.header 
        initial={{ y: -100, opacity: 0 }}
        animate={{ y: 0, opacity: 1 }}
        transition={{ duration: 0.6, ease: "easeOut" }}
        className="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 shadow-xl relative overflow-hidden"
      >
        {/* Header Background Pattern */}
        <div className="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-transparent" />
        <div className="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-16 translate-x-16" />
        <div className="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-12 -translate-x-12" />
        
        <div className="relative z-10">
          <div className="flex items-center justify-between mb-4">
            <motion.div
              initial={{ scale: 0 }}
              animate={{ scale: 1 }}
              transition={{ delay: 0.3, duration: 0.5, type: "spring" }}
            >
              <h1 className="text-2xl tracking-wide">KLINIK DOKTERKU</h1>
            </motion.div>
            <div className="flex gap-3">
              {/* Profile Button (moved from bottom nav) */}
              <motion.div
                whileTap={{ scale: 0.95 }}
                whileHover={{ scale: 1.05 }}
              >
                <Button 
                  variant="ghost" 
                  size="sm" 
                  onClick={handleProfileClick}
                  className={`rounded-full p-2 transition-all duration-200 ${
                    activeTab === 'profil' 
                      ? 'text-white bg-white/30' 
                      : 'text-white hover:bg-white/20'
                  }`}
                  title="Profil"
                >
                  <User className="w-5 h-5" />
                </Button>
              </motion.div>
              
              {/* Notification Button */}
              <motion.div
                whileTap={{ scale: 0.95 }}
                whileHover={{ scale: 1.05 }}
              >
                <Button variant="ghost" size="sm" className="text-white hover:bg-white/20 rounded-full p-2 relative">
                  <Bell className="w-5 h-5" />
                  <div className="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse" />
                </Button>
              </motion.div>
              
              {/* Logout Button */}
              <motion.div
                whileTap={{ scale: 0.95 }}
                whileHover={{ scale: 1.05 }}
              >
                <Button 
                  variant="ghost" 
                  size="sm" 
                  onClick={handleLogout}
                  className="text-white hover:bg-white/20 rounded-full p-2"
                  title="Keluar"
                >
                  <LogOut className="w-5 h-5" />
                </Button>
              </motion.div>
            </div>
          </div>
          
          <motion.div
            initial={{ x: -50, opacity: 0 }}
            animate={{ x: 0, opacity: 1 }}
            transition={{ delay: 0.4, duration: 0.5 }}
            className="flex items-center gap-3"
          >
            <div className="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
              <User className="w-6 h-6 text-white" />
            </div>
            <div>
              <p className="text-white/90 text-sm">Selamat datang,</p>
              <p className="text-white">{userData?.name || 'Dokter'}</p>
            </div>
          </motion.div>
        </div>
      </motion.header>

      {/* Main Content */}
      <main className="flex-1 p-4 pb-32 relative z-10">
        <AnimatePresence mode="wait">
          <motion.div
            key={activeTab}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -20 }}
            transition={{ duration: 0.3, ease: "easeInOut" }}
          >
            <ActiveComponent userData={userData} />
          </motion.div>
        </AnimatePresence>
      </main>

      {/* Bottom Navigation - Profile removed */}
      <motion.nav 
        initial={{ y: 100, opacity: 0 }}
        animate={{ y: 0, opacity: 1 }}
        transition={{ duration: 0.6, delay: 0.2, ease: "easeOut" }}
        className="fixed bottom-0 left-1/2 transform -translate-x-1/2 w-full max-w-md z-50"
      >
        <Card className="m-4 shadow-2xl border-0 bg-white/95 backdrop-blur-lg">
          <CardContent className="p-2">
            <div className="flex justify-around items-center">
              {tabs.map((tab, index) => {
                const Icon = tab.icon;
                const isActive = activeTab === tab.id;
                
                return (
                  <motion.button
                    key={tab.id}
                    onClick={() => handleTabChange(tab.id)}
                    className="relative flex flex-col items-center p-3 rounded-xl transition-all duration-200 min-w-0 flex-1"
                    whileTap={{ scale: 0.95 }}
                    whileHover={{ scale: 1.02 }}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: index * 0.1 + 0.3, duration: 0.4 }}
                  >
                    {/* Active Background */}
                    <AnimatePresence>
                      {isActive && (
                        <motion.div
                          layoutId="activeTab"
                          className="absolute inset-0 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-lg"
                          initial={{ opacity: 0, scale: 0.8 }}
                          animate={{ opacity: 1, scale: 1 }}
                          exit={{ opacity: 0, scale: 0.8 }}
                          transition={{ duration: 0.2, ease: "easeInOut" }}
                        />
                      )}
                    </AnimatePresence>
                    
                    {/* Icon and Label */}
                    <div className="relative z-10 flex flex-col items-center">
                      <motion.div
                        animate={{ 
                          scale: isActive ? 1.1 : 1,
                          y: isActive ? -2 : 0
                        }}
                        transition={{ duration: 0.2 }}
                      >
                        <Icon 
                          className={`w-5 h-5 mb-1 transition-colors duration-200 ${
                            isActive ? 'text-white' : 'text-gray-600'
                          }`} 
                        />
                      </motion.div>
                      <motion.span 
                        className={`text-xs transition-colors duration-200 text-center ${
                          isActive ? 'text-white' : 'text-gray-600'
                        }`}
                        animate={{ 
                          scale: isActive ? 1.05 : 1
                        }}
                        transition={{ duration: 0.2 }}
                      >
                        {tab.label}
                      </motion.span>
                    </div>
                  </motion.button>
                );
              })}
            </div>
          </CardContent>
        </Card>
      </motion.nav>
    </motion.div>
  );
}
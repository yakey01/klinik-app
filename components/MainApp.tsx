import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Calendar, DollarSign, Clock, FileText, User, Bell, Search, LogOut } from 'lucide-react';
import { JadwalJaga } from './JadwalJaga';
import { Jaspel } from './Jaspel';
import { Presensi } from './Presensi';
import { Laporan } from './Laporan';
import { Profil } from './Profil';
import { Button } from './ui/button';
import { Card, CardContent } from './ui/card';

interface MainAppProps {
  onLogout: () => void;
}

export function MainApp({ onLogout }: MainAppProps) {
  const [activeTab, setActiveTab] = useState('presensi');

  const tabs = [
    { id: 'jadwal', label: 'Jadwal', icon: Calendar, component: JadwalJaga },
    { id: 'jaspel', label: 'Jaspel', icon: DollarSign, component: Jaspel },
    { id: 'presensi', label: 'Presensi', icon: Clock, component: Presensi },
    { id: 'laporan', label: 'Laporan', icon: FileText, component: Laporan },
    { id: 'profil', label: 'Profil', icon: User, component: Profil },
  ];

  const ActiveComponent = tabs.find(tab => tab.id === activeTab)?.component || Presensi;

  const handleTabChange = (tabId: string) => {
    console.log('Switching to tab:', tabId);
    setActiveTab(tabId);
  };

  return (
    <motion.div 
      initial={{ opacity: 0, scale: 0.95 }}
      animate={{ opacity: 1, scale: 1 }}
      transition={{ duration: 0.5 }}
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
              <motion.div
                whileTap={{ scale: 0.95 }}
                whileHover={{ scale: 1.05 }}
              >
                <Button variant="ghost" size="sm" className="text-white hover:bg-white/20 rounded-full p-2">
                  <Search className="w-5 h-5" />
                </Button>
              </motion.div>
              <motion.div
                whileTap={{ scale: 0.95 }}
                whileHover={{ scale: 1.05 }}
              >
                <Button variant="ghost" size="sm" className="text-white hover:bg-white/20 rounded-full p-2 relative">
                  <Bell className="w-5 h-5" />
                  <div className="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse" />
                </Button>
              </motion.div>
              <motion.div
                whileTap={{ scale: 0.95 }}
                whileHover={{ scale: 1.05 }}
              >
                <Button 
                  variant="ghost" 
                  size="sm" 
                  onClick={onLogout}
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
              <p className="text-white">Dr. Ahmad Fauzi</p>
            </div>
          </motion.div>
        </div>
      </motion.header>

      {/* Main Content */}
      <main className="flex-1 p-4 pb-28 relative z-10">
        <AnimatePresence mode="wait">
          <motion.div
            key={activeTab}
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -20 }}
            transition={{ duration: 0.3, ease: "easeInOut" }}
          >
            <ActiveComponent />
          </motion.div>
        </AnimatePresence>
      </main>

      {/* Bottom Navigation */}
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
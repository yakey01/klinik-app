import { useState } from 'react';
import { motion } from 'framer-motion';
import { Card, CardContent, CardHeader } from './ui/card';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Label } from './ui/label';
import { Eye, EyeOff, Stethoscope, Shield, Users, Award, ArrowRight } from 'lucide-react';

interface LoginProps {
  onLogin: () => void;
}

export function Login({ onLogin }: LoginProps) {
  const [showPassword, setShowPassword] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    
    // Simulate login process
    await new Promise(resolve => setTimeout(resolve, 1500));
    
    setIsLoading(false);
    onLogin();
  };

  const features = [
    {
      icon: Stethoscope,
      title: 'Kelola Jadwal Jaga',
      description: 'Atur dan pantau jadwal kerja dengan mudah'
    },
    {
      icon: Shield,
      title: 'Presensi Digital',
      description: 'Check-in dan check-out secara real-time'
    },
    {
      icon: Users,
      title: 'Tracking Jaspel',
      description: 'Pantau pendapatan dan fee layanan medis'
    },
    {
      icon: Award,
      title: 'Laporan Lengkap',
      description: 'Analisis kinerja dan statistik kehadiran'
    }
  ];

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
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-blue-50 flex flex-col max-w-md mx-auto relative overflow-hidden">
      {/* Background Pattern */}
      <div className="absolute inset-0 bg-gradient-to-br from-blue-600/5 via-transparent to-blue-600/5 pointer-events-none" />
      <div className="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-200/20 to-purple-200/20 rounded-full -translate-y-32 translate-x-32" />
      <div className="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr from-green-200/20 to-blue-200/20 rounded-full translate-y-24 -translate-x-24" />

      <motion.div
        variants={container}
        initial="hidden"
        animate="show"
        className="flex-1 p-6 relative z-10"
      >
        {/* Logo and Branding */}
        <motion.div 
          variants={item}
          className="text-center mb-12 mt-8"
        >
          <motion.div
            initial={{ scale: 0 }}
            animate={{ scale: 1 }}
            transition={{ delay: 0.3, duration: 0.6, type: "spring" }}
            className="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl mx-auto mb-6 flex items-center justify-center shadow-xl"
          >
            <Stethoscope className="w-10 h-10 text-white" />
          </motion.div>
          
          <motion.h1 
            className="text-3xl bg-gradient-to-r from-blue-600 to-blue-700 bg-clip-text text-transparent tracking-wide mb-2"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4, duration: 0.6 }}
          >
            KLINIK DOKTERKU
          </motion.h1>
          
          <motion.p 
            className="text-muted-foreground"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.5, duration: 0.6 }}
          >
            Platform manajemen tenaga kesehatan modern
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
                  <Label htmlFor="email">Email atau ID Pegawai</Label>
                  <Input
                    id="email"
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="dr.ahmad@klinikdokterku.com"
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

        {/* Features Section */}
        <motion.div variants={item}>
          <h3 className="text-center mb-6 text-muted-foreground">
            Fitur Unggulan
          </h3>
          <motion.div 
            variants={container}
            className="grid grid-cols-2 gap-4"
          >
            {features.map((feature, index) => {
              const Icon = feature.icon;
              return (
                <motion.div
                  key={index}
                  variants={item}
                  whileHover={{ scale: 1.02, y: -2 }}
                  transition={{ duration: 0.2 }}
                >
                  <Card className="p-4 border-0 bg-white/60 backdrop-blur-sm hover:bg-white/80 transition-all duration-300">
                    <CardContent className="p-0 text-center">
                      <div className="w-12 h-12 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl mx-auto mb-3 flex items-center justify-center">
                        <Icon className="w-6 h-6 text-blue-600" />
                      </div>
                      <h4 className="text-sm mb-1">{feature.title}</h4>
                      <p className="text-xs text-muted-foreground leading-relaxed">
                        {feature.description}
                      </p>
                    </CardContent>
                  </Card>
                </motion.div>
              );
            })}
          </motion.div>
        </motion.div>

        {/* Footer */}
        <motion.div
          variants={item}
          className="text-center mt-12 pt-6 border-t border-gray-200"
        >
          <p className="text-xs text-muted-foreground">
            © 2025 Klinik Dokterku. Semua hak dilindungi.
          </p>
          <p className="text-xs text-muted-foreground mt-1">
            Versi 1.0.0
          </p>
        </motion.div>
      </motion.div>
    </div>
  );
}
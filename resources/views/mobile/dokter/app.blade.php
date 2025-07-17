<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KLINIK DOKTERKU - {{ auth()->user()->name ?? 'Dokter' }}</title>
    
    <!-- React and Dependencies -->
    <script src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/framer-motion@11.0.24/dist/framer-motion.js"></script>
    <script src="https://unpkg.com/lucide-react@latest/dist/umd/lucide-react.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .app-container {
            min-height: 100vh;
            background: linear-gradient(to bottom right, #dbeafe, #ffffff, #dbeafe);
            max-width: 448px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        
        .bg-pattern {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom right, rgba(59, 130, 246, 0.05), rgba(0, 0, 0, 0), rgba(59, 130, 246, 0.05));
            pointer-events: none;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(to right, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 15px 35px -5px rgba(59, 130, 246, 0.5);
        }
        
        .btn-ghost {
            background: transparent;
            color: #6b7280;
            border: 1px solid #e5e7eb;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-ghost:hover {
            background: rgba(59, 130, 246, 0.05);
            border-color: rgba(59, 130, 246, 0.2);
        }
        
        .input-field {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(4px);
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .input-field:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(255, 255, 255, 0.9);
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.75rem;
            border-radius: 0.75rem;
            transition: all 0.2s;
            min-width: 0;
            flex: 1;
            position: relative;
        }
        
        .nav-item.active {
            background: linear-gradient(to right, #3b82f6, #2563eb);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
        }
        
        .nav-item:not(.active) {
            color: #6b7280;
        }
        
        .nav-item:not(.active):hover {
            background: rgba(59, 130, 246, 0.05);
            color: #3b82f6;
            transform: translateY(-1px);
        }
        
        .spinner {
            width: 1rem;
            height: 1rem;
            border: 2px solid white;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(100px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div id="app"></div>

    <script type="text/babel">
        const { useState, useEffect } = React;
        const { motion, AnimatePresence } = Motion;
        const { 
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
            Home,
            Activity,
            MapPin,
            TrendingUp,
            CheckCircle,
            Award,
            Timer
        } = lucide;

        // Dashboard Component
        function Dashboard() {
            const [currentTime, setCurrentTime] = useState(new Date());
            const [jadwalMendatang] = useState([
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
                }
            ]);

            useEffect(() => {
                const timer = setInterval(() => {
                    setCurrentTime(new Date());
                }, 1000);
                return () => clearInterval(timer);
            }, []);

            const getShiftColor = (jenis) => {
                switch (jenis) {
                    case 'pagi': return 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-white';
                    case 'siang': return 'bg-gradient-to-r from-orange-400 to-orange-500 text-white';
                    case 'malam': return 'bg-gradient-to-r from-purple-400 to-purple-500 text-white';
                    default: return 'bg-gray-100 text-gray-800';
                }
            };

            const formatTanggal = (tanggal) => {
                return new Date(tanggal).toLocaleDateString('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long'
                });
            };

            const getShiftIcon = (jenis) => {
                switch (jenis) {
                    case 'pagi': return '☀️';
                    case 'siang': return '🌤️';
                    case 'malam': return '🌙';
                    default: return '⏰';
                }
            };

            const stats = {
                attendance: { current: 85, target: 90, change: +5 },
                performance: { score: 92, change: +3 },
                jaspel: { thisMonth: 15500000, lastMonth: 14200000, change: +9.2 }
            };

            return (
                <div className="space-y-6 fade-in">
                    {/* Welcome Card */}
                    <div className="card p-6 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                <Activity size={24} />
                            </div>
                            <div>
                                <h2 className="text-xl">Dashboard</h2>
                                <p className="text-blue-100 text-sm">Selamat datang kembali, {{ auth()->user()->name ?? 'Dr. Ahmad' }}</p>
                            </div>
                        </div>
                        
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-blue-100 text-sm">Waktu Sekarang</p>
                                <p className="text-lg">
                                    {currentTime.toLocaleTimeString('id-ID', { 
                                        hour: '2-digit', 
                                        minute: '2-digit' 
                                    })}
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-blue-100 text-sm">
                                    {currentTime.toLocaleDateString('id-ID', {
                                        weekday: 'long',
                                        day: 'numeric',
                                        month: 'long'
                                    })}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Next Schedule */}
                    <div className="card p-6">
                        <div className="flex items-center justify-between mb-4">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <Calendar size={20} className="text-blue-600" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-medium">Jadwal Jaga Berikutnya</h3>
                                    <p className="text-sm text-gray-600">Shift yang akan datang</p>
                                </div>
                            </div>
                            <span className="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-sm border border-blue-200">
                                Segera
                            </span>
                        </div>

                        {jadwalMendatang[0] && (
                            <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-5 text-white relative overflow-hidden">
                                <div className="absolute top-0 right-0 w-24 h-24 bg-white bg-opacity-10 rounded-full transform -translate-y-12 translate-x-12"></div>
                                
                                <div className="relative z-10">
                                    <div className="flex items-center justify-between mb-4">
                                        <div className="flex items-center gap-3">
                                            <div className="text-2xl">{getShiftIcon(jadwalMendatang[0].jenis)}</div>
                                            <div>
                                                <h4 className="text-lg">{formatTanggal(jadwalMendatang[0].tanggal)}</h4>
                                                <p className="text-blue-100 text-sm">Shift {jadwalMendatang[0].jenis}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="space-y-3">
                                        <div className="flex items-center gap-3">
                                            <Clock size={16} className="text-blue-200" />
                                            <span className="text-sm text-blue-100">{jadwalMendatang[0].waktu}</span>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <MapPin size={16} className="text-blue-200" />
                                            <span className="text-sm text-blue-100">{jadwalMendatang[0].lokasi}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Performance Stats */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="card p-5">
                            <div className="flex items-center gap-3 mb-3">
                                <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <CheckCircle size={20} className="text-green-600" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium">Tingkat Kehadiran</h4>
                                    <p className="text-2xl font-semibold text-green-600">{stats.attendance.current}%</p>
                                </div>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2 mb-2">
                                <div className="bg-green-500 h-2 rounded-full" style={{width: `${stats.attendance.current}%`}}></div>
                            </div>
                            <div className="flex items-center gap-1 text-xs">
                                <TrendingUp size={12} className="text-green-500" />
                                <span className="text-green-600">+{stats.attendance.change}%</span>
                                <span className="text-gray-600">dari bulan lalu</span>
                            </div>
                        </div>

                        <div className="card p-5">
                            <div className="flex items-center gap-3 mb-3">
                                <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <Award size={20} className="text-blue-600" />
                                </div>
                                <div>
                                    <h4 className="text-sm font-medium">Skor Kinerja</h4>
                                    <p className="text-2xl font-semibold text-blue-600">{stats.performance.score}</p>
                                </div>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2 mb-2">
                                <div className="bg-blue-500 h-2 rounded-full" style={{width: `${stats.performance.score}%`}}></div>
                            </div>
                            <div className="flex items-center gap-1 text-xs">
                                <TrendingUp size={12} className="text-blue-500" />
                                <span className="text-blue-600">+{stats.performance.change}</span>
                                <span className="text-gray-600">poin minggu ini</span>
                            </div>
                        </div>
                    </div>

                    {/* Jaspel Summary */}
                    <div className="card p-6">
                        <div className="flex items-center justify-between mb-4">
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <DollarSign size={20} className="text-green-600" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-medium">Jaspel Bulan Ini</h3>
                                    <p className="text-sm text-gray-600">Pendapatan layanan medis</p>
                                </div>
                            </div>
                            <div className="text-right">
                                <p className="text-2xl font-semibold text-green-600">
                                    Rp {stats.jaspel.thisMonth.toLocaleString('id-ID')}
                                </p>
                                <div className="flex items-center gap-1 text-xs">
                                    <TrendingUp size={12} className="text-green-500" />
                                    <span className="text-green-600">+{stats.jaspel.change}%</span>
                                </div>
                            </div>
                        </div>
                        <div className="bg-green-50 rounded-lg p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-green-700">Bulan Lalu</span>
                                <span className="text-sm text-green-600">
                                    Rp {stats.jaspel.lastMonth.toLocaleString('id-ID')}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="card p-6">
                        <h3 className="flex items-center gap-2 text-lg font-medium mb-4">
                            <Timer size={20} className="text-blue-600" />
                            Aksi Cepat
                        </h3>
                        <div className="space-y-3">
                            <button className="w-full flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-all">
                                <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <Clock size={16} className="text-blue-600" />
                                </div>
                                <span className="text-gray-700">Check In/Out Sekarang</span>
                            </button>
                            
                            <button className="w-full flex items-center gap-3 p-3 bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg transition-all">
                                <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <Calendar size={16} className="text-green-600" />
                                </div>
                                <span className="text-gray-700">Lihat Jadwal Minggu Ini</span>
                            </button>

                            <button className="w-full flex items-center gap-3 p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg transition-all">
                                <div className="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <Activity size={16} className="text-purple-600" />
                                </div>
                                <span className="text-gray-700">Lihat Laporan Kinerja</span>
                            </button>
                        </div>
                    </div>
                </div>
            );
        }

        // Simple placeholder components for other tabs
        function JadwalJaga() {
            return (
                <div className="card p-6 fade-in">
                    <h2 className="text-xl font-semibold mb-4">Jadwal Jaga</h2>
                    <p className="text-gray-600">Fitur jadwal jaga akan segera hadir...</p>
                </div>
            );
        }

        function Jaspel() {
            return (
                <div className="card p-6 fade-in">
                    <h2 className="text-xl font-semibold mb-4">Jaspel</h2>
                    <p className="text-gray-600">Fitur jaspel akan segera hadir...</p>
                </div>
            );
        }

        function Presensi() {
            return (
                <div className="card p-6 fade-in">
                    <h2 className="text-xl font-semibold mb-4">Presensi</h2>
                    <p className="text-gray-600">Fitur presensi akan segera hadir...</p>
                </div>
            );
        }

        function Laporan() {
            return (
                <div className="card p-6 fade-in">
                    <h2 className="text-xl font-semibold mb-4">Laporan</h2>
                    <p className="text-gray-600">Fitur laporan akan segera hadir...</p>
                </div>
            );
        }

        function Profil() {
            return (
                <div className="card p-6 fade-in">
                    <h2 className="text-xl font-semibold mb-4">Profil</h2>
                    <p className="text-gray-600">Fitur profil akan segera hadir...</p>
                </div>
            );
        }

        // Main App Component
        function App() {
            const [isLoggedIn, setIsLoggedIn] = useState(true); // Auto-login for authenticated users
            const [activeTab, setActiveTab] = useState('dashboard');
            const [showPassword, setShowPassword] = useState(false);
            const [email, setEmail] = useState('');
            const [password, setPassword] = useState('');
            const [isLoading, setIsLoading] = useState(false);

            const handleLogin = async (e) => {
                e.preventDefault();
                setIsLoading(true);
                
                // Simulate login process
                await new Promise(resolve => setTimeout(resolve, 1500));
                
                setIsLoading(false);
                setIsLoggedIn(true);
            };

            const handleLogout = () => {
                window.location.href = '/logout';
            };

            const handleTabChange = (tabId) => {
                setActiveTab(tabId);
            };

            const handleProfileClick = () => {
                setActiveTab('profil');
            };

            // Bottom navigation tabs
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

            return (
                <div className="app-container">
                    <div className="bg-pattern"></div>
                    
                    {/* Header */}
                    <header className="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-6 shadow-xl relative overflow-hidden">
                        {/* Header Background Pattern */}
                        <div className="absolute inset-0 bg-gradient-to-r from-blue-600 from-opacity-20 to-transparent"></div>
                        <div className="absolute top-0 right-0 w-32 h-32 bg-white bg-opacity-10 rounded-full transform -translate-y-16 translate-x-16"></div>
                        
                        <div className="relative z-10">
                            <div className="flex items-center justify-between mb-4">
                                <h1 className="text-2xl font-semibold tracking-wide">KLINIK DOKTERKU</h1>
                                <div className="flex gap-3">
                                    {/* Profile Button */}
                                    <button 
                                        onClick={handleProfileClick}
                                        className={`p-2 rounded-full transition-all duration-200 ${
                                            activeTab === 'profil' 
                                                ? 'text-white bg-white bg-opacity-30' 
                                                : 'text-white hover:bg-white hover:bg-opacity-20'
                                        }`}
                                        title="Profil"
                                    >
                                        <User size={20} />
                                    </button>
                                    
                                    {/* Notification Button */}
                                    <button className="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2 relative">
                                        <Bell size={20} />
                                        <div className="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                                    </button>
                                    
                                    {/* Logout Button */}
                                    <button 
                                        onClick={handleLogout}
                                        className="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2"
                                        title="Keluar"
                                    >
                                        <LogOut size={20} />
                                    </button>
                                </div>
                            </div>
                            
                            <div className="flex items-center gap-3">
                                <div className="w-12 h-12 bg-white bg-opacity-20 backdrop-blur-sm rounded-full flex items-center justify-center">
                                    <User size={24} className="text-white" />
                                </div>
                                <div>
                                    <p className="text-white text-opacity-90 text-sm">Selamat datang,</p>
                                    <p className="text-white">{{ auth()->user()->name ?? 'Dr. Ahmad Fauzi' }}</p>
                                </div>
                            </div>
                        </div>
                    </header>

                    {/* Main Content */}
                    <main className="flex-1 p-4 pb-32 relative z-10">
                        <ActiveComponent />
                    </main>

                    {/* Bottom Navigation */}
                    <nav className="fixed bottom-0 left-1/2 transform -translate-x-1/2 w-full max-w-md z-50">
                        <div className="card m-4 bg-white bg-opacity-95 backdrop-blur-lg">
                            <div className="p-2">
                                <div className="flex justify-around items-center">
                                    {tabs.map((tab) => {
                                        const Icon = tab.icon;
                                        const isActive = activeTab === tab.id;
                                        
                                        return (
                                            <button
                                                key={tab.id}
                                                onClick={() => handleTabChange(tab.id)}
                                                className={`nav-item ${isActive ? 'active' : ''}`}
                                            >
                                                <Icon size={20} className="mb-1" />
                                                <span className="text-xs text-center">{tab.label}</span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
            );
        }

        // Render the app
        ReactDOM.render(<App />, document.getElementById('app'));
    </script>
</body>
</html>
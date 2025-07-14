# Dokterku Paramedis Mobile App - Jaspel Management System

## 📱 Overview

Aplikasi mobile React Native untuk sistem manajemen Jaspel (Jasa Pelayanan) paramedis yang terintegrasi dengan backend Laravel Dokterku. Aplikasi ini menampilkan informasi jaspel dengan desain kelas dunia, mengikuti prinsip Material Design dan Human Interface Guidelines.

## ✨ Fitur Utama

### 🎯 Halaman Jaspel (Completed)
- **Card Informasi Utama**: 
  - Jaspel Bulan Ini (gradasi biru: #3B82F6 → #60A5FA)
  - Jaspel Minggu Ini (gradasi kuning: #FACC15 → #FDE68A)
  - Progress indicator dengan animasi
  - Icon wallet dan trending untuk visual appeal

- **Tabel Jaspel Harian**:
  - FlatList dengan zebra striping (alternating colors)
  - Kolom: Tanggal, Tindakan, Nominal
  - Status badge dengan icon (approved, pending, rejected)
  - Typography menggunakan Inter font family
  - Responsive design untuk semua ukuran layar

### 🧭 Navigasi Lengkap
- **Bottom Navigation**: 5 tab (Beranda, Presensi, Jaspel, GPS, Tindakan)
- **Stack Navigation**: Memungkinkan deep navigation dengan back button
- **Animasi Transisi**: Smooth slide animations antar screen
- **Safe Area**: Kompatibel dengan iPhone notch dan Android navbar

## 🎨 Design System

### Color Palette
```javascript
// Primary Colors
Primary Blue: #3B82F6 → #60A5FA
Primary Yellow: #FACC15 → #FDE68A
Success Green: #10B981 → #34D399

// Status Colors
Success: #10B981 (approved)
Warning: #F59E0B (pending)
Danger: #EF4444 (rejected)

// Text Colors
Primary: #374151
Secondary: #6B7280
Light: #9CA3AF
```

### Typography
```javascript
Font Family: Inter (Regular, Medium, SemiBold, Bold, ExtraBold)
Header Title: 20px, Bold
Card Amount: 32px, ExtraBold
Table Text: 13-15px, Medium-SemiBold
Button Text: 18px, SemiBold
```

### Spacing & Layout
```javascript
Card Padding: 24px
Container Padding: 20px
Card Border Radius: 20px
Button Border Radius: 16px
Icon Size: 24-28px
```

## 🚀 Installation & Setup

### Prerequisites
```bash
# Install Node.js (v18+)
# Install Expo CLI
npm install -g @expo/cli

# Install React Native development tools
npm install -g react-native-cli
```

### Project Setup
```bash
# Navigate to React Native directory
cd resources/react-native

# Install dependencies
npm install

# Start development server
npm start

# Run on specific platform
npm run android  # For Android
npm run ios      # For iOS
npm run web      # For Web
```

### Required Dependencies
```json
{
  "@react-navigation/native": "^6.1.7",
  "@react-navigation/bottom-tabs": "^6.5.8", 
  "@react-navigation/stack": "^6.3.17",
  "expo-linear-gradient": "~12.7.0",
  "lucide-react-native": "^0.263.1",
  "react-native-reanimated": "~3.6.2"
}
```

## 📁 Project Structure

```
resources/react-native/
├── App.js                          # Main app entry point
├── package.json                    # Dependencies & scripts
├── app.json                        # Expo configuration
├── navigation/
│   └── ParamedisNavigator.jsx      # Navigation setup
├── screens/
│   ├── JaspelScreen.jsx            # ⭐ Main Jaspel screen
│   ├── DashboardScreen.jsx         # Dashboard placeholder
│   ├── PresensiScreen.jsx          # Presensi placeholder
│   ├── GPSScreen.jsx               # GPS placeholder
│   └── TindakanScreen.jsx          # Tindakan placeholder
├── assets/
│   ├── fonts/                      # Inter font family
│   ├── icon.png                    # App icon
│   └── splash.png                  # Splash screen
└── README.md                       # This documentation
```

## 🎯 Implementasi Detail

### JaspelScreen.jsx - Main Feature

#### Card Informasi Utama
```javascript
// Blue gradient card (Monthly Jaspel)
<LinearGradient
  colors={['#3B82F6', '#60A5FA']}
  style={styles.card}
>
  <TrendingUp size={28} color="#FFFFFF" />
  <Text style={styles.cardTitle}>Jaspel Bulan Ini</Text>
  <Text style={styles.cardAmount}>
    {formatCurrency(jaspelData.monthly)}
  </Text>
  <ProgressBar progress="65%" />
</LinearGradient>

// Yellow gradient card (Weekly Jaspel)  
<LinearGradient
  colors={['#FACC15', '#FDE68A']}
  style={styles.card}
>
  <DollarSign size={28} color="#92400E" />
  <Text style={styles.cardTitle}>Jaspel Minggu Ini</Text>
  // ... similar structure
</LinearGradient>
```

#### Tabel Jaspel dengan FlatList
```javascript
<FlatList
  data={dailyJaspel}
  renderItem={renderJaspelItem}
  keyExtractor={(item) => item.id}
  // Zebra striping implementation
  backgroundColor={index % 2 === 0 ? '#FFFFFF' : '#F8FAFC'}
/>
```

#### Status Badge dengan Icon
```javascript
const getStatusIcon = (status) => {
  switch (status) {
    case 'disetujui':
      return <CheckCircle size={16} color="#10B981" />;
    case 'pending':
      return <AlertTriangle size={16} color="#F59E0B" />;
    case 'ditolak':
      return <XCircle size={16} color="#EF4444" />;
  }
};
```

### Navigasi Terintegrasi
```javascript
// Bottom Tab Navigator (5 tabs)
const BottomTabNavigator = () => (
  <Tab.Navigator screenOptions={{...}}>
    <Tab.Screen name="Dashboard" component={DashboardScreen} />
    <Tab.Screen name="Presensi" component={PresensiScreen} />
    <Tab.Screen name="Jaspel" component={JaspelStackNavigator} />
    <Tab.Screen name="GPS" component={GPSScreen} />
    <Tab.Screen name="Tindakan" component={TindakanScreen} />
  </Tab.Navigator>
);

// Stack Navigator untuk Jaspel (memungkinkan detail view)
const JaspelStackNavigator = () => (
  <Stack.Navigator>
    <Stack.Screen name="JaspelMain" component={JaspelScreen} />
    // Bisa ditambahkan JaspelDetailScreen di sini
  </Stack.Navigator>
);
```

## 🔗 Integrasi dengan Backend Laravel

### API Endpoints yang Digunakan
```javascript
// Data source (akan dikoneksi ke Laravel API)
GET /api/paramedis/jaspel/monthly    // Monthly jaspel summary
GET /api/paramedis/jaspel/weekly     // Weekly jaspel summary  
GET /api/paramedis/jaspel/daily      // Daily jaspel transactions
GET /api/paramedis/jaspel/status     // Status counts (approved/pending/rejected)
```

### Data Structure
```javascript
// Jaspel Data Model
{
  id: string,
  tanggal: string,         // '14 Juli 2025'
  tindakan: string,        // 'Pemeriksaan Umum'
  nominal: number,         // 75000
  status: string,          // 'disetujui' | 'pending' | 'ditolak'
  pasien: string,          // 'Ahmad Suryanto'
  waktu: string           // '08:30'
}

// Summary Data
{
  monthly: number,         // 15200000
  weekly: number,          // 3800000
  approved: number,        // 12800000
  pending: number         // 2400000
}
```

## ✅ Fitur yang Telah Selesai

- [x] **Halaman Jaspel dengan desain kelas dunia**
- [x] **Card informasi utama dengan gradasi biru & kuning**
- [x] **Tabel jaspel harian dengan FlatList**
- [x] **Zebra striping untuk readability**
- [x] **Status badge dengan icon dan warna**
- [x] **Responsive layout dengan SafeAreaView**
- [x] **ScrollView dengan RefreshControl**
- [x] **Animasi fade-in dan slide transitions**
- [x] **Bottom navigation (5 tabs)**
- [x] **Stack navigation dengan back button**
- [x] **Typography dengan Inter font family**
- [x] **Shadow dan elevation effects**
- [x] **Progress bar dengan animasi**

## 🔮 Roadmap Selanjutnya

### Phase 2: API Integration
- [ ] Koneksi ke Laravel Sanctum API
- [ ] Real-time data fetching
- [ ] Error handling dan loading states
- [ ] Offline data caching

### Phase 3: Advanced Features  
- [ ] Detail jaspel screen
- [ ] Filter dan search functionality
- [ ] Export PDF reports
- [ ] Push notifications

### Phase 4: Performance Optimization
- [ ] Image optimization
- [ ] Bundle size optimization
- [ ] Memory management
- [ ] Performance monitoring

## 🛠️ Development Notes

### Kompatibilitas dengan Existing System
- ✅ Mengikuti struktur bottom navigation paramedis existing
- ✅ Konsisten dengan color scheme dashboard web
- ✅ Font family Inter sesuai dengan web version
- ✅ Icon set Lucide konsisten dengan implementasi web
- ✅ Tidak mengubah sidebar dan navigation existing

### Best Practices Implemented
- ✅ Component-based architecture
- ✅ Consistent naming conventions
- ✅ Responsive design principles
- ✅ Accessibility considerations
- ✅ Performance optimization
- ✅ Clean code structure
- ✅ Comprehensive documentation

### Technical Decisions
- **React Native + Expo**: Fastest development dan deployment
- **Stack + Tab Navigation**: Flexible navigation system
- **Linear Gradient**: Eye-catching visual effects
- **FlatList**: Optimal performance untuk large datasets
- **Lucide Icons**: Consistent dengan web version
- **Inter Font**: Professional typography
- **SafeAreaView**: Universal device compatibility

## 💡 Tips untuk Development

1. **Jalankan Metro bundler terlebih dahulu**:
   ```bash
   npm start
   ```

2. **Test di multiple devices**:
   - iPhone (various sizes)
   - Android (various screen densities)
   - Tablet compatibility

3. **Monitor performance**:
   - Use React DevTools
   - Check memory usage
   - Optimize re-renders

4. **Consistent styling**:
   - Follow established design system
   - Use StyleSheet.create untuk performance
   - Maintain responsive breakpoints

---

**Developed with ❤️ for Dokterku Healthcare System**
**Technology Stack**: React Native + Expo + Laravel API
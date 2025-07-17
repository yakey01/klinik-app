import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  SafeAreaView,
  TouchableOpacity,
  FlatList,
  Animated,
  StatusBar,
  RefreshControl,
  Alert,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { 
  Wallet, 
  ArrowLeft, 
  TrendingUp, 
  Calendar,
  DollarSign,
  Clock,
  CheckCircle,
  AlertTriangle,
  XCircle 
} from 'lucide-react-native';
import { StyleSheet } from 'react-native';
import ApiService from '../services/ApiService';

const JaspelScreen = ({ navigation }) => {
  const [jaspelData, setJaspelData] = useState({
    monthly: 0,
    weekly: 0,
    approved: 0,
    pending: 0,
  });

  const [dailyJaspel, setDailyJaspel] = useState([]);
  const [refreshing, setRefreshing] = useState(false);
  const [loading, setLoading] = useState(true);
  const [fadeAnim] = useState(new Animated.Value(0));

  useEffect(() => {
    loadJaspelData();
    
    // Fade in animation on mount
    Animated.timing(fadeAnim, {
      toValue: 1,
      duration: 800,
      useNativeDriver: true,
    }).start();
  }, []);

  const loadJaspelData = async () => {
    try {
      setLoading(true);
      
      // Load data from API
      const [summaryData, historyData] = await Promise.all([
        ApiService.getJaspelSummary(),
        ApiService.getJaspelHistory(10)
      ]);

      // Update jaspel summary data
      setJaspelData({
        monthly: summaryData.monthly || 0,
        weekly: summaryData.weekly || 0,
        approved: summaryData.approved || 0,
        pending: summaryData.pending || 0,
      });

      // Update history data
      setDailyJaspel(historyData.data || []);
      
    } catch (error) {
      console.error('Failed to load jaspel data:', error);
      Alert.alert(
        'Error',
        'Gagal memuat data jaspel. Menggunakan data sample.',
        [{ text: 'OK' }]
      );
      
      // Fallback to sample data
      setJaspelData({
        monthly: 15200000,
        weekly: 3800000,
        approved: 12800000,
        pending: 2400000,
      });
      
      setDailyJaspel([
        {
          id: '1',
          tanggal: '14 Juli 2025',
          tindakan: 'Pemeriksaan Umum',
          nominal: 75000,
          status: 'disetujui',
          pasien: 'Ahmad Suryanto',
          waktu: '08:30'
        },
        {
          id: '2', 
          tanggal: '14 Juli 2025',
          tindakan: 'Tindakan Luka Ringan',
          nominal: 120000,
          status: 'pending',
          pasien: 'Siti Nurhaliza',
          waktu: '10:15'
        },
        {
          id: '3',
          tanggal: '13 Juli 2025', 
          tindakan: 'Konsultasi Kesehatan',
          nominal: 85000,
          status: 'disetujui',
          pasien: 'Budi Santoso',
          waktu: '14:20'
        }
      ]);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadJaspelData();
    setRefreshing(false);
  };

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const getStatusIcon = (status) => {
    switch (status) {
      case 'disetujui':
        return <CheckCircle size={16} color="#10B981" />;
      case 'pending':
        return <AlertTriangle size={16} color="#F59E0B" />;
      case 'ditolak':
        return <XCircle size={16} color="#EF4444" />;
      default:
        return <Clock size={16} color="#6B7280" />;
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'disetujui':
        return '#10B981';
      case 'pending':
        return '#F59E0B';
      case 'ditolak':
        return '#EF4444';
      default:
        return '#6B7280';
    }
  };

  const renderJaspelItem = ({ item, index }) => (
    <Animated.View
      style={[
        styles.tableRow,
        { 
          backgroundColor: index % 2 === 0 ? '#FFFFFF' : '#F8FAFC',
          opacity: fadeAnim,
          transform: [{
            translateY: fadeAnim.interpolate({
              inputRange: [0, 1],
              outputRange: [20, 0],
            })
          }]
        }
      ]}
    >
      <View style={styles.tableCell}>
        <Text style={styles.dateText}>{item.tanggal}</Text>
        <Text style={styles.timeText}>{item.waktu}</Text>
      </View>
      
      <View style={[styles.tableCell, styles.tindakanCell]}>
        <Text style={styles.tindakanText} numberOfLines={2}>
          {item.tindakan}
        </Text>
        <Text style={styles.pasienText} numberOfLines={1}>
          {item.pasien}
        </Text>
      </View>
      
      <View style={[styles.tableCell, styles.nominalCell]}>
        <Text style={styles.nominalText}>
          {formatCurrency(item.nominal)}
        </Text>
        <View style={[styles.statusBadge, { backgroundColor: `${getStatusColor(item.status)}20` }]}>
          {getStatusIcon(item.status)}
          <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>
            {item.status.charAt(0).toUpperCase() + item.status.slice(1)}
          </Text>
        </View>
      </View>
    </Animated.View>
  );

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#3B82F6" />
      
      {/* Header with Back Button */}
      <LinearGradient
        colors={['#3B82F6', '#60A5FA']}
        style={styles.header}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
      >
        <TouchableOpacity 
          style={styles.backButton}
          onPress={() => navigation.goBack()}
          activeOpacity={0.8}
        >
          <ArrowLeft size={24} color="#FFFFFF" />
        </TouchableOpacity>
        
        <Text style={styles.headerTitle}>Jaspel Saya</Text>
        
        <TouchableOpacity style={styles.walletIcon} activeOpacity={0.8}>
          <Wallet size={24} color="#FFFFFF" />
        </TouchableOpacity>
      </LinearGradient>

      <ScrollView 
        style={styles.scrollView}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
      >
        {/* Cards Informasi Utama */}
        <View style={styles.cardsContainer}>
          {/* Card Jaspel Bulan Ini - Gradasi Biru */}
          <Animated.View style={[styles.cardWrapper, { opacity: fadeAnim }]}>
            <LinearGradient
              colors={['#3B82F6', '#60A5FA']}
              style={styles.card}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
            >
              <View style={styles.cardContent}>
                <View style={styles.cardHeader}>
                  <TrendingUp size={28} color="#FFFFFF" />
                  <Text style={styles.cardTitle}>Jaspel Bulan Ini</Text>
                </View>
                <Text style={styles.cardAmount}>
                  {formatCurrency(jaspelData.monthly)}
                </Text>
                <View style={styles.cardFooter}>
                  <View style={styles.progressBar}>
                    <View style={[styles.progressFill, { width: '65%' }]} />
                  </View>
                  <Text style={styles.progressText}>65% dari target</Text>
                </View>
              </View>
            </LinearGradient>
          </Animated.View>

          {/* Card Jaspel Minggu Ini - Gradasi Kuning */}
          <Animated.View 
            style={[
              styles.cardWrapper, 
              { 
                opacity: fadeAnim,
                transform: [{
                  translateY: fadeAnim.interpolate({
                    inputRange: [0, 1],
                    outputRange: [30, 0],
                  })
                }]
              }
            ]}
          >
            <LinearGradient
              colors={['#FACC15', '#FDE68A']}
              style={styles.card}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
            >
              <View style={styles.cardContent}>
                <View style={styles.cardHeader}>
                  <DollarSign size={28} color="#92400E" />
                  <Text style={[styles.cardTitle, { color: '#92400E' }]}>
                    Jaspel Minggu Ini
                  </Text>
                </View>
                <Text style={[styles.cardAmount, { color: '#92400E' }]}>
                  {formatCurrency(jaspelData.weekly)}
                </Text>
                <View style={styles.cardFooter}>
                  <View style={[styles.progressBar, { backgroundColor: 'rgba(146, 64, 14, 0.2)' }]}>
                    <View style={[styles.progressFill, { width: '40%', backgroundColor: '#92400E' }]} />
                  </View>
                  <Text style={[styles.progressText, { color: '#92400E' }]}>
                    40% dari target mingguan
                  </Text>
                </View>
              </View>
            </LinearGradient>
          </Animated.View>
        </View>

        {/* Tabel Jaspel Harian */}
        <Animated.View 
          style={[
            styles.tableContainer,
            {
              opacity: fadeAnim,
              transform: [{
                translateY: fadeAnim.interpolate({
                  inputRange: [0, 1],
                  outputRange: [40, 0],
                })
              }]
            }
          ]}
        >
          <View style={styles.tableHeader}>
            <Calendar size={20} color="#374151" />
            <Text style={styles.tableTitle}>Riwayat Jaspel Harian</Text>
          </View>
          
          {/* Table Header */}
          <View style={styles.tableHeaderRow}>
            <Text style={[styles.tableHeaderText, { flex: 1.2 }]}>Tanggal</Text>
            <Text style={[styles.tableHeaderText, { flex: 2 }]}>Tindakan</Text>
            <Text style={[styles.tableHeaderText, { flex: 1.5, textAlign: 'right' }]}>Nominal</Text>
          </View>

          {/* Table Data */}
          <FlatList
            data={dailyJaspel}
            renderItem={renderJaspelItem}
            keyExtractor={(item) => item.id}
            scrollEnabled={false}
            showsVerticalScrollIndicator={false}
          />
        </Animated.View>

        {/* Bottom Padding for Navigation */}
        <View style={styles.bottomPadding} />
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F7F9FC',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 16,
    elevation: 4,
    shadowColor: '#3B82F6',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 8,
  },
  backButton: {
    padding: 8,
    borderRadius: 12,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: '#FFFFFF',
    fontFamily: 'Inter-Bold',
  },
  walletIcon: {
    padding: 8,
    borderRadius: 12,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
  },
  scrollView: {
    flex: 1,
  },
  cardsContainer: {
    padding: 20,
    gap: 16,
  },
  cardWrapper: {
    borderRadius: 20,
    elevation: 8,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 12,
  },
  card: {
    borderRadius: 20,
    padding: 24,
    minHeight: 160,
  },
  cardContent: {
    flex: 1,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 16,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#FFFFFF',
    fontFamily: 'Inter-SemiBold',
  },
  cardAmount: {
    fontSize: 32,
    fontWeight: '800',
    color: '#FFFFFF',
    fontFamily: 'Inter-ExtraBold',
    marginBottom: 16,
    letterSpacing: -1,
  },
  cardFooter: {
    gap: 8,
  },
  progressBar: {
    height: 6,
    backgroundColor: 'rgba(255, 255, 255, 0.3)',
    borderRadius: 3,
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    backgroundColor: '#FFFFFF',
    borderRadius: 3,
  },
  progressText: {
    fontSize: 12,
    fontWeight: '500',
    color: 'rgba(255, 255, 255, 0.9)',
    fontFamily: 'Inter-Medium',
  },
  tableContainer: {
    backgroundColor: '#FFFFFF',
    marginHorizontal: 20,
    borderRadius: 16,
    overflow: 'hidden',
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  tableHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    padding: 20,
    backgroundColor: '#F8FAFC',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  tableTitle: {
    fontSize: 18,
    fontWeight: '600',
    color: '#374151',
    fontFamily: 'Inter-SemiBold',
  },
  tableHeaderRow: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#F3F4F6',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  tableHeaderText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#6B7280',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    fontFamily: 'Inter-SemiBold',
  },
  tableRow: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#F3F4F6',
  },
  tableCell: {
    flex: 1,
    justifyContent: 'center',
  },
  tindakanCell: {
    flex: 2,
    paddingHorizontal: 8,
  },
  nominalCell: {
    flex: 1.5,
    alignItems: 'flex-end',
  },
  dateText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#374151',
    fontFamily: 'Inter-SemiBold',
  },
  timeText: {
    fontSize: 11,
    color: '#6B7280',
    fontFamily: 'Inter-Regular',
    marginTop: 2,
  },
  tindakanText: {
    fontSize: 14,
    fontWeight: '500',
    color: '#374151',
    fontFamily: 'Inter-Medium',
    lineHeight: 18,
  },
  pasienText: {
    fontSize: 12,
    color: '#6B7280',
    fontFamily: 'Inter-Regular',
    marginTop: 4,
  },
  nominalText: {
    fontSize: 15,
    fontWeight: '700',
    color: '#059669',
    fontFamily: 'Inter-Bold',
    textAlign: 'right',
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
    marginTop: 6,
    alignSelf: 'flex-end',
  },
  statusText: {
    fontSize: 10,
    fontWeight: '600',
    fontFamily: 'Inter-SemiBold',
    textTransform: 'capitalize',
  },
  bottomPadding: {
    height: 100,
  },
});

export default JaspelScreen;
/// MediCare Pro - Professional Application Constants
/// Contains all constant values used throughout the application
class AppConstants {
  // Professional App Information
  static const String appName = 'MediCare Pro';
  static const String appTagline = 'Professional Healthcare Management System';
  static const String appVersion = '1.0.0';
  static const String appBuildNumber = '1';
  
  // Professional API Configuration
  static const String apiBaseUrl = 'https://dokterku.app/api/v2';
  static const String apiTimeout = '30';
  static const String apiRetryCount = '3';
  
  // Professional Storage Keys
  static const String storageAuthToken = 'auth_token';
  static const String storageUserData = 'user_data';
  static const String storageRefreshToken = 'refresh_token';
  static const String storageSettings = 'app_settings';
  static const String storageOfflineData = 'offline_data';
  
  // Professional User Roles
  static const String roleDokter = 'dokter';
  static const String roleDokterGigi = 'dokter_gigi';
  static const String roleParamedis = 'paramedis';
  static const String roleAdmin = 'admin';
  
  // Professional Permissions
  static const List<String> dokterPermissions = [
    'view-dashboard',
    'view-dashboard-stats',
    'view-patients',
    'manage-patient-queue',
    'call-patients',
    'view-schedules',
    'manage-schedules',
    'add-schedule',
    'edit-schedule',
    'delete-schedule',
    'view-attendance',
    'manage-attendance',
    'checkin-attendance',
    'checkout-attendance',
    'view-attendance-history',
    'view-jaspel',
    'manage-jaspel',
    'view-jaspel-stats',
    'view-jaspel-breakdown',
    'view-procedures',
    'manage-procedures',
    'record-procedures',
    'edit-procedures',
    'view-reports',
    'generate-reports',
    'export-reports',
    'view-analytics',
    'view-profile',
    'edit-profile',
    'change-password',
    'view-presensi',
    'manage-presensi',
    'presensi-checkin',
    'presensi-checkout',
    'view-dokter-dashboard',
    'manage-dokter-schedule',
    'view-dokter-patients',
    'manage-dokter-procedures',
    'view-dokter-jaspel',
    'view-dokter-reports',
    'access-dokter-panel',
    'view-dokter-mobile',
    'use-dokter-api',
  ];
  
  // Professional Status Types
  static const String statusOnline = 'online';
  static const String statusOffline = 'offline';
  static const String statusBusy = 'busy';
  static const String statusAway = 'away';
  static const String statusInProgress = 'in_progress';
  
  // Professional Attendance Status
  static const String attendanceCheckedIn = 'checked_in';
  static const String attendanceCheckedOut = 'checked_out';
  static const String attendanceAbsent = 'absent';
  static const String attendanceLate = 'late';
  static const String attendanceEarly = 'early';
  
  // Professional Patient Status
  static const String patientWaiting = 'waiting';
  static const String patientCurrent = 'current';
  static const String patientCompleted = 'completed';
  static const String patientCancelled = 'cancelled';
  static const String patientNoShow = 'no_show';
  
  // Professional Schedule Status
  static const String scheduleActive = 'active';
  static const String scheduleCompleted = 'completed';
  static const String scheduleCancelled = 'cancelled';
  static const String scheduleRescheduled = 'rescheduled';
  
  // Professional Notification Types
  static const String notificationInfo = 'info';
  static const String notificationSuccess = 'success';
  static const String notificationWarning = 'warning';
  static const String notificationError = 'error';
  
  // Professional Date Formats
  static const String dateFormatDefault = 'dd/MM/yyyy';
  static const String dateFormatLong = 'EEEE, dd MMMM yyyy';
  static const String dateFormatShort = 'dd MMM yyyy';
  static const String timeFormatDefault = 'HH:mm';
  static const String timeFormatLong = 'HH:mm:ss';
  static const String dateTimeFormatDefault = 'dd/MM/yyyy HH:mm';
  
  // Professional Currency Format
  static const String currencySymbol = 'Rp';
  static const String currencyLocale = 'id_ID';
  
  // Professional Validation Rules
  static const int minPasswordLength = 6;
  static const int maxPasswordLength = 50;
  static const int maxNameLength = 100;
  static const int maxDescriptionLength = 500;
  static const int maxNotesLength = 1000;
  
  // Professional File Limits
  static const int maxImageSize = 5 * 1024 * 1024; // 5MB
  static const int maxDocumentSize = 10 * 1024 * 1024; // 10MB
  static const List<String> allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif'];
  static const List<String> allowedDocumentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
  
  // Professional Network Configuration
  static const int connectionTimeout = 30000; // 30 seconds
  static const int receiveTimeout = 30000; // 30 seconds
  static const int sendTimeout = 30000; // 30 seconds
  
  // Professional Cache Configuration
  static const int cacheMaxAge = 24 * 60 * 60; // 24 hours
  static const int cacheMaxSize = 50 * 1024 * 1024; // 50MB
  
  // Professional Location Configuration
  static const double locationAccuracy = 10.0; // meters
  static const int locationTimeout = 30000; // 30 seconds
  static const double clinicLatitude = -6.200000; // Jakarta coordinates
  static const double clinicLongitude = 106.816666;
  static const double clinicRadius = 50.0; // meters
  
  // Professional Pagination
  static const int defaultPageSize = 20;
  static const int maxPageSize = 100;
  
  // Professional Error Messages
  static const String errorNetworkGeneral = 'Terjadi kesalahan jaringan. Silakan coba lagi.';
  static const String errorNetworkTimeout = 'Koneksi timeout. Silakan coba lagi.';
  static const String errorNetworkNoInternet = 'Tidak ada koneksi internet. Silakan periksa koneksi Anda.';
  static const String errorServerGeneral = 'Server sedang mengalami gangguan. Silakan coba lagi nanti.';
  static const String errorAuthExpired = 'Sesi Anda telah berakhir. Silakan login ulang.';
  static const String errorAuthInvalid = 'Kredensial tidak valid. Silakan periksa email dan password Anda.';
  static const String errorPermissionDenied = 'Anda tidak memiliki izin untuk mengakses fitur ini.';
  static const String errorDataNotFound = 'Data tidak ditemukan.';
  static const String errorValidationFailed = 'Data yang dimasukkan tidak valid.';
  
  // Professional Success Messages
  static const String successLoginGeneral = 'Login berhasil. Selamat datang!';
  static const String successLogoutGeneral = 'Logout berhasil. Sampai jumpa!';
  static const String successDataSaved = 'Data berhasil disimpan.';
  static const String successDataDeleted = 'Data berhasil dihapus.';
  static const String successDataUpdated = 'Data berhasil diperbarui.';
  static const String successAttendanceCheckin = 'Check-in berhasil.';
  static const String successAttendanceCheckout = 'Check-out berhasil.';
  static const String successPatientCalled = 'Pasien berhasil dipanggil.';
  static const String successScheduleCreated = 'Jadwal berhasil dibuat.';
  static const String successScheduleUpdated = 'Jadwal berhasil diperbarui.';
  static const String successScheduleDeleted = 'Jadwal berhasil dihapus.';
  
  // Professional Loading Messages
  static const String loadingGeneral = 'Memuat data...';
  static const String loadingLogin = 'Sedang login...';
  static const String loadingLogout = 'Sedang logout...';
  static const String loadingDashboard = 'Memuat dashboard...';
  static const String loadingPatientQueue = 'Memuat antrian pasien...';
  static const String loadingSchedule = 'Memuat jadwal...';
  static const String loadingAttendance = 'Memuat presensi...';
  static const String loadingJaspel = 'Memuat data jaspel...';
  static const String loadingReports = 'Memuat laporan...';
  static const String loadingProfile = 'Memuat profil...';
  
  // Professional Navigation Labels
  static const String navDashboard = 'Dashboard';
  static const String navSchedule = 'Jadwal';
  static const String navAttendance = 'Presensi';
  static const String navJaspel = 'Jaspel';
  static const String navReports = 'Laporan';
  static const String navProfile = 'Profil';
  static const String navSettings = 'Pengaturan';
  static const String navLogout = 'Logout';
  
  // Professional Feature Labels
  static const String featurePatientQueue = 'Antrian Pasien';
  static const String featureQuickAccess = 'Akses Cepat';
  static const String featureScheduleManagement = 'Manajemen Jadwal';
  static const String featureAttendanceTracking = 'Pelacakan Presensi';
  static const String featureJaspelCalculation = 'Kalkulasi Jaspel';
  static const String featureReportsAnalytics = 'Laporan & Analitik';
  static const String featureNotifications = 'Notifikasi';
  static const String featureSearchGlobal = 'Pencarian Global';
  
  // Professional Time Zones
  static const String timezoneDefault = 'Asia/Jakarta';
  static const String timezoneUTC = 'UTC';
  
  // Professional Device Types
  static const String deviceTypeAndroid = 'android';
  static const String deviceTypeIOS = 'ios';
  static const String deviceTypeWeb = 'web';
  
  // Professional Chart Types
  static const String chartTypeBar = 'bar';
  static const String chartTypeLine = 'line';
  static const String chartTypePie = 'pie';
  static const String chartTypeArea = 'area';
  static const String chartTypeScatter = 'scatter';
  
  // Professional Export Types
  static const String exportTypePDF = 'pdf';
  static const String exportTypeExcel = 'excel';
  static const String exportTypeCSV = 'csv';
  static const String exportTypeJSON = 'json';
  
  // Professional Theme Types
  static const String themeLight = 'light';
  static const String themeDark = 'dark';
  static const String themeSystem = 'system';
  
  // Professional Language Codes
  static const String languageIndonesian = 'id';
  static const String languageEnglish = 'en';
  
  // Professional Regular Expressions
  static const String regexEmail = r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$';
  static const String regexPhone = r'^\+?[\d\s\-\(\)]+$';
  static const String regexName = r'^[a-zA-Z\s\.]+$';
  static const String regexPassword = r'^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{6,}$';
  
  // Professional Animation Durations
  static const int animationFast = 150;
  static const int animationNormal = 300;
  static const int animationSlow = 500;
  static const int animationBounce = 800;
  
  // Professional Refresh Intervals
  static const int refreshIntervalDashboard = 30000; // 30 seconds
  static const int refreshIntervalQueue = 10000; // 10 seconds
  static const int refreshIntervalNotifications = 60000; // 1 minute
  static const int refreshIntervalAttendance = 300000; // 5 minutes
  
  // Professional Quality Configurations
  static const int imageQuality = 85;
  static const int thumbnailSize = 200;
  static const int previewSize = 400;
  
  // Professional Security Configuration
  static const int tokenRefreshBuffer = 300; // 5 minutes before expiry
  static const int maxLoginAttempts = 5;
  static const int lockoutDuration = 900; // 15 minutes
  
  // Professional Notification Configuration
  static const String notificationChannelId = 'medicare_pro_general';
  static const String notificationChannelName = 'MediCare Pro Notifications';
  static const String notificationChannelDescription = 'General notifications for MediCare Pro';
  
  // Professional Development Configuration
  static const bool isProduction = bool.fromEnvironment('dart.vm.product');
  static const String logLevel = isProduction ? 'ERROR' : 'DEBUG';
  
  // Professional Analytics Configuration
  static const bool enableAnalytics = true;
  static const bool enableCrashlytics = true;
  static const bool enablePerformanceMonitoring = true;
  
  // Professional Accessibility Configuration
  static const double minTouchTarget = 44.0;
  static const double maxFontScale = 1.5;
  static const double minFontScale = 0.8;
  
  // Professional Professional Credentials (for testing)
  static const String testDoctorEmail = 'dokter@dokterku.com';
  static const String testDoctorPassword = 'password';
  static const String testDoctorName = 'Dr. Sarah Wijaya, Sp.PD';
  static const String testDoctorSpecialty = 'Spesialis Penyakit Dalam';
  static const String testDoctorLicense = '503/SIP/2020';
  
  // Professional Fallback Data
  static const Map<String, dynamic> fallbackDashboardData = {
    'today_patients': 32,
    'monthly_jaspel': 5200000,
    'weekly_schedules': 8,
    'patient_rating': 4.9,
    'attendance_percentage': 98,
    'total_patients': 208,
    'work_days': 26,
  };
  
  static const List<Map<String, dynamic>> fallbackPatientQueue = [
    {
      'id': '1',
      'name': 'Andi Wijaya',
      'complaint': 'Kontrol hipertensi rutin',
      'status': 'current',
      'number': 1,
      'estimated_time': '08:30',
    },
    {
      'id': '2',
      'name': 'Siti Rahayu',
      'complaint': 'Demam dan batuk kering',
      'status': 'waiting',
      'number': 2,
      'estimated_time': '09:00',
    },
    {
      'id': '3',
      'name': 'Budi Santoso',
      'complaint': 'Konsultasi diabetes mellitus',
      'status': 'waiting',
      'number': 3,
      'estimated_time': '09:30',
    },
    {
      'id': '4',
      'name': 'Maya Sari',
      'complaint': 'Pemeriksaan laboratorium',
      'status': 'waiting',
      'number': 4,
      'estimated_time': '10:00',
    },
  ];
  
  static const List<Map<String, dynamic>> fallbackScheduleData = [
    {
      'id': '1',
      'title': 'Praktek Umum Pagi',
      'start_time': '08:00',
      'end_time': '12:00',
      'date': '2025-07-15',
      'location': 'Ruang Praktek 1',
      'patient_count': 18,
      'status': 'active',
    },
    {
      'id': '2',
      'title': 'Konsultasi VIP',
      'start_time': '13:00',
      'end_time': '14:00',
      'date': '2025-07-15',
      'location': 'Ruang VIP',
      'patient_count': 4,
      'status': 'active',
    },
    {
      'id': '3',
      'title': 'Tindakan Medis',
      'start_time': '14:30',
      'end_time': '17:00',
      'date': '2025-07-15',
      'location': 'Ruang Tindakan',
      'patient_count': 4,
      'status': 'active',
    },
    {
      'id': '4',
      'title': 'Praktek Malam',
      'start_time': '19:00',
      'end_time': '21:00',
      'date': '2025-07-15',
      'location': 'Ruang Praktek 1',
      'patient_count': 6,
      'status': 'active',
    },
  ];
  
  // Professional Company Information
  static const String companyName = 'MediCare Pro Clinic';
  static const String companyAddress = 'Jl. Sudirman No. 123, Jakarta Pusat';
  static const String companyPhone = '+62 21 1234567';
  static const String companyEmail = 'info@medicareprogroup.com';
  static const String companyWebsite = 'https://medicareprogroup.com';
  
  // Professional Support Information
  static const String supportEmail = 'support@medicareprogroup.com';
  static const String supportPhone = '+62 21 1234567';
  static const String supportWhatsapp = '+62 812 3456 7890';
  static const String supportHours = 'Senin - Jumat: 08:00 - 17:00';
  
  // Professional Legal Information
  static const String privacyPolicyUrl = 'https://medicareprogroup.com/privacy';
  static const String termsOfServiceUrl = 'https://medicareprogroup.com/terms';
  static const String licenseUrl = 'https://medicareprogroup.com/license';
  
  // Professional Version Information
  static const String buildDate = '2025-07-15';
  static const String buildHash = 'abc123def456';
  static const String buildEnvironment = isProduction ? 'production' : 'development';
}
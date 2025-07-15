import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:pretty_dio_logger/pretty_dio_logger.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:connectivity_plus/connectivity_plus.dart';

/// MediCare Pro - Professional API Service
/// Handles all API communication with Laravel backend
class ApiService {
  static const String _baseUrl = 'https://dokterku.app/api/v2';
  static const String _ngrokUrl = 'https://your-ngrok-url.ngrok.io/api/v2';
  static const String _localUrl = 'http://127.0.0.1:8000/api/v2';
  
  static const String _storageTokenKey = 'auth_token';
  static const String _storageUserKey = 'user_data';
  static const String _storageRefreshKey = 'refresh_token';
  
  late final Dio _dio;
  late final FlutterSecureStorage _secureStorage;
  final Connectivity _connectivity = Connectivity();
  
  String? _authToken;
  Map<String, dynamic>? _userData;
  Timer? _tokenRefreshTimer;
  
  // Professional Error Types
  enum ApiErrorType { network, timeout, unauthorized, serverError, unknown }
  
  // Professional API Response
  class ApiResponse<T> {
    final bool success;
    final T? data;
    final String? message;
    final int? statusCode;
    final ApiErrorType? errorType;
    final Map<String, dynamic>? errors;
    
    ApiResponse({
      required this.success,
      this.data,
      this.message,
      this.statusCode,
      this.errorType,
      this.errors,
    });
    
    factory ApiResponse.success(T data, {String? message}) {
      return ApiResponse(
        success: true,
        data: data,
        message: message,
      );
    }
    
    factory ApiResponse.error(
      String message, {
      ApiErrorType? errorType,
      int? statusCode,
      Map<String, dynamic>? errors,
    }) {
      return ApiResponse(
        success: false,
        message: message,
        errorType: errorType,
        statusCode: statusCode,
        errors: errors,
      );
    }
  }
  
  // Professional Exception Classes
  class ApiException implements Exception {
    final String message;
    final ApiErrorType type;
    final int? statusCode;
    final Map<String, dynamic>? errors;
    
    ApiException({
      required this.message,
      required this.type,
      this.statusCode,
      this.errors,
    });
    
    @override
    String toString() => 'ApiException: $message (${type.name})';
  }
  
  // Singleton Pattern
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  
  ApiService._internal() {
    _secureStorage = const FlutterSecureStorage(
      aOptions: AndroidOptions(
        encryptedSharedPreferences: true,
        keyCipherAlgorithm: KeyCipherAlgorithm.RSA_ECB_PKCS1Padding,
        storageCipherAlgorithm: StorageCipherAlgorithm.AES_GCM_NoPadding,
      ),
      iOptions: IOSOptions(
        accessibility: KeychainAccessibility.first_unlock_this_device,
      ),
    );
    
    _initializeDio();
    _setupTokenRefresh();
  }
  
  void _initializeDio() {
    _dio = Dio(BaseOptions(
      baseUrl: _determineBaseUrl(),
      connectTimeout: const Duration(milliseconds: 30000),
      receiveTimeout: const Duration(milliseconds: 30000),
      sendTimeout: const Duration(milliseconds: 30000),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    ));
    
    // Professional Logger (only in debug mode)
    if (kDebugMode) {
      _dio.interceptors.add(PrettyDioLogger(
        requestHeader: true,
        requestBody: true,
        responseBody: true,
        responseHeader: false,
        error: true,
        compact: true,
        maxWidth: 90,
      ));
    }
    
    // Professional Auth Interceptor
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await getAuthToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) async {
        if (error.response?.statusCode == 401) {
          await _handleUnauthorized();
        }
        handler.next(error);
      },
    ));
    
    // Professional Retry Interceptor
    _dio.interceptors.add(InterceptorsWrapper(
      onError: (error, handler) async {
        if (_shouldRetry(error)) {
          try {
            final response = await _dio.fetch(error.requestOptions);
            return handler.resolve(response);
          } catch (e) {
            handler.next(error);
          }
        } else {
          handler.next(error);
        }
      },
    ));
  }
  
  String _determineBaseUrl() {
    // Professional environment detection
    if (kDebugMode) {
      return _localUrl; // Use local for development
    }
    
    // Check for ngrok in production testing
    if (_ngrokUrl.contains('ngrok')) {
      return _ngrokUrl;
    }
    
    return _baseUrl; // Production URL
  }
  
  bool _shouldRetry(DioException error) {
    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.receiveTimeout ||
        error.type == DioExceptionType.sendTimeout) {
      return true;
    }
    
    if (error.response?.statusCode == 502 ||
        error.response?.statusCode == 503 ||
        error.response?.statusCode == 504) {
      return true;
    }
    
    return false;
  }
  
  // Professional Authentication Methods
  Future<ApiResponse<Map<String, dynamic>>> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _dio.post('/auth/login', data: {
        'email': email,
        'password': password,
      });
      
      if (response.statusCode == 200) {
        final data = response.data['data'];
        final token = data['token'];
        final user = data['user'];
        
        await _storeAuthData(token, user);
        await _setupTokenRefresh();
        
        return ApiResponse.success(data, message: 'Login berhasil');
      } else {
        return ApiResponse.error('Login gagal');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  Future<ApiResponse<void>> logout() async {
    try {
      await _dio.post('/auth/logout');
      await _clearAuthData();
      return ApiResponse.success(null, message: 'Logout berhasil');
    } catch (e) {
      // Clear local data even if API call fails
      await _clearAuthData();
      return ApiResponse.success(null, message: 'Logout berhasil');
    }
  }
  
  Future<ApiResponse<Map<String, dynamic>>> refreshToken() async {
    try {
      final refreshToken = await _secureStorage.read(key: _storageRefreshKey);
      if (refreshToken == null) {
        return ApiResponse.error('Refresh token tidak ditemukan');
      }
      
      final response = await _dio.post('/auth/refresh', data: {
        'refresh_token': refreshToken,
      });
      
      if (response.statusCode == 200) {
        final data = response.data['data'];
        final token = data['token'];
        final user = data['user'];
        
        await _storeAuthData(token, user);
        return ApiResponse.success(data);
      } else {
        return ApiResponse.error('Refresh token gagal');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  // Professional Dashboard Methods
  Future<ApiResponse<Map<String, dynamic>>> getDashboardData() async {
    try {
      final response = await _dio.get('/dashboard/dokter');
      
      if (response.statusCode == 200) {
        return ApiResponse.success(response.data['data']);
      } else {
        return ApiResponse.error('Gagal mengambil data dashboard');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  Future<ApiResponse<List<Map<String, dynamic>>>> getPatientQueue() async {
    try {
      final response = await _dio.get('/dashboard/dokter/queue');
      
      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'];
        return ApiResponse.success(data.cast<Map<String, dynamic>>());
      } else {
        return ApiResponse.error('Gagal mengambil antrian pasien');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  Future<ApiResponse<Map<String, dynamic>>> getTodaySchedule() async {
    try {
      final response = await _dio.get('/dashboard/dokter/schedule/today');
      
      if (response.statusCode == 200) {
        return ApiResponse.success(response.data['data']);
      } else {
        return ApiResponse.error('Gagal mengambil jadwal hari ini');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  // Professional Attendance Methods
  Future<ApiResponse<Map<String, dynamic>>> checkIn({
    required double latitude,
    required double longitude,
    String? notes,
  }) async {
    try {
      final response = await _dio.post('/attendance/checkin', data: {
        'latitude': latitude,
        'longitude': longitude,
        'notes': notes,
        'device_info': await _getDeviceInfo(),
      });
      
      if (response.statusCode == 200) {
        return ApiResponse.success(
          response.data['data'],
          message: 'Check-in berhasil',
        );
      } else {
        return ApiResponse.error('Check-in gagal');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  Future<ApiResponse<Map<String, dynamic>>> checkOut({
    required double latitude,
    required double longitude,
    String? notes,
  }) async {
    try {
      final response = await _dio.post('/attendance/checkout', data: {
        'latitude': latitude,
        'longitude': longitude,
        'notes': notes,
        'device_info': await _getDeviceInfo(),
      });
      
      if (response.statusCode == 200) {
        return ApiResponse.success(
          response.data['data'],
          message: 'Check-out berhasil',
        );
      } else {
        return ApiResponse.error('Check-out gagal');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  Future<ApiResponse<List<Map<String, dynamic>>>> getAttendanceHistory({
    int page = 1,
    int limit = 20,
  }) async {
    try {
      final response = await _dio.get('/attendance/history', queryParameters: {
        'page': page,
        'limit': limit,
      });
      
      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'];
        return ApiResponse.success(data.cast<Map<String, dynamic>>());
      } else {
        return ApiResponse.error('Gagal mengambil riwayat presensi');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  // Professional Jaspel Methods
  Future<ApiResponse<Map<String, dynamic>>> getJaspelData({
    String? month,
    String? year,
  }) async {
    try {
      final response = await _dio.get('/jaspel/dokter', queryParameters: {
        if (month != null) 'month': month,
        if (year != null) 'year': year,
      });
      
      if (response.statusCode == 200) {
        return ApiResponse.success(response.data['data']);
      } else {
        return ApiResponse.error('Gagal mengambil data jaspel');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  // Professional Schedule Methods
  Future<ApiResponse<List<Map<String, dynamic>>>> getSchedules({
    String? startDate,
    String? endDate,
  }) async {
    try {
      final response = await _dio.get('/schedule/dokter', queryParameters: {
        if (startDate != null) 'start_date': startDate,
        if (endDate != null) 'end_date': endDate,
      });
      
      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'];
        return ApiResponse.success(data.cast<Map<String, dynamic>>());
      } else {
        return ApiResponse.error('Gagal mengambil jadwal');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  Future<ApiResponse<Map<String, dynamic>>> createSchedule({
    required String title,
    required String startTime,
    required String endTime,
    required String date,
    String? description,
    String? location,
  }) async {
    try {
      final response = await _dio.post('/schedule/dokter', data: {
        'title': title,
        'start_time': startTime,
        'end_time': endTime,
        'date': date,
        'description': description,
        'location': location,
      });
      
      if (response.statusCode == 201) {
        return ApiResponse.success(
          response.data['data'],
          message: 'Jadwal berhasil dibuat',
        );
      } else {
        return ApiResponse.error('Gagal membuat jadwal');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  // Professional Report Methods
  Future<ApiResponse<Map<String, dynamic>>> getReports({
    String? type,
    String? startDate,
    String? endDate,
  }) async {
    try {
      final response = await _dio.get('/reports/dokter', queryParameters: {
        if (type != null) 'type': type,
        if (startDate != null) 'start_date': startDate,
        if (endDate != null) 'end_date': endDate,
      });
      
      if (response.statusCode == 200) {
        return ApiResponse.success(response.data['data']);
      } else {
        return ApiResponse.error('Gagal mengambil laporan');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  // Professional Patient Methods
  Future<ApiResponse<Map<String, dynamic>>> callPatient({
    required String patientId,
  }) async {
    try {
      final response = await _dio.post('/patients/call', data: {
        'patient_id': patientId,
      });
      
      if (response.statusCode == 200) {
        return ApiResponse.success(
          response.data['data'],
          message: 'Pasien berhasil dipanggil',
        );
      } else {
        return ApiResponse.error('Gagal memanggil pasien');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  // Professional Notification Methods
  Future<ApiResponse<List<Map<String, dynamic>>>> getNotifications({
    int page = 1,
    int limit = 20,
  }) async {
    try {
      final response = await _dio.get('/notifications', queryParameters: {
        'page': page,
        'limit': limit,
      });
      
      if (response.statusCode == 200) {
        final List<dynamic> data = response.data['data'];
        return ApiResponse.success(data.cast<Map<String, dynamic>>());
      } else {
        return ApiResponse.error('Gagal mengambil notifikasi');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  Future<ApiResponse<void>> markNotificationAsRead({
    required String notificationId,
  }) async {
    try {
      final response = await _dio.patch('/notifications/$notificationId/read');
      
      if (response.statusCode == 200) {
        return ApiResponse.success(null, message: 'Notifikasi ditandai sudah dibaca');
      } else {
        return ApiResponse.error('Gagal menandai notifikasi');
      }
    } catch (e) {
      return _handleError(e);
    }
  }
  
  // Professional Utility Methods
  Future<String?> getAuthToken() async {
    _authToken ??= await _secureStorage.read(key: _storageTokenKey);
    return _authToken;
  }
  
  Future<Map<String, dynamic>?> getUserData() async {
    if (_userData == null) {
      final userString = await _secureStorage.read(key: _storageUserKey);
      if (userString != null) {
        _userData = jsonDecode(userString);
      }
    }
    return _userData;
  }
  
  Future<bool> isLoggedIn() async {
    final token = await getAuthToken();
    return token != null;
  }
  
  Future<bool> isOnline() async {
    final connectivityResult = await _connectivity.checkConnectivity();
    return connectivityResult != ConnectivityResult.none;
  }
  
  // Professional Private Methods
  Future<void> _storeAuthData(String token, Map<String, dynamic> user) async {
    _authToken = token;
    _userData = user;
    
    await _secureStorage.write(key: _storageTokenKey, value: token);
    await _secureStorage.write(key: _storageUserKey, value: jsonEncode(user));
  }
  
  Future<void> _clearAuthData() async {
    _authToken = null;
    _userData = null;
    _tokenRefreshTimer?.cancel();
    
    await _secureStorage.delete(key: _storageTokenKey);
    await _secureStorage.delete(key: _storageUserKey);
    await _secureStorage.delete(key: _storageRefreshKey);
  }
  
  Future<void> _setupTokenRefresh() async {
    _tokenRefreshTimer?.cancel();
    
    // Refresh token every 50 minutes (assuming 60-minute expiry)
    _tokenRefreshTimer = Timer.periodic(
      const Duration(minutes: 50),
      (timer) async {
        final result = await refreshToken();
        if (!result.success) {
          timer.cancel();
          await _clearAuthData();
        }
      },
    );
  }
  
  Future<void> _handleUnauthorized() async {
    await _clearAuthData();
    // Redirect to login screen would be handled by the app
  }
  
  Future<Map<String, dynamic>> _getDeviceInfo() async {
    // This would normally use device_info_plus and package_info_plus
    return {
      'platform': Platform.operatingSystem,
      'version': Platform.operatingSystemVersion,
      'app_version': '1.0.0',
    };
  }
  
  ApiResponse<T> _handleError<T>(dynamic error) {
    if (error is DioException) {
      switch (error.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.receiveTimeout:
        case DioExceptionType.sendTimeout:
          return ApiResponse.error(
            'Koneksi timeout. Silakan coba lagi.',
            errorType: ApiErrorType.timeout,
          );
        case DioExceptionType.badResponse:
          final statusCode = error.response?.statusCode ?? 0;
          final message = error.response?.data?['message'] ?? 'Terjadi kesalahan server';
          
          if (statusCode == 401) {
            return ApiResponse.error(
              'Sesi Anda telah berakhir. Silakan login ulang.',
              errorType: ApiErrorType.unauthorized,
              statusCode: statusCode,
            );
          } else if (statusCode >= 500) {
            return ApiResponse.error(
              'Server sedang mengalami gangguan. Silakan coba lagi nanti.',
              errorType: ApiErrorType.serverError,
              statusCode: statusCode,
            );
          } else {
            return ApiResponse.error(
              message,
              errorType: ApiErrorType.serverError,
              statusCode: statusCode,
              errors: error.response?.data?['errors'],
            );
          }
        case DioExceptionType.connectionError:
          return ApiResponse.error(
            'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.',
            errorType: ApiErrorType.network,
          );
        default:
          return ApiResponse.error(
            'Terjadi kesalahan yang tidak diketahui.',
            errorType: ApiErrorType.unknown,
          );
      }
    } else {
      return ApiResponse.error(
        'Terjadi kesalahan yang tidak diketahui.',
        errorType: ApiErrorType.unknown,
      );
    }
  }
  
  // Professional Cleanup
  void dispose() {
    _tokenRefreshTimer?.cancel();
    _dio.close();
  }
}
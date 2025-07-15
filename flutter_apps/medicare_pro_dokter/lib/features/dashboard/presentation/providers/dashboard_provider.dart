import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/services/api_service.dart';
import '../../../../core/utils/app_constants.dart';

/// Professional Dashboard Provider
final dashboardProvider = AsyncNotifierProvider<DashboardNotifier, Map<String, dynamic>>(
  DashboardNotifier.new,
);

/// Professional Patient Queue Provider
final patientQueueProvider = AsyncNotifierProvider<PatientQueueNotifier, List<Map<String, dynamic>>>(
  PatientQueueNotifier.new,
);

/// Professional Dashboard State Notifier
class DashboardNotifier extends AsyncNotifier<Map<String, dynamic>> {
  late final ApiService _apiService;

  @override
  Future<Map<String, dynamic>> build() async {
    _apiService = ApiService();
    return await loadDashboardData();
  }

  /// Load dashboard data
  Future<Map<String, dynamic>> loadDashboardData() async {
    try {
      final response = await _apiService.getDashboardData();
      
      if (response.success) {
        return response.data!;
      } else {
        // Return fallback data if API fails
        return AppConstants.fallbackDashboardData;
      }
    } catch (e) {
      // Return fallback data on error
      return AppConstants.fallbackDashboardData;
    }
  }

  /// Refresh dashboard data
  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() => loadDashboardData());
  }
}

/// Professional Patient Queue State Notifier
class PatientQueueNotifier extends AsyncNotifier<List<Map<String, dynamic>>> {
  late final ApiService _apiService;

  @override
  Future<List<Map<String, dynamic>>> build() async {
    _apiService = ApiService();
    return await loadPatientQueue();
  }

  /// Load patient queue
  Future<List<Map<String, dynamic>>> loadPatientQueue() async {
    try {
      final response = await _apiService.getPatientQueue();
      
      if (response.success) {
        return response.data!;
      } else {
        // Return fallback data if API fails
        return AppConstants.fallbackPatientQueue;
      }
    } catch (e) {
      // Return fallback data on error
      return AppConstants.fallbackPatientQueue;
    }
  }

  /// Call patient
  Future<void> callPatient(String patientId) async {
    try {
      final response = await _apiService.callPatient(patientId: patientId);
      
      if (response.success) {
        // Refresh queue after calling patient
        await refresh();
      }
    } catch (e) {
      // Handle error
      rethrow;
    }
  }

  /// Refresh patient queue
  Future<void> refresh() async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() => loadPatientQueue());
  }
}
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/services/api_service.dart';
import '../../../../core/utils/app_constants.dart';

/// Professional Authentication Provider
final authProvider = AsyncNotifierProvider<AuthNotifier, Map<String, dynamic>?>(
  AuthNotifier.new,
);

/// Professional Authentication State Notifier
class AuthNotifier extends AsyncNotifier<Map<String, dynamic>?> {
  late final ApiService _apiService;

  @override
  Future<Map<String, dynamic>?> build() async {
    _apiService = ApiService();
    
    // Check if user is already logged in
    final userData = await _apiService.getUserData();
    return userData;
  }

  /// Login user
  Future<void> login({
    required String email,
    required String password,
  }) async {
    state = const AsyncLoading();
    
    try {
      final response = await _apiService.login(
        email: email,
        password: password,
      );
      
      if (response.success) {
        state = AsyncData(response.data!['user']);
      } else {
        state = AsyncError(response.message ?? 'Login failed', StackTrace.current);
      }
    } catch (e) {
      state = AsyncError(e, StackTrace.current);
    }
  }

  /// Logout user
  Future<void> logout() async {
    state = const AsyncLoading();
    
    try {
      await _apiService.logout();
      state = const AsyncData(null);
    } catch (e) {
      // Even if logout fails, clear local state
      state = const AsyncData(null);
    }
  }

  /// Check if user is logged in
  bool get isLoggedIn => state.hasValue && state.value != null;
  
  /// Get current user data
  Map<String, dynamic>? get currentUser => state.value;
}
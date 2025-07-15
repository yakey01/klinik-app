import 'dart:async';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../services/api_service.dart';
import 'app_constants.dart';

/// MediCare Pro - Professional Error Handler
/// Provides centralized error handling and logging
class ErrorHandler {
  static ErrorHandler? _instance;
  static ErrorHandler get instance => _instance ??= ErrorHandler._();
  
  ErrorHandler._();
  
  /// Initialize error handler
  static void initialize() {
    instance._setupErrorHandling();
  }
  
  /// Setup error handling
  void _setupErrorHandling() {
    // Handle Flutter errors
    FlutterError.onError = (FlutterErrorDetails details) {
      _logError(
        'Flutter Error',
        details.exception,
        details.stack,
      );
      
      // In production, send to crash reporting service
      if (AppConstants.isProduction) {
        _sendToCrashReporting(details.exception, details.stack);
      }
    };
    
    // Handle async errors
    PlatformDispatcher.instance.onError = (error, stack) {
      _logError(
        'Async Error',
        error,
        stack,
      );
      
      // In production, send to crash reporting service
      if (AppConstants.isProduction) {
        _sendToCrashReporting(error, stack);
      }
      
      return true;
    };
  }
  
  /// Handle API errors
  String handleApiError(ApiService.ApiResponse response) {
    switch (response.errorType) {
      case ApiService.ApiErrorType.network:
        return AppConstants.errorNetworkGeneral;
      case ApiService.ApiErrorType.timeout:
        return AppConstants.errorNetworkTimeout;
      case ApiService.ApiErrorType.unauthorized:
        return AppConstants.errorAuthExpired;
      case ApiService.ApiErrorType.serverError:
        return response.message ?? AppConstants.errorServerGeneral;
      case ApiService.ApiErrorType.unknown:
      default:
        return response.message ?? 'Terjadi kesalahan yang tidak diketahui';
    }
  }
  
  /// Handle general errors
  String handleGeneralError(dynamic error) {
    if (error is ApiService.ApiException) {
      return handleApiError(
        ApiService.ApiResponse.error(
          error.message,
          errorType: error.type,
          statusCode: error.statusCode,
          errors: error.errors,
        ),
      );
    }
    
    if (error is TimeoutException) {
      return AppConstants.errorNetworkTimeout;
    }
    
    if (error is FormatException) {
      return AppConstants.errorValidationFailed;
    }
    
    if (error is StateError) {
      return AppConstants.errorDataNotFound;
    }
    
    // Log unknown errors
    _logError('Unknown Error', error, StackTrace.current);
    
    return 'Terjadi kesalahan yang tidak diketahui';
  }
  
  /// Show error dialog
  void showErrorDialog(
    BuildContext context, {
    required String title,
    required String message,
    VoidCallback? onRetry,
    VoidCallback? onCancel,
  }) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            Icon(
              Icons.error_outline,
              color: Theme.of(context).colorScheme.error,
            ),
            const SizedBox(width: 12),
            Text(title),
          ],
        ),
        content: Text(message),
        actions: [
          if (onCancel != null)
            TextButton(
              onPressed: () {
                Navigator.of(context).pop();
                onCancel();
              },
              child: const Text('Batal'),
            ),
          if (onRetry != null)
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pop();
                onRetry();
              },
              child: const Text('Coba Lagi'),
            )
          else
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('OK'),
            ),
        ],
      ),
    );
  }
  
  /// Show error snackbar
  void showErrorSnackbar(
    BuildContext context, {
    required String message,
    VoidCallback? onRetry,
    Duration duration = const Duration(seconds: 4),
  }) {
    final snackBar = SnackBar(
      content: Row(
        children: [
          Icon(
            Icons.error_outline,
            color: Theme.of(context).colorScheme.onError,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onError,
              ),
            ),
          ),
        ],
      ),
      backgroundColor: Theme.of(context).colorScheme.error,
      duration: duration,
      action: onRetry != null
          ? SnackBarAction(
              label: 'Coba Lagi',
              textColor: Theme.of(context).colorScheme.onError,
              onPressed: onRetry,
            )
          : null,
    );
    
    ScaffoldMessenger.of(context).showSnackBar(snackBar);
  }
  
  /// Show success snackbar
  void showSuccessSnackbar(
    BuildContext context, {
    required String message,
    Duration duration = const Duration(seconds: 3),
  }) {
    final snackBar = SnackBar(
      content: Row(
        children: [
          Icon(
            Icons.check_circle_outline,
            color: Theme.of(context).colorScheme.onPrimary,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onPrimary,
              ),
            ),
          ),
        ],
      ),
      backgroundColor: Theme.of(context).colorScheme.primary,
      duration: duration,
    );
    
    ScaffoldMessenger.of(context).showSnackBar(snackBar);
  }
  
  /// Show warning snackbar
  void showWarningSnackbar(
    BuildContext context, {
    required String message,
    Duration duration = const Duration(seconds: 3),
  }) {
    final snackBar = SnackBar(
      content: Row(
        children: [
          const Icon(
            Icons.warning_amber_outlined,
            color: Colors.black,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: Colors.black,
              ),
            ),
          ),
        ],
      ),
      backgroundColor: Colors.amber,
      duration: duration,
    );
    
    ScaffoldMessenger.of(context).showSnackBar(snackBar);
  }
  
  /// Show info snackbar
  void showInfoSnackbar(
    BuildContext context, {
    required String message,
    Duration duration = const Duration(seconds: 3),
  }) {
    final snackBar = SnackBar(
      content: Row(
        children: [
          const Icon(
            Icons.info_outline,
            color: Colors.white,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: Colors.white,
              ),
            ),
          ),
        ],
      ),
      backgroundColor: Colors.blue,
      duration: duration,
    );
    
    ScaffoldMessenger.of(context).showSnackBar(snackBar);
  }
  
  /// Log error
  void _logError(String type, dynamic error, StackTrace? stack) {
    if (kDebugMode) {
      debugPrint('[$type] Error: $error');
      if (stack != null) {
        debugPrint('Stack trace: $stack');
      }
    }
    
    // In production, you would send this to a logging service
    // like Firebase Crashlytics, Sentry, etc.
    if (AppConstants.isProduction) {
      // Send to logging service
      _sendToLoggingService(type, error, stack);
    }
  }
  
  /// Send error to crash reporting service
  void _sendToCrashReporting(dynamic error, StackTrace? stack) {
    // Implementation would depend on your crash reporting service
    // For example, Firebase Crashlytics:
    // FirebaseCrashlytics.instance.recordError(error, stack);
    
    debugPrint('Would send to crash reporting: $error');
  }
  
  /// Send error to logging service
  void _sendToLoggingService(String type, dynamic error, StackTrace? stack) {
    // Implementation would depend on your logging service
    // For example, sending to a remote logging API
    
    debugPrint('Would send to logging service: [$type] $error');
  }
}

/// Professional Error Widget
class ErrorWidget extends StatelessWidget {
  final String title;
  final String message;
  final VoidCallback? onRetry;
  final IconData? icon;
  final Color? iconColor;

  const ErrorWidget({
    super.key,
    required this.title,
    required this.message,
    this.onRetry,
    this.icon,
    this.iconColor,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Error Icon
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: (iconColor ?? Theme.of(context).colorScheme.error).withOpacity(0.1),
                borderRadius: BorderRadius.circular(40),
              ),
              child: Icon(
                icon ?? Icons.error_outline,
                size: 40,
                color: iconColor ?? Theme.of(context).colorScheme.error,
              ),
            ),
            
            const SizedBox(height: 24),
            
            // Error Title
            Text(
              title,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.onSurface,
              ),
              textAlign: TextAlign.center,
            ),
            
            const SizedBox(height: 12),
            
            // Error Message
            Text(
              message,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
              textAlign: TextAlign.center,
            ),
            
            const SizedBox(height: 32),
            
            // Retry Button
            if (onRetry != null)
              ElevatedButton.icon(
                onPressed: onRetry,
                icon: const Icon(Icons.refresh),
                label: const Text('Coba Lagi'),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 32,
                    vertical: 16,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// Professional Network Error Widget
class NetworkErrorWidget extends StatelessWidget {
  final VoidCallback? onRetry;

  const NetworkErrorWidget({
    super.key,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ErrorWidget(
      title: 'Tidak Ada Koneksi',
      message: 'Pastikan Anda terhubung ke internet dan coba lagi.',
      icon: Icons.wifi_off,
      iconColor: Colors.orange,
      onRetry: onRetry,
    );
  }
}

/// Professional Server Error Widget
class ServerErrorWidget extends StatelessWidget {
  final VoidCallback? onRetry;

  const ServerErrorWidget({
    super.key,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ErrorWidget(
      title: 'Server Bermasalah',
      message: 'Server sedang mengalami gangguan. Silakan coba lagi nanti.',
      icon: Icons.cloud_off,
      iconColor: Colors.red,
      onRetry: onRetry,
    );
  }
}

/// Professional Empty State Widget
class EmptyStateWidget extends StatelessWidget {
  final String title;
  final String message;
  final IconData? icon;
  final VoidCallback? onAction;
  final String? actionText;

  const EmptyStateWidget({
    super.key,
    required this.title,
    required this.message,
    this.icon,
    this.onAction,
    this.actionText,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Empty Icon
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.primary.withOpacity(0.1),
                borderRadius: BorderRadius.circular(40),
              ),
              child: Icon(
                icon ?? Icons.inbox_outlined,
                size: 40,
                color: Theme.of(context).colorScheme.primary,
              ),
            ),
            
            const SizedBox(height: 24),
            
            // Empty Title
            Text(
              title,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
                color: Theme.of(context).colorScheme.onSurface,
              ),
              textAlign: TextAlign.center,
            ),
            
            const SizedBox(height: 12),
            
            // Empty Message
            Text(
              message,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
              textAlign: TextAlign.center,
            ),
            
            const SizedBox(height: 32),
            
            // Action Button
            if (onAction != null && actionText != null)
              ElevatedButton.icon(
                onPressed: onAction,
                icon: const Icon(Icons.add),
                label: Text(actionText!),
                style: ElevatedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 32,
                    vertical: 16,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
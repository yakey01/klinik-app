import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:flutter_native_splash/flutter_native_splash.dart';

import 'core/theme/app_theme.dart';
import 'features/auth/presentation/pages/login_page.dart';
import 'features/dashboard/presentation/pages/dashboard_page.dart';
import 'features/auth/presentation/providers/auth_provider.dart';
import 'core/services/api_service.dart';
import 'core/utils/app_constants.dart';
import 'core/utils/error_handler.dart';

/// MediCare Pro - Professional Healthcare Management System
/// Entry point for the Flutter application
void main() async {
  WidgetsBinding widgetsBinding = WidgetsFlutterBinding.ensureInitialized();
  
  // Keep splash screen visible during initialization
  FlutterNativeSplash.preserve(widgetsBinding: widgetsBinding);
  
  // Professional initialization
  await _initializeApp();
  
  // Run the app
  runApp(
    const ProviderScope(
      child: MediCareProApp(),
    ),
  );
}

/// Professional app initialization
Future<void> _initializeApp() async {
  try {
    // Initialize Hive for local storage
    await Hive.initFlutter();
    
    // Initialize date formatting for Indonesian locale
    await initializeDateFormatting('id_ID');
    
    // Set system UI overlay style
    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        systemNavigationBarColor: Colors.transparent,
        systemNavigationBarIconBrightness: Brightness.dark,
      ),
    );
    
    // Set preferred orientations
    await SystemChrome.setPreferredOrientations([
      DeviceOrientation.portraitUp,
      DeviceOrientation.portraitDown,
    ]);
    
    // Initialize API service
    ApiService();
    
    // Initialize error handler
    ErrorHandler.initialize();
    
    // Remove splash screen
    FlutterNativeSplash.remove();
    
  } catch (e) {
    debugPrint('Error during app initialization: $e');
    FlutterNativeSplash.remove();
  }
}

/// Professional main app widget
class MediCareProApp extends ConsumerWidget {
  const MediCareProApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    
    return MaterialApp(
      title: AppConstants.appName,
      debugShowCheckedModeBanner: false,
      
      // Professional theme configuration
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: ThemeMode.system,
      
      // Professional localization
      locale: const Locale('id', 'ID'),
      
      // Professional navigation
      home: authState.when(
        data: (user) {
          if (user != null) {
            return const DashboardPage();
          } else {
            return const LoginPage();
          }
        },
        loading: () => const ProfessionalSplashScreen(),
        error: (error, stack) => ProfessionalErrorScreen(
          error: error,
          onRetry: () => ref.refresh(authProvider),
        ),
      ),
      
      // Professional route configuration
      routes: {
        '/login': (context) => const LoginPage(),
        '/dashboard': (context) => const DashboardPage(),
      },
      
      // Professional error handling
      builder: (context, child) {
        return ProfessionalErrorBoundary(
          child: child ?? Container(),
        );
      },
    );
  }
}

/// Professional splash screen
class ProfessionalSplashScreen extends StatelessWidget {
  const ProfessionalSplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: AppTheme.backgroundGradient,
        ),
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Professional logo
              Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.2),
                  borderRadius: AppTheme.borderRadius3xl,
                  border: Border.all(
                    color: Colors.white.withOpacity(0.3),
                    width: 2,
                  ),
                ),
                child: const Icon(
                  Icons.medical_services_outlined,
                  size: 60,
                  color: Colors.white,
                ),
              ),
              
              const SizedBox(height: AppTheme.spacing8),
              
              // Professional title
              Text(
                'MediCare Pro',
                style: AppTheme.textTheme.headlineLarge?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w900,
                  letterSpacing: -1,
                ),
              ),
              
              const SizedBox(height: AppTheme.spacing2),
              
              // Professional subtitle
              Text(
                'Professional Healthcare Management',
                style: AppTheme.textTheme.bodyMedium?.copyWith(
                  color: Colors.white.withOpacity(0.9),
                  fontWeight: FontWeight.w500,
                ),
              ),
              
              const SizedBox(height: AppTheme.spacing8),
              
              // Professional loading indicator
              const CircularProgressIndicator(
                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                strokeWidth: 3,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Professional error screen
class ProfessionalErrorScreen extends StatelessWidget {
  final Object error;
  final VoidCallback onRetry;

  const ProfessionalErrorScreen({
    super.key,
    required this.error,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: AppTheme.backgroundGradient,
        ),
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(AppTheme.spacing6),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Professional error icon
                Container(
                  width: 100,
                  height: 100,
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.1),
                    borderRadius: AppTheme.borderRadius3xl,
                    border: Border.all(
                      color: Colors.white.withOpacity(0.3),
                      width: 2,
                    ),
                  ),
                  child: const Icon(
                    Icons.error_outline,
                    size: 50,
                    color: Colors.white,
                  ),
                ),
                
                const SizedBox(height: AppTheme.spacing6),
                
                // Professional error title
                Text(
                  'Oops! Terjadi Kesalahan',
                  style: AppTheme.textTheme.headlineMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                  ),
                  textAlign: TextAlign.center,
                ),
                
                const SizedBox(height: AppTheme.spacing4),
                
                // Professional error message
                Text(
                  'Aplikasi mengalami masalah saat memuat data. Silakan coba lagi.',
                  style: AppTheme.textTheme.bodyMedium?.copyWith(
                    color: Colors.white.withOpacity(0.9),
                  ),
                  textAlign: TextAlign.center,
                ),
                
                const SizedBox(height: AppTheme.spacing8),
                
                // Professional retry button
                ElevatedButton(
                  onPressed: onRetry,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.white,
                    foregroundColor: AppTheme.primaryBlue,
                    padding: const EdgeInsets.symmetric(
                      horizontal: AppTheme.spacing8,
                      vertical: AppTheme.spacing4,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: AppTheme.borderRadius2xl,
                    ),
                  ),
                  child: const Text(
                    'Coba Lagi',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Professional error boundary widget
class ProfessionalErrorBoundary extends StatefulWidget {
  final Widget child;

  const ProfessionalErrorBoundary({
    super.key,
    required this.child,
  });

  @override
  State<ProfessionalErrorBoundary> createState() => _ProfessionalErrorBoundaryState();
}

class _ProfessionalErrorBoundaryState extends State<ProfessionalErrorBoundary> {
  bool _hasError = false;
  Object? _error;

  @override
  void initState() {
    super.initState();
    
    // Listen for Flutter errors
    FlutterError.onError = (FlutterErrorDetails details) {
      setState(() {
        _hasError = true;
        _error = details.exception;
      });
      
      // Log error for debugging
      debugPrint('Flutter Error: ${details.exception}');
      debugPrint('Stack trace: ${details.stack}');
    };
  }

  @override
  Widget build(BuildContext context) {
    if (_hasError) {
      return ProfessionalErrorScreen(
        error: _error ?? 'Unknown error',
        onRetry: () {
          setState(() {
            _hasError = false;
            _error = null;
          });
        },
      );
    }

    return widget.child;
  }
}
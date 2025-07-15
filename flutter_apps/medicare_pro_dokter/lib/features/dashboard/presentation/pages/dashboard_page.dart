import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/app_constants.dart';
import '../../../../core/utils/error_handler.dart';
import '../widgets/professional_status_bar.dart';
import '../widgets/professional_app_header.dart';
import '../widgets/professional_dashboard_stats.dart';
import '../widgets/professional_patient_queue.dart';
import '../widgets/professional_quick_menu.dart';
import '../widgets/professional_bottom_navigation.dart';
import '../widgets/professional_floating_action_button.dart';
import '../providers/dashboard_provider.dart';
import '../../schedule/presentation/pages/schedule_page.dart';
import '../../attendance/presentation/pages/attendance_page.dart';
import '../../jaspel/presentation/pages/jaspel_page.dart';
import '../../reports/presentation/pages/reports_page.dart';

/// MediCare Pro - Professional Dashboard Page
/// Main dashboard interface for doctors
class DashboardPage extends ConsumerStatefulWidget {
  const DashboardPage({super.key});

  @override
  ConsumerState<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends ConsumerState<DashboardPage>
    with TickerProviderStateMixin {
  late PageController _pageController;
  int _currentPageIndex = 0;
  
  late AnimationController _backgroundAnimationController;
  late Animation<double> _backgroundAnimation;
  
  @override
  void initState() {
    super.initState();
    
    _pageController = PageController(initialPage: 0);
    
    // Professional background animation
    _backgroundAnimationController = AnimationController(
      duration: const Duration(seconds: 30),
      vsync: this,
    );
    
    _backgroundAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _backgroundAnimationController,
      curve: Curves.easeInOut,
    ));
    
    _backgroundAnimationController.repeat(reverse: true);
    
    // Load initial data
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(dashboardProvider.notifier).loadDashboardData();
    });
  }
  
  @override
  void dispose() {
    _pageController.dispose();
    _backgroundAnimationController.dispose();
    super.dispose();
  }
  
  @override
  Widget build(BuildContext context) {
    final dashboardState = ref.watch(dashboardProvider);
    
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: Stack(
        children: [
          // Professional Animated Background
          AnimatedBuilder(
            animation: _backgroundAnimation,
            builder: (context, child) {
              return Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      AppTheme.primaryBlue,
                      AppTheme.secondaryBlue,
                      AppTheme.accentTeal,
                      AppTheme.primaryLight,
                    ],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    stops: [
                      0.0,
                      0.25 + (_backgroundAnimation.value * 0.1),
                      0.75 - (_backgroundAnimation.value * 0.1),
                      1.0,
                    ],
                  ),
                ),
              );
            },
          ),
          
          // Professional Content
          SafeArea(
            child: Column(
              children: [
                // Professional Status Bar
                const ProfessionalStatusBar(),
                
                // Professional App Header
                const ProfessionalAppHeader(),
                
                // Professional Main Content
                Expanded(
                  child: Container(
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(
                        colors: [AppTheme.gray50, AppTheme.gray100],
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                      ),
                    ),
                    child: PageView(
                      controller: _pageController,
                      onPageChanged: (index) {
                        setState(() {
                          _currentPageIndex = index;
                        });
                      },
                      children: [
                        // Dashboard Page
                        _buildDashboardContent(dashboardState),
                        
                        // Schedule Page
                        const SchedulePage(),
                        
                        // Attendance Page
                        const AttendancePage(),
                        
                        // Jaspel Page
                        const JaspelPage(),
                        
                        // Reports Page
                        const ReportsPage(),
                      ],
                    ),
                  ),
                ),
                
                // Professional Bottom Navigation
                ProfessionalBottomNavigation(
                  currentIndex: _currentPageIndex,
                  onTap: (index) {
                    _pageController.animateToPage(
                      index,
                      duration: AppTheme.transitionDuration,
                      curve: AppTheme.transitionCurve,
                    );
                  },
                ),
              ],
            ),
          ),
          
          // Professional Floating Action Button
          const ProfessionalFloatingActionButton(),
        ],
      ),
    );
  }
  
  /// Build dashboard content
  Widget _buildDashboardContent(AsyncValue<Map<String, dynamic>> dashboardState) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(AppTheme.spacing5),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Professional Search Bar
          _buildSearchBar(),
          
          const SizedBox(height: AppTheme.spacing6),
          
          // Professional Dashboard Stats
          dashboardState.when(
            data: (data) => ProfessionalDashboardStats(data: data),
            loading: () => const ProfessionalDashboardStatsShimmer(),
            error: (error, stack) => _buildErrorWidget(
              'Gagal memuat statistik dashboard',
              () => ref.refresh(dashboardProvider),
            ),
          ),
          
          const SizedBox(height: AppTheme.spacing6),
          
          // Professional Patient Queue
          _buildPatientQueueSection(),
          
          const SizedBox(height: AppTheme.spacing6),
          
          // Professional Quick Menu
          const ProfessionalQuickMenu(),
          
          const SizedBox(height: AppTheme.spacing12),
        ],
      ),
    );
  }
  
  /// Build search bar
  Widget _buildSearchBar() {
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.white,
        borderRadius: AppTheme.borderRadius2xl,
        border: Border.all(color: AppTheme.gray200, width: 2),
        boxShadow: const [AppTheme.shadowSm],
      ),
      child: TextField(
        decoration: InputDecoration(
          hintText: 'Cari pasien, jadwal, atau laporan...',
          hintStyle: const TextStyle(
            color: AppTheme.gray400,
            fontWeight: FontWeight.w500,
          ),
          prefixIcon: const Icon(
            Icons.search,
            color: AppTheme.gray400,
            size: 20,
          ),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: AppTheme.spacing5,
            vertical: AppTheme.spacing4,
          ),
        ),
        style: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w500,
        ),
        onChanged: (query) {
          // Handle search
          if (query.isNotEmpty) {
            _handleSearch(query);
          }
        },
      ),
    );
  }
  
  /// Build patient queue section
  Widget _buildPatientQueueSection() {
    final queueState = ref.watch(patientQueueProvider);
    
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.white,
        borderRadius: AppTheme.borderRadius2xl,
        border: Border.all(color: AppTheme.gray200, width: 1),
        boxShadow: const [AppTheme.shadowMd],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Padding(
            padding: const EdgeInsets.all(AppTheme.spacing6),
            child: Row(
              children: [
                Container(
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    gradient: AppTheme.primaryGradient,
                    borderRadius: AppTheme.borderRadiusLg,
                  ),
                  child: const Icon(
                    Icons.assignment_outlined,
                    color: AppTheme.white,
                    size: 16,
                  ),
                ),
                const SizedBox(width: AppTheme.spacing3),
                const Text(
                  'Antrian Pasien',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                    color: AppTheme.gray900,
                  ),
                ),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: AppTheme.spacing4,
                    vertical: AppTheme.spacing2,
                  ),
                  decoration: BoxDecoration(
                    gradient: AppTheme.primaryGradient,
                    borderRadius: AppTheme.borderRadius2xl,
                  ),
                  child: queueState.when(
                    data: (queue) => Text(
                      '${queue.length} Menunggu',
                      style: const TextStyle(
                        color: AppTheme.white,
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    loading: () => const Text(
                      'Memuat...',
                      style: TextStyle(
                        color: AppTheme.white,
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    error: (_, __) => const Text(
                      'Error',
                      style: TextStyle(
                        color: AppTheme.white,
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          // Queue Content
          queueState.when(
            data: (queue) => ProfessionalPatientQueue(patients: queue),
            loading: () => const ProfessionalPatientQueueShimmer(),
            error: (error, stack) => _buildErrorWidget(
              'Gagal memuat antrian pasien',
              () => ref.refresh(patientQueueProvider),
            ),
          ),
        ],
      ),
    );
  }
  
  /// Build error widget
  Widget _buildErrorWidget(String message, VoidCallback onRetry) {
    return Container(
      padding: const EdgeInsets.all(AppTheme.spacing6),
      child: Column(
        children: [
          Icon(
            Icons.error_outline,
            color: AppTheme.errorRed,
            size: 48,
          ),
          const SizedBox(height: AppTheme.spacing4),
          Text(
            message,
            style: const TextStyle(
              color: AppTheme.gray600,
              fontSize: 16,
              fontWeight: FontWeight.w500,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: AppTheme.spacing4),
          ElevatedButton(
            onPressed: onRetry,
            child: const Text('Coba Lagi'),
          ),
        ],
      ),
    );
  }
  
  /// Handle search
  void _handleSearch(String query) {
    // Simulate search
    ErrorHandler.instance.showInfoSnackbar(
      context,
      message: 'Mencari: "$query"',
    );
    
    // Navigate to appropriate page based on search
    if (query.toLowerCase().contains('pasien')) {
      // Stay on dashboard
    } else if (query.toLowerCase().contains('jadwal')) {
      _pageController.animateToPage(
        1,
        duration: AppTheme.transitionDuration,
        curve: AppTheme.transitionCurve,
      );
    } else if (query.toLowerCase().contains('laporan')) {
      _pageController.animateToPage(
        4,
        duration: AppTheme.transitionDuration,
        curve: AppTheme.transitionCurve,
      );
    } else if (query.toLowerCase().contains('jaspel')) {
      _pageController.animateToPage(
        3,
        duration: AppTheme.transitionDuration,
        curve: AppTheme.transitionCurve,
      );
    }
  }
}

/// Professional Dashboard Stats Shimmer
class ProfessionalDashboardStatsShimmer extends StatelessWidget {
  const ProfessionalDashboardStatsShimmer({super.key});

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      childAspectRatio: 1.2,
      mainAxisSpacing: AppTheme.spacing4,
      crossAxisSpacing: AppTheme.spacing4,
      children: List.generate(
        4,
        (index) => Container(
          decoration: BoxDecoration(
            color: AppTheme.white,
            borderRadius: AppTheme.borderRadius2xl,
            border: Border.all(color: AppTheme.gray200, width: 1),
            boxShadow: const [AppTheme.shadowMd],
          ),
          child: const Center(
            child: CircularProgressIndicator(),
          ),
        ),
      ),
    );
  }
}

/// Professional Patient Queue Shimmer
class ProfessionalPatientQueueShimmer extends StatelessWidget {
  const ProfessionalPatientQueueShimmer({super.key});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: List.generate(
        3,
        (index) => Container(
          margin: const EdgeInsets.symmetric(
            horizontal: AppTheme.spacing6,
            vertical: AppTheme.spacing2,
          ),
          padding: const EdgeInsets.all(AppTheme.spacing4),
          decoration: BoxDecoration(
            color: AppTheme.gray50,
            borderRadius: AppTheme.borderRadiusXl,
            border: Border.all(color: AppTheme.gray200, width: 1),
          ),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: AppTheme.gray300,
                  borderRadius: AppTheme.borderRadiusLg,
                ),
              ),
              const SizedBox(width: AppTheme.spacing4),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      height: 16,
                      width: double.infinity,
                      decoration: BoxDecoration(
                        color: AppTheme.gray300,
                        borderRadius: AppTheme.borderRadiusSm,
                      ),
                    ),
                    const SizedBox(height: AppTheme.spacing2),
                    Container(
                      height: 12,
                      width: 150,
                      decoration: BoxDecoration(
                        color: AppTheme.gray300,
                        borderRadius: AppTheme.borderRadiusSm,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                width: 80,
                height: 24,
                decoration: BoxDecoration(
                  color: AppTheme.gray300,
                  borderRadius: AppTheme.borderRadius2xl,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
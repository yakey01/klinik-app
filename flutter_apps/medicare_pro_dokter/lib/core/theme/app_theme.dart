import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// MediCare Pro - Professional Healthcare Theme System
/// Provides consistent styling and colors across the application
class AppTheme {
  // Professional Healthcare Color Palette
  static const Color primaryBlue = Color(0xFF0066CC);
  static const Color primaryDark = Color(0xFF004D99);
  static const Color primaryLight = Color(0xFF3385D6);
  static const Color secondaryBlue = Color(0xFF4A90E2);
  static const Color accentTeal = Color(0xFF00B4A6);
  static const Color successGreen = Color(0xFF28A745);
  static const Color warningOrange = Color(0xFFF39C12);
  static const Color errorRed = Color(0xFFDC3545);
  static const Color infoBlue = Color(0xFF17A2B8);
  
  // Professional Neutrals
  static const Color white = Color(0xFFFFFFFF);
  static const Color gray50 = Color(0xFFF8FAFC);
  static const Color gray100 = Color(0xFFF1F5F9);
  static const Color gray200 = Color(0xFFE2E8F0);
  static const Color gray300 = Color(0xFFCBD5E1);
  static const Color gray400 = Color(0xFF94A3B8);
  static const Color gray500 = Color(0xFF64748B);
  static const Color gray600 = Color(0xFF475569);
  static const Color gray700 = Color(0xFF334155);
  static const Color gray800 = Color(0xFF1E293B);
  static const Color gray900 = Color(0xFF0F172A);
  
  // Professional Gradients
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [primaryBlue, secondaryBlue],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  
  static const LinearGradient successGradient = LinearGradient(
    colors: [successGreen, Color(0xFF20C997)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  
  static const LinearGradient warningGradient = LinearGradient(
    colors: [warningOrange, Color(0xFFFD7E14)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  
  static const LinearGradient accentGradient = LinearGradient(
    colors: [accentTeal, Color(0xFF00D4AA)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  
  static const LinearGradient backgroundGradient = LinearGradient(
    colors: [
      primaryBlue,
      secondaryBlue,
      accentTeal,
      primaryLight,
    ],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    stops: [0.0, 0.25, 0.75, 1.0],
  );
  
  // Professional Shadows
  static const BoxShadow shadowSm = BoxShadow(
    color: Color(0x0D000000),
    blurRadius: 2,
    offset: Offset(0, 1),
  );
  
  static const BoxShadow shadowMd = BoxShadow(
    color: Color(0x1A000000),
    blurRadius: 6,
    offset: Offset(0, 4),
  );
  
  static const BoxShadow shadowLg = BoxShadow(
    color: Color(0x1A000000),
    blurRadius: 15,
    offset: Offset(0, 10),
  );
  
  static const BoxShadow shadowXl = BoxShadow(
    color: Color(0x1A000000),
    blurRadius: 25,
    offset: Offset(0, 20),
  );
  
  static const BoxShadow shadowPremium = BoxShadow(
    color: Color(0x40000000),
    blurRadius: 50,
    offset: Offset(0, 25),
  );
  
  // Professional Border Radius
  static const BorderRadius borderRadiusSm = BorderRadius.all(Radius.circular(6));
  static const BorderRadius borderRadiusMd = BorderRadius.all(Radius.circular(8));
  static const BorderRadius borderRadiusLg = BorderRadius.all(Radius.circular(12));
  static const BorderRadius borderRadiusXl = BorderRadius.all(Radius.circular(16));
  static const BorderRadius borderRadius2xl = BorderRadius.all(Radius.circular(24));
  static const BorderRadius borderRadius3xl = BorderRadius.all(Radius.circular(32));
  
  // Professional Typography
  static TextTheme get textTheme => GoogleFonts.interTextTheme(
    const TextTheme(
      displayLarge: TextStyle(
        fontSize: 57,
        fontWeight: FontWeight.w400,
        letterSpacing: -0.25,
      ),
      displayMedium: TextStyle(
        fontSize: 45,
        fontWeight: FontWeight.w400,
        letterSpacing: 0,
      ),
      displaySmall: TextStyle(
        fontSize: 36,
        fontWeight: FontWeight.w400,
        letterSpacing: 0,
      ),
      headlineLarge: TextStyle(
        fontSize: 32,
        fontWeight: FontWeight.w700,
        letterSpacing: 0,
      ),
      headlineMedium: TextStyle(
        fontSize: 28,
        fontWeight: FontWeight.w600,
        letterSpacing: 0,
      ),
      headlineSmall: TextStyle(
        fontSize: 24,
        fontWeight: FontWeight.w600,
        letterSpacing: 0,
      ),
      titleLarge: TextStyle(
        fontSize: 22,
        fontWeight: FontWeight.w600,
        letterSpacing: 0,
      ),
      titleMedium: TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w500,
        letterSpacing: 0.15,
      ),
      titleSmall: TextStyle(
        fontSize: 14,
        fontWeight: FontWeight.w500,
        letterSpacing: 0.1,
      ),
      bodyLarge: TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w400,
        letterSpacing: 0.5,
      ),
      bodyMedium: TextStyle(
        fontSize: 14,
        fontWeight: FontWeight.w400,
        letterSpacing: 0.25,
      ),
      bodySmall: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w400,
        letterSpacing: 0.4,
      ),
      labelLarge: TextStyle(
        fontSize: 14,
        fontWeight: FontWeight.w500,
        letterSpacing: 0.1,
      ),
      labelMedium: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w500,
        letterSpacing: 0.5,
      ),
      labelSmall: TextStyle(
        fontSize: 11,
        fontWeight: FontWeight.w500,
        letterSpacing: 0.5,
      ),
    ),
  );
  
  // Professional Light Theme
  static ThemeData get lightTheme => ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: primaryBlue,
      brightness: Brightness.light,
      primary: primaryBlue,
      secondary: secondaryBlue,
      tertiary: accentTeal,
      surface: white,
      background: gray50,
      error: errorRed,
      onPrimary: white,
      onSecondary: white,
      onTertiary: white,
      onSurface: gray900,
      onBackground: gray900,
      onError: white,
    ),
    textTheme: textTheme,
    fontFamily: GoogleFonts.inter().fontFamily,
    scaffoldBackgroundColor: gray50,
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.transparent,
      elevation: 0,
      scrolledUnderElevation: 0,
      iconTheme: IconThemeData(color: gray900),
      titleTextStyle: TextStyle(
        color: gray900,
        fontSize: 20,
        fontWeight: FontWeight.w600,
      ),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: primaryBlue,
        foregroundColor: white,
        elevation: 0,
        shadowColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: borderRadiusLg,
        ),
        padding: const EdgeInsets.symmetric(
          horizontal: 24,
          vertical: 16,
        ),
        textStyle: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: primaryBlue,
        side: const BorderSide(color: primaryBlue, width: 2),
        shape: RoundedRectangleBorder(
          borderRadius: borderRadiusLg,
        ),
        padding: const EdgeInsets.symmetric(
          horizontal: 24,
          vertical: 16,
        ),
        textStyle: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(
        foregroundColor: primaryBlue,
        shape: RoundedRectangleBorder(
          borderRadius: borderRadiusLg,
        ),
        padding: const EdgeInsets.symmetric(
          horizontal: 24,
          vertical: 16,
        ),
        textStyle: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: white,
      border: OutlineInputBorder(
        borderRadius: borderRadiusLg,
        borderSide: const BorderSide(color: gray200, width: 2),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: borderRadiusLg,
        borderSide: const BorderSide(color: gray200, width: 2),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: borderRadiusLg,
        borderSide: const BorderSide(color: primaryBlue, width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: borderRadiusLg,
        borderSide: const BorderSide(color: errorRed, width: 2),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: borderRadiusLg,
        borderSide: const BorderSide(color: errorRed, width: 2),
      ),
      contentPadding: const EdgeInsets.all(16),
      hintStyle: const TextStyle(
        color: gray400,
        fontWeight: FontWeight.w500,
      ),
      labelStyle: const TextStyle(
        color: gray700,
        fontWeight: FontWeight.w600,
      ),
    ),
    cardTheme: CardTheme(
      color: white,
      elevation: 0,
      shadowColor: Colors.transparent,
      shape: RoundedRectangleBorder(
        borderRadius: borderRadius2xl,
        side: const BorderSide(color: gray200, width: 1),
      ),
      margin: const EdgeInsets.all(8),
    ),
    chipTheme: ChipThemeData(
      backgroundColor: gray100,
      selectedColor: primaryBlue,
      disabledColor: gray300,
      labelStyle: const TextStyle(
        color: gray700,
        fontSize: 12,
        fontWeight: FontWeight.w500,
      ),
      shape: RoundedRectangleBorder(
        borderRadius: borderRadius2xl,
      ),
      padding: const EdgeInsets.symmetric(
        horizontal: 12,
        vertical: 8,
      ),
    ),
    bottomNavigationBarTheme: const BottomNavigationBarThemeData(
      backgroundColor: white,
      selectedItemColor: primaryBlue,
      unselectedItemColor: gray600,
      type: BottomNavigationBarType.fixed,
      elevation: 0,
      selectedLabelStyle: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w600,
      ),
      unselectedLabelStyle: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w500,
      ),
    ),
    dialogTheme: DialogTheme(
      backgroundColor: white,
      elevation: 0,
      shadowColor: Colors.transparent,
      shape: RoundedRectangleBorder(
        borderRadius: borderRadius2xl,
      ),
      titleTextStyle: const TextStyle(
        color: gray900,
        fontSize: 20,
        fontWeight: FontWeight.w700,
      ),
      contentTextStyle: const TextStyle(
        color: gray600,
        fontSize: 16,
        fontWeight: FontWeight.w400,
      ),
    ),
    snackBarTheme: SnackBarThemeData(
      backgroundColor: gray900,
      contentTextStyle: const TextStyle(
        color: white,
        fontSize: 16,
        fontWeight: FontWeight.w500,
      ),
      shape: RoundedRectangleBorder(
        borderRadius: borderRadiusLg,
      ),
      behavior: SnackBarBehavior.floating,
    ),
  );
  
  // Professional Dark Theme
  static ThemeData get darkTheme => ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: primaryBlue,
      brightness: Brightness.dark,
      primary: primaryLight,
      secondary: secondaryBlue,
      tertiary: accentTeal,
      surface: gray800,
      background: gray900,
      error: errorRed,
      onPrimary: gray900,
      onSecondary: gray900,
      onTertiary: gray900,
      onSurface: gray100,
      onBackground: gray100,
      onError: gray900,
    ),
    textTheme: textTheme.apply(
      bodyColor: gray100,
      displayColor: gray100,
    ),
    fontFamily: GoogleFonts.inter().fontFamily,
    scaffoldBackgroundColor: gray900,
    appBarTheme: const AppBarTheme(
      backgroundColor: Colors.transparent,
      elevation: 0,
      scrolledUnderElevation: 0,
      iconTheme: IconThemeData(color: gray100),
      titleTextStyle: TextStyle(
        color: gray100,
        fontSize: 20,
        fontWeight: FontWeight.w600,
      ),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: primaryLight,
        foregroundColor: gray900,
        elevation: 0,
        shadowColor: Colors.transparent,
        shape: RoundedRectangleBorder(
          borderRadius: borderRadiusLg,
        ),
        padding: const EdgeInsets.symmetric(
          horizontal: 24,
          vertical: 16,
        ),
        textStyle: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w600,
        ),
      ),
    ),
    cardTheme: CardTheme(
      color: gray800,
      elevation: 0,
      shadowColor: Colors.transparent,
      shape: RoundedRectangleBorder(
        borderRadius: borderRadius2xl,
        side: const BorderSide(color: gray700, width: 1),
      ),
      margin: const EdgeInsets.all(8),
    ),
    bottomNavigationBarTheme: const BottomNavigationBarThemeData(
      backgroundColor: gray800,
      selectedItemColor: primaryLight,
      unselectedItemColor: gray400,
      type: BottomNavigationBarType.fixed,
      elevation: 0,
      selectedLabelStyle: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w600,
      ),
      unselectedLabelStyle: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w500,
      ),
    ),
  );
  
  // Professional Animation Durations
  static const Duration animationFast = Duration(milliseconds: 150);
  static const Duration animationNormal = Duration(milliseconds: 200);
  static const Duration animationSlow = Duration(milliseconds: 300);
  static const Duration animationBounce = Duration(milliseconds: 400);
  
  // Professional Spacing
  static const double spacing1 = 4.0;
  static const double spacing2 = 8.0;
  static const double spacing3 = 12.0;
  static const double spacing4 = 16.0;
  static const double spacing5 = 20.0;
  static const double spacing6 = 24.0;
  static const double spacing8 = 32.0;
  static const double spacing12 = 48.0;
  static const double spacing16 = 64.0;
  
  // Professional Icon Sizes
  static const double iconXs = 12.0;
  static const double iconSm = 16.0;
  static const double iconMd = 20.0;
  static const double iconLg = 24.0;
  static const double iconXl = 32.0;
  static const double icon2xl = 48.0;
  static const double icon3xl = 64.0;
  
  // Professional Status Colors
  static const Color statusOnline = successGreen;
  static const Color statusOffline = gray500;
  static const Color statusBusy = errorRed;
  static const Color statusAway = warningOrange;
  static const Color statusInProgress = infoBlue;
  
  // Professional Chart Colors
  static const List<Color> chartColors = [
    primaryBlue,
    secondaryBlue,
    accentTeal,
    successGreen,
    warningOrange,
    errorRed,
    infoBlue,
    gray500,
  ];
  
  // Professional Device Breakpoints
  static const double mobileBreakpoint = 640;
  static const double tabletBreakpoint = 768;
  static const double desktopBreakpoint = 1024;
  static const double wideBreakpoint = 1280;
  
  // Professional Page Transitions
  static const Curve transitionCurve = Curves.easeOutCubic;
  static const Duration transitionDuration = Duration(milliseconds: 300);
  
  // Professional Helper Methods
  static bool isMobile(BuildContext context) =>
      MediaQuery.of(context).size.width < mobileBreakpoint;
  
  static bool isTablet(BuildContext context) =>
      MediaQuery.of(context).size.width >= mobileBreakpoint &&
      MediaQuery.of(context).size.width < desktopBreakpoint;
  
  static bool isDesktop(BuildContext context) =>
      MediaQuery.of(context).size.width >= desktopBreakpoint;
  
  static double getResponsiveValue(
    BuildContext context, {
    required double mobile,
    required double tablet,
    required double desktop,
  }) {
    if (isMobile(context)) return mobile;
    if (isTablet(context)) return tablet;
    return desktop;
  }
}
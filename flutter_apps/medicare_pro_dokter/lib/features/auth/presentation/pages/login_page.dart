import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/app_constants.dart';
import '../../../../core/utils/error_handler.dart';
import '../providers/auth_provider.dart';

/// Professional Login Page
class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});

  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isPasswordVisible = false;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: AppTheme.backgroundGradient,
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(AppTheme.spacing6),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Logo
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
                  
                  // Title
                  const Text(
                    'MediCare Pro',
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                      letterSpacing: -1,
                    ),
                  ),
                  
                  const SizedBox(height: AppTheme.spacing2),
                  
                  // Subtitle
                  const Text(
                    'Professional Healthcare Management',
                    style: TextStyle(
                      fontSize: 16,
                      color: Colors.white70,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  
                  const SizedBox(height: AppTheme.spacing12),
                  
                  // Login Form
                  Container(
                    constraints: const BoxConstraints(maxWidth: 400),
                    padding: const EdgeInsets.all(AppTheme.spacing8),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: AppTheme.borderRadius2xl,
                      boxShadow: const [AppTheme.shadowPremium],
                    ),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Welcome Text
                          const Text(
                            'Selamat Datang',
                            style: TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.w700,
                              color: AppTheme.gray900,
                            ),
                          ),
                          
                          const SizedBox(height: AppTheme.spacing2),
                          
                          const Text(
                            'Silakan masuk dengan akun dokter Anda',
                            style: TextStyle(
                              fontSize: 16,
                              color: AppTheme.gray600,
                            ),
                          ),
                          
                          const SizedBox(height: AppTheme.spacing8),
                          
                          // Email Field
                          TextFormField(
                            controller: _emailController,
                            keyboardType: TextInputType.emailAddress,
                            textInputAction: TextInputAction.next,
                            decoration: const InputDecoration(
                              labelText: 'Email',
                              prefixIcon: Icon(Icons.email_outlined),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Email tidak boleh kosong';
                              }
                              if (!RegExp(AppConstants.regexEmail).hasMatch(value)) {
                                return 'Format email tidak valid';
                              }
                              return null;
                            },
                          ),
                          
                          const SizedBox(height: AppTheme.spacing5),
                          
                          // Password Field
                          TextFormField(
                            controller: _passwordController,
                            obscureText: !_isPasswordVisible,
                            textInputAction: TextInputAction.done,
                            decoration: InputDecoration(
                              labelText: 'Password',
                              prefixIcon: const Icon(Icons.lock_outlined),
                              suffixIcon: IconButton(
                                icon: Icon(
                                  _isPasswordVisible
                                      ? Icons.visibility_off
                                      : Icons.visibility,
                                ),
                                onPressed: () {
                                  setState(() {
                                    _isPasswordVisible = !_isPasswordVisible;
                                  });
                                },
                              ),
                            ),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Password tidak boleh kosong';
                              }
                              if (value.length < AppConstants.minPasswordLength) {
                                return 'Password minimal ${AppConstants.minPasswordLength} karakter';
                              }
                              return null;
                            },
                            onFieldSubmitted: (_) => _handleLogin(),
                          ),
                          
                          const SizedBox(height: AppTheme.spacing8),
                          
                          // Login Button
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton(
                              onPressed: authState.isLoading ? null : _handleLogin,
                              style: ElevatedButton.styleFrom(
                                padding: const EdgeInsets.symmetric(vertical: 16),
                                shape: RoundedRectangleBorder(
                                  borderRadius: AppTheme.borderRadiusXl,
                                ),
                              ),
                              child: authState.isLoading
                                  ? const SizedBox(
                                      height: 20,
                                      width: 20,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        valueColor: AlwaysStoppedAnimation<Color>(
                                          Colors.white,
                                        ),
                                      ),
                                    )
                                  : const Text(
                                      'Masuk',
                                      style: TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                            ),
                          ),
                          
                          const SizedBox(height: AppTheme.spacing6),
                          
                          // Test Credentials
                          Container(
                            padding: const EdgeInsets.all(AppTheme.spacing4),
                            decoration: BoxDecoration(
                              color: AppTheme.gray50,
                              borderRadius: AppTheme.borderRadiusLg,
                              border: Border.all(color: AppTheme.gray200),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Akun Test:',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w600,
                                    color: AppTheme.gray700,
                                  ),
                                ),
                                const SizedBox(height: AppTheme.spacing2),
                                const Text(
                                  'Email: dokter@dokterku.com',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: AppTheme.gray600,
                                  ),
                                ),
                                const Text(
                                  'Password: password',
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: AppTheme.gray600,
                                  ),
                                ),
                                const SizedBox(height: AppTheme.spacing3),
                                SizedBox(
                                  width: double.infinity,
                                  child: OutlinedButton(
                                    onPressed: () {
                                      _emailController.text = AppConstants.testDoctorEmail;
                                      _passwordController.text = AppConstants.testDoctorPassword;
                                    },
                                    style: OutlinedButton.styleFrom(
                                      padding: const EdgeInsets.symmetric(vertical: 8),
                                      side: const BorderSide(color: AppTheme.primaryBlue),
                                    ),
                                    child: const Text(
                                      'Gunakan Akun Test',
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  
                  const SizedBox(height: AppTheme.spacing8),
                  
                  // Footer
                  Text(
                    '© 2025 MediCare Pro Group',
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.white.withOpacity(0.7),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  void _handleLogin() {
    if (_formKey.currentState?.validate() ?? false) {
      ref.read(authProvider.notifier).login(
        email: _emailController.text.trim(),
        password: _passwordController.text,
      );
    }
  }
}
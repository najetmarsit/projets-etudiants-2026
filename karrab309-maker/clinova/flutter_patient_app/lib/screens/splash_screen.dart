import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/locale_service.dart';
import '../services/theme_preferences_service.dart';
import '../theme/app_theme.dart';
import '../widgets/clinova_brand_logo.dart';
import 'login_screen.dart';
import 'onboarding_screen.dart';
import 'patient_home_screen.dart';
import 'patients_list_screen.dart';
import 'doctor_home_screen.dart';
import 'nurse_home_screen.dart';

/// Splash + auto-login JWT si token valide.
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _pulse;

  @override
  void initState() {
    super.initState();
    _pulse = AnimationController(vsync: this, duration: const Duration(milliseconds: 1400))..repeat(reverse: true);
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    await ThemePreferencesService.instance.load();
    await Future<void>.delayed(const Duration(milliseconds: 600));
    if (!mounted) return;

    final onboardingDone = await ThemePreferencesService.instance.isOnboardingDone();
    if (!onboardingDone && mounted) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const OnboardingScreen()),
      );
      return;
    }

    try {
      if (ApiService.token == null || ApiService.token!.isEmpty) {
        _goLogin();
        return;
      }
      final me = await ApiService.me();
      if (!mounted) return;
      final role = me.role;
      final locale = me.locale;
      await LocaleService.applyLocaleFromUser(locale);

      if (role == 'Patient') {
        final patients = await ApiService.getPatients();
        if (!mounted) return;
        if (patients.isNotEmpty) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => PatientHomeScreen(patientId: patients.first.id)),
          );
        } else {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const PatientsListScreen(autoOpenSinglePatient: true)),
          );
        }
      } else if (role == 'Doctor') {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const DoctorHomeScreen()),
        );
      } else if (role == 'Nurse') {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const NurseHomeScreen()),
        );
      } else {
        await ApiService.logout();
        _goLogin();
      }
    } catch (_) {
      _goLogin();
    }
  }

  void _goLogin() {
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
    );
  }

  @override
  void dispose() {
    _pulse.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(gradient: AppTheme.heroGradient),
        child: SafeArea(
          child: LayoutBuilder(
            builder: (context, constraints) {
              return SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                child: ConstrainedBox(
                  constraints: BoxConstraints(minHeight: constraints.maxHeight),
                  child: FadeTransition(
                    opacity: Tween(begin: 0.85, end: 1.0).animate(_pulse),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Center(
                          child: ClinovaBrandLogo(size: ClinovaBrandLogoSize.splash),
                        ),
                        const SizedBox(height: 24),
                        Text(
                          'Clinova',
                          style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                                color: Colors.white,
                                fontWeight: FontWeight.w800,
                              ),
                        ),
                        const SizedBox(height: 32),
                        const SizedBox(
                          width: 28,
                          height: 28,
                          child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white70),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}

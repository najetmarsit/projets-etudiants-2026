import 'package:flutter/material.dart';
import '../services/theme_preferences_service.dart';
import '../theme/app_theme.dart';
import 'splash_screen.dart';

class OnboardingScreen extends StatefulWidget {
  const OnboardingScreen({super.key});

  @override
  State<OnboardingScreen> createState() => _OnboardingScreenState();
}

class _OnboardingScreenState extends State<OnboardingScreen> {
  final _page = PageController();
  int _index = 0;

  static const _slides = [
    (
      icon: Icons.medical_services_rounded,
      title: 'Bienvenue sur Clinova',
      body: 'Suivi médical moderne, sécurisé et connecté à votre établissement.'
    ),
    (
      icon: Icons.monitor_heart_rounded,
      title: 'Suivi en temps réel',
      body:
          'Tension, glycémie, rendez-vous et documents — tout au même endroit.'
    ),
    (
      icon: Icons.lock_rounded,
      title: 'Données protégées',
      body:
          'Connexion JWT et accès selon votre rôle patient, médecin ou infirmier.'
    ),
  ];

  Future<void> _finish() async {
    await ThemePreferencesService.instance.setOnboardingDone();
    if (!mounted) return;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const SplashScreen()),
    );
  }

  @override
  void dispose() {
    _page.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration:
            const BoxDecoration(gradient: AppTheme.pageBackgroundGradient),
        child: SafeArea(
          child: Column(
            children: [
              Align(
                alignment: Alignment.centerRight,
                child:
                    TextButton(onPressed: _finish, child: const Text('Passer')),
              ),
              Expanded(
                child: PageView.builder(
                  controller: _page,
                  itemCount: _slides.length,
                  onPageChanged: (i) => setState(() => _index = i),
                  itemBuilder: (context, i) {
                    final s = _slides[i];
                    return Padding(
                      padding: const EdgeInsets.all(32),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(28),
                            decoration: BoxDecoration(
                              gradient: AppTheme.heroGradient,
                              borderRadius: BorderRadius.circular(28),
                            ),
                            child: Icon(s.icon, size: 64, color: Colors.white),
                          ),
                          const SizedBox(height: 32),
                          Text(s.title,
                              textAlign: TextAlign.center,
                              style: Theme.of(context)
                                  .textTheme
                                  .headlineSmall
                                  ?.copyWith(fontWeight: FontWeight.w800)),
                          const SizedBox(height: 12),
                          Text(s.body,
                              textAlign: TextAlign.center,
                              style: Theme.of(context)
                                  .textTheme
                                  .bodyLarge
                                  ?.copyWith(color: AppTheme.textMuted)),
                        ],
                      ),
                    );
                  },
                ),
              ),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(_slides.length, (i) {
                  return AnimatedContainer(
                    duration: const Duration(milliseconds: 200),
                    margin: const EdgeInsets.symmetric(horizontal: 4),
                    width: _index == i ? 24 : 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: _index == i ? AppTheme.primary : AppTheme.border,
                      borderRadius: BorderRadius.circular(8),
                    ),
                  );
                }),
              ),
              Padding(
                padding: const EdgeInsets.all(24),
                child: FilledButton(
                  onPressed: () {
                    if (_index < _slides.length - 1) {
                      _page.nextPage(
                          duration: const Duration(milliseconds: 350),
                          curve: Curves.easeOutCubic);
                    } else {
                      _finish();
                    }
                  },
                  child: Text(
                      _index < _slides.length - 1 ? 'Suivant' : 'Commencer'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

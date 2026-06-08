import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../theme/app_theme.dart';
import '../widgets/clinova_mobile_bottom_nav.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/lazy_indexed_tab.dart';
import '../config/app_assets.dart';
import '../services/api_service.dart';
import 'login_screen.dart';
import 'patients_list_screen.dart';
import 'doctor_notifications_tab.dart';
import 'profile_tab.dart';
import 'staff_dashboard_tab.dart';

class NurseHomeScreen extends StatefulWidget {
  const NurseHomeScreen({super.key});

  @override
  State<NurseHomeScreen> createState() => _NurseHomeScreenState();
}

class _NurseHomeScreenState extends State<NurseHomeScreen> {
  int _currentIndex = 0;
  int _staffHomeRefreshNonce = 0;

  static const List<ClinovaMobileNavItem> _navItems = [
    ClinovaMobileNavItem(
      label: 'Accueil',
      icon: Icons.dashboard_outlined,
      iconSelected: Icons.dashboard_rounded,
    ),
    ClinovaMobileNavItem(
      label: 'Patients',
      icon: Icons.people_outline_rounded,
      iconSelected: Icons.people_rounded,
    ),
    ClinovaMobileNavItem(
      label: 'Notifications',
      icon: Icons.notifications_none_rounded,
      iconSelected: Icons.notifications_rounded,
    ),
    ClinovaMobileNavItem(
      label: 'Profil',
      icon: Icons.person_outline_rounded,
      iconSelected: Icons.person_rounded,
    ),
  ];

  Future<void> _logout() async {
    await ApiService.logout();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  void _refreshActiveTab() {
    setState(() => _staffHomeRefreshNonce++);
  }

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        body: Container(
          width: double.infinity,
          height: double.infinity,
          decoration: const BoxDecoration(gradient: AppTheme.pageBackgroundGradient),
          child: SafeArea(
            bottom: false,
            child: Column(
              children: [
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.fromLTRB(16, 14, 12, 16),
                  decoration: BoxDecoration(
                    gradient: AppTheme.heroGradient,
                    boxShadow: [
                      BoxShadow(
                        color: AppTheme.primary.withValues(alpha: 0.28),
                        blurRadius: 24,
                        offset: const Offset(0, 8),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(11),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.22),
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(color: Colors.white.withValues(alpha: 0.25)),
                            ),
                            child: const Icon(Icons.local_hospital_rounded, color: Colors.white, size: 28),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  'Clinova',
                                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                        fontWeight: FontWeight.w800,
                                        color: Colors.white,
                                        letterSpacing: -0.5,
                                      ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  'Espace Infirmier(ère) (mobile)',
                                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                        color: Colors.white.withValues(alpha: 0.92),
                                        fontWeight: FontWeight.w500,
                                      ),
                                ),
                              ],
                            ),
                          ),
                          Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              IconButton(
                                icon: const Icon(Icons.refresh_rounded, color: Colors.white, size: 26),
                                tooltip: 'Actualiser',
                                onPressed: _refreshActiveTab,
                              ),
                              IconButton(
                                icon: const Icon(Icons.logout_rounded, color: Colors.white, size: 26),
                                tooltip: 'Déconnexion',
                                onPressed: _logout,
                              ),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      const ClinovaIllustrationBanner(
                        assetPath: AppAssets.bannerNurse,
                        height: 88,
                        semanticLabel: 'Espace infirmier',
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: IndexedStack(
                    index: _currentIndex,
                    children: [
                      LazyIndexedTab(
                        index: 0,
                        currentIndex: _currentIndex,
                        child: const StaffDashboardTab(role: StaffRole.nurse),
                      ),
                      LazyIndexedTab(
                        index: 1,
                        currentIndex: _currentIndex,
                        child: PatientsListScreen(
                          embedInStaffHome: true,
                          staffTabActive: _currentIndex == 1,
                          staffHomeRefreshNonce: _staffHomeRefreshNonce,
                        ),
                      ),
                      LazyIndexedTab(
                        index: 2,
                        currentIndex: _currentIndex,
                        child: DoctorNotificationsTab(
                          staffTabActive: _currentIndex == 2,
                          staffHomeRefreshNonce: _staffHomeRefreshNonce,
                        ),
                      ),
                      LazyIndexedTab(
                        index: 3,
                        currentIndex: _currentIndex,
                        child: ProfileTab(
                          staffTabActive: _currentIndex == 3,
                          staffHomeRefreshNonce: _staffHomeRefreshNonce,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        bottomNavigationBar: ClinovaMobileBottomNav(
          currentIndex: _currentIndex,
          onSelect: (i) => setState(() => _currentIndex = i),
          items: _navItems,
        ),
      ),
    );
  }
}

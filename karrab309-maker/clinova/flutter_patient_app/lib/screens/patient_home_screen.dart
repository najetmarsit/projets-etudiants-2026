import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import 'patient_dashboard_tab.dart';
import 'suivi_tab.dart';
import 'appointments_tab.dart';
import 'patient_more_tab.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import 'login_screen.dart';
import '../widgets/assistant_clinova_fab.dart';
import '../widgets/clinova_mobile_bottom_nav.dart';
import '../widgets/lazy_indexed_tab.dart';

class PatientHomeScreen extends StatefulWidget {
  final int patientId;

  const PatientHomeScreen({super.key, required this.patientId});

  @override
  State<PatientHomeScreen> createState() => _PatientHomeScreenState();
}

class _PatientHomeScreenState extends State<PatientHomeScreen> {
  int _currentIndex = 0;

  List<({String label, IconData icon, IconData iconSelected})> _destinations(BuildContext context) {
    final l = AppLocalizations.of(context)!;
    return [
      (label: 'Accueil', icon: Icons.home_outlined, iconSelected: Icons.home_rounded),
      (label: l.followUp, icon: Icons.monitor_heart_outlined, iconSelected: Icons.monitor_heart_rounded),
      (label: l.tabAppointments, icon: Icons.calendar_month_outlined, iconSelected: Icons.calendar_month_rounded),
      (label: l.tabMore, icon: Icons.apps_outlined, iconSelected: Icons.apps_rounded),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final destinations = _destinations(context);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: isDark ? SystemUiOverlayStyle.light : SystemUiOverlayStyle.light,
      child: Scaffold(
        body: Container(
          width: double.infinity,
          height: double.infinity,
          decoration: BoxDecoration(
            gradient: isDark
                ? const LinearGradient(
                    colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                  )
                : AppTheme.pageBackgroundGradient,
          ),
          child: SafeArea(
            bottom: false,
            child: Column(
              children: [
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.fromLTRB(16, 14, 8, 18),
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
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(11),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.22),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.25)),
                        ),
                        child: const Icon(Icons.medical_services_rounded, color: Colors.white, size: 28),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              AppLocalizations.of(context)!.appTitle,
                              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                    fontWeight: FontWeight.w800,
                                    color: Colors.white,
                                    letterSpacing: -0.5,
                                  ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              AppLocalizations.of(context)!.postOpFollowUp,
                              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                    color: Colors.white.withValues(alpha: 0.92),
                                    fontWeight: FontWeight.w500,
                                  ),
                            ),
                          ],
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.logout_rounded, color: Colors.white, size: 26),
                        tooltip: AppLocalizations.of(context)!.logout,
                        onPressed: () async {
                          await ApiService.logout();
                          if (!context.mounted) return;
                          Navigator.of(context).pushAndRemoveUntil(
                            MaterialPageRoute(builder: (_) => const LoginScreen()),
                            (_) => false,
                          );
                        },
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: Stack(
                    children: [
                      IndexedStack(
                        index: _currentIndex,
                        children: [
                          LazyIndexedTab(
                            index: 0,
                            currentIndex: _currentIndex,
                            child: PatientDashboardTab(patientId: widget.patientId),
                          ),
                          LazyIndexedTab(
                            index: 1,
                            currentIndex: _currentIndex,
                            child: SuiviTab(patientId: widget.patientId),
                          ),
                          LazyIndexedTab(
                            index: 2,
                            currentIndex: _currentIndex,
                            child: AppointmentsTab(patientId: widget.patientId),
                          ),
                          LazyIndexedTab(
                            index: 3,
                            currentIndex: _currentIndex,
                            child: PatientMoreTab(patientId: widget.patientId),
                          ),
                        ],
                      ),
                      const Positioned(
                        right: 16,
                        bottom: 20,
                        child: AssistantClinovaFab(),
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
          items: [
            for (final d in destinations)
              ClinovaMobileNavItem(label: d.label, icon: d.icon, iconSelected: d.iconSelected),
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../models/alert_model.dart';
import '../models/notification_model.dart';
import '../models/patient_model.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../utils/error_message.dart';
import '../widgets/analytics/kpi_card.dart';
import '../widgets/analytics/mini_bar_chart.dart';
import '../widgets/analytics/mini_line_chart.dart';
import '../widgets/clinova_ui.dart' hide ClinovaEmptyState;
import '../widgets/clinova_empty_state.dart';
import 'patient_dashboard_tab.dart' show SkeletonDashboard;

enum StaffRole { doctor, nurse }

/// Tableau de bord médecin / infirmier(ère).
class StaffDashboardTab extends StatefulWidget {
  const StaffDashboardTab({super.key, required this.role});

  final StaffRole role;

  @override
  State<StaffDashboardTab> createState() => _StaffDashboardTabState();
}

class _StaffDashboardTabState extends State<StaffDashboardTab> {
  bool _loading = true;
  String? _error;
  List<PatientModel> _patients = [];
  List<NotificationModel> _notifications = [];
  List<AlertModel> _alerts = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final patients = await ApiService.getPatients();
      final notifs = await ApiService.getNotifications(limit: 30);
      List<AlertModel> alerts = [];
      if (widget.role == StaffRole.doctor) {
        alerts = await ApiService.getAlerts(limit: 30);
      }
      if (mounted) {
        setState(() {
          _patients = patients;
          _notifications = notifs;
          _alerts = alerts;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = userFacingError(e);
          _loading = false;
        });
      }
    }
  }

  List<double> _activityByDay() {
    final counts = List<double>.filled(7, 0);
    final now = DateTime.now();
    for (final n in _notifications) {
      final diff = now.difference(n.createdAt).inDays;
      if (diff >= 0 && diff < 7) counts[6 - diff] += 1;
    }
    return counts;
  }

  int get _criticalAlerts => _alerts.where((a) => a.status != 'acknowledged').length;

  @override
  Widget build(BuildContext context) {
    final isDoctor = widget.role == StaffRole.doctor;
    final title = isDoctor ? 'Tableau de bord médecin' : 'Tableau de bord infirmier';

    if (_loading) {
      return const SingleChildScrollView(padding: EdgeInsets.all(16), child: SkeletonDashboard());
    }
    if (_error != null) {
      return ClinovaEmptyState(
        title: 'Impossible de charger',
        message: _error,
        actionLabel: 'Réessayer',
        onAction: _load,
      );
    }

    final activity = _activityByDay();
    final patientTrend = List<double>.generate(6, (i) => (_patients.length / 6) * (i + 1));

    return RefreshIndicator(
      color: AppTheme.primary,
      onRefresh: _load,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ClinovaPageHeader(
              title: title,
              subtitle: 'Indicateurs en temps réel',
              icon: Icons.insights_rounded,
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: KpiCard(
                    label: 'Patients',
                    value: '${_patients.length}',
                    icon: Icons.people_rounded,
                    accentColor: AppTheme.primary,
                    onTap: () => HapticFeedback.selectionClick(),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: KpiCard(
                    label: 'Notifications',
                    value: '${_notifications.length}',
                    icon: Icons.notifications_rounded,
                    accentColor: AppTheme.violet,
                  ),
                ),
              ],
            ),
            if (isDoctor) ...[
              const SizedBox(height: 12),
              KpiCard(
                label: 'Alertes actives',
                value: '$_criticalAlerts',
                icon: Icons.warning_amber_rounded,
                trend: _criticalAlerts > 0 ? 'Priorité' : null,
                accentColor: AppTheme.accent,
              ),
            ],
            const SizedBox(height: 20),
            Text('Activité quotidienne', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
            const SizedBox(height: 8),
            ClinovaModernCard(child: MiniBarChart(values: activity, barColor: AppTheme.primary)),
            const SizedBox(height: 20),
            Text('Consultations (aperçu)', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
            const SizedBox(height: 8),
            ClinovaModernCard(
              child: MiniLineChart(
                values: patientTrend.isEmpty ? [0, 1, 2, 3, 4, 5] : patientTrend,
                lineColor: AppTheme.violet,
                height: 100,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              'Taux de présence estimé : ${(_patients.isEmpty ? 0 : ((_patients.length - _criticalAlerts).clamp(0, _patients.length) / _patients.length * 100)).round()}%',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.textMuted),
            ),
          ],
        ),
      ),
    );
  }
}

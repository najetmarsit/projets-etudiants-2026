import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import '../models/health_indicator_model.dart';
import '../models/operation_model.dart';
import '../models/payment_models.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../utils/clinova_formatters.dart';
import '../utils/error_message.dart';
import '../widgets/analytics/kpi_card.dart';
import '../widgets/analytics/medical_timeline.dart';
import '../widgets/analytics/mini_bar_chart.dart';
import '../widgets/analytics/mini_line_chart.dart';
import '../widgets/analytics/progress_ring.dart';
import '../widgets/clinova_page_route.dart';
import '../widgets/clinova_ui.dart' hide ClinovaEmptyState;
import '../widgets/clinova_empty_state.dart';
import '../widgets/skeleton_widgets.dart';
import 'appointments_tab.dart';
import 'finance_tab.dart';
import 'patient_dossier_tab.dart';
import 'suivi_tab.dart';

/// Tableau de bord patient — KPI, mini-graphiques, raccourcis.
class PatientDashboardTab extends StatefulWidget {
  const PatientDashboardTab({super.key, required this.patientId});

  final int patientId;

  @override
  State<PatientDashboardTab> createState() => _PatientDashboardTabState();
}

class _PatientDashboardTabState extends State<PatientDashboardTab> {
  bool _loading = true;
  String? _error;
  List<OperationModel> _operations = [];
  List<HealthIndicator> _indicators = [];
  PaymentBalanceModel? _balance;
  int _notifCount = 0;
  int _labCount = 0;

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
      final results = await Future.wait([
        ApiService.getOperations(),
        ApiService.getHealthIndicators(widget.patientId),
        ApiService.getPaymentBalanceForPatient(widget.patientId),
        ApiService.getNotifications(limit: 20),
        ApiService.getLabAppointments(),
      ]);
      final ops = (results[0] as List<OperationModel>)
          .where((o) => o.patientId == widget.patientId)
          .toList();
      final indicators = results[1] as List<HealthIndicator>;
      final balance = results[2] as PaymentBalanceModel;
      final notifs = results[3] as List;
      final labs = results[4] as List;

      if (mounted) {
        setState(() {
          _operations = ops;
          _indicators = indicators;
          _balance = balance;
          _notifCount = notifs.length;
          _labCount = labs.length;
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

  List<double> _rdvByMonth() {
    final now = DateTime.now();
    final counts = List<double>.filled(6, 0);
    for (final o in _operations) {
      final d = DateTime.tryParse(o.operationDate?.replaceFirst(' ', 'T') ?? '');
      if (d == null) continue;
      final diff = (now.year - d.year) * 12 + (now.month - d.month);
      if (diff >= 0 && diff < 6) counts[5 - diff] += 1;
    }
    return counts;
  }

  List<double> _glucoseSeries() {
    final sorted = List<HealthIndicator>.from(_indicators)
      ..sort((a, b) => a.recordedAt.compareTo(b.recordedAt));
    return sorted
        .where((h) => h.bloodGlucose != null)
        .map((h) => h.bloodGlucose!)
        .take(8)
        .toList();
  }

  List<double> _bpSystolicSeries() {
    final sorted = List<HealthIndicator>.from(_indicators)
      ..sort((a, b) => a.recordedAt.compareTo(b.recordedAt));
    return sorted
        .where((h) => h.bloodPressureSystolic != null)
        .map((h) => h.bloodPressureSystolic!.toDouble())
        .take(8)
        .toList();
  }

  double _healthProgress() {
    if (_indicators.isEmpty) return 0;
    final latest = _indicators.first;
    var score = 0.0;
    var parts = 0.0;
    if (latest.painLevel != null) {
      parts += 1;
      score += (10 - latest.painLevel!.clamp(0, 10)) / 10;
    }
    if (latest.bloodGlucose != null) {
      parts += 1;
      final g = latest.bloodGlucose!;
      score += (g >= 0.7 && g <= 1.4) ? 1 : 0.5;
    }
    if (latest.heartRate != null) {
      parts += 1;
      final hr = latest.heartRate!;
      score += (hr >= 60 && hr <= 100) ? 1 : 0.6;
    }
    return parts > 0 ? (score / parts).clamp(0.0, 1.0) : 0.5;
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;
    if (_loading) {
      return const SingleChildScrollView(
        padding: EdgeInsets.all(16),
        child: SkeletonDashboard(),
      );
    }
    if (_error != null) {
      return ClinovaEmptyState(
        title: l.errorGeneric,
        message: _error,
        icon: Icons.cloud_off_rounded,
        actionLabel: 'Réessayer',
        onAction: _load,
      );
    }

    final rdvChart = _rdvByMonth();
    final glucose = _glucoseSeries();
    final bp = _bpSystolicSeries();
    final progress = _healthProgress();

    return RefreshIndicator(
      color: AppTheme.primary,
      onRefresh: _load,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ClinovaPageHeader(
              title: 'Tableau de bord',
              subtitle: 'Vue d\'ensemble de votre parcours',
              icon: Icons.dashboard_rounded,
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: KpiCard(
                    label: 'Rendez-vous',
                    value: '${_operations.length}',
                    icon: Icons.calendar_month_rounded,
                    trend: rdvChart.last > 0 ? '+${rdvChart.last.toInt()}' : null,
                    accentColor: AppTheme.primary,
                    onTap: () => HapticFeedback.lightImpact(),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: KpiCard(
                    label: 'Notifications',
                    value: '$_notifCount',
                    icon: Icons.notifications_rounded,
                    accentColor: AppTheme.violet,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: KpiCard(
                    label: 'Solde à régler',
                    value: ClinovaFormatters.money(
                      context,
                      _balance?.remaining ?? 0,
                      _balance?.currency ?? 'TND',
                    ),
                    icon: Icons.payments_rounded,
                    accentColor: AppTheme.accent,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: KpiCard(
                    label: 'Analyses labo',
                    value: '$_labCount',
                    icon: Icons.biotech_rounded,
                    accentColor: AppTheme.violet,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            _sectionTitle(context, 'Évolution des rendez-vous'),
            const SizedBox(height: 8),
            ClinovaModernCard(
              child: MiniBarChart(values: rdvChart.isEmpty ? [0, 0, 0, 0, 0, 0] : rdvChart),
            ),
            const SizedBox(height: 20),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _sectionTitle(context, 'Glycémie'),
                      const SizedBox(height: 8),
                      ClinovaModernCard(
                        child: glucose.isEmpty
                            ? Text('Aucune mesure', style: TextStyle(color: AppTheme.textMuted))
                            : MiniLineChart(values: glucose, lineColor: AppTheme.violet, height: 100),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _sectionTitle(context, 'Santé'),
                      const SizedBox(height: 8),
                      ClinovaModernCard(
                        padding: const EdgeInsets.all(12),
                        child: Center(
                          child: ProgressRing(
                            progress: progress,
                            label: 'Score',
                            subtitle: 'suivi',
                            size: 88,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (bp.isNotEmpty) ...[
              const SizedBox(height: 20),
              _sectionTitle(context, 'Tension (systolique)'),
              const SizedBox(height: 8),
              ClinovaModernCard(
                child: MiniLineChart(values: bp, lineColor: AppTheme.primary, height: 110),
              ),
            ],
            const SizedBox(height: 20),
            _sectionTitle(context, 'Activité récente'),
            const SizedBox(height: 8),
            ClinovaModernCard(
              child: MedicalTimeline(
                entries: [
                  for (final o in _operations.take(4))
                    MedicalTimelineEntry(
                      title: o.operationType,
                      subtitle: o.doctorName ?? '—',
                      timeLabel: ClinovaFormatters.formatIsoDateShort(context, o.operationDate),
                      icon: Icons.event_rounded,
                      color: AppTheme.primary,
                    ),
                  for (final h in _indicators.take(3))
                    MedicalTimelineEntry(
                      title: 'Suivi médical',
                      subtitle: h.bloodGlucose != null ? 'Glycémie ${h.bloodGlucose}' : 'Mesures enregistrées',
                      timeLabel: ClinovaFormatters.formatIsoDateShort(context, h.recordedAt),
                      icon: Icons.monitor_heart_rounded,
                      color: AppTheme.violet,
                    ),
                ],
              ),
            ),
            const SizedBox(height: 20),
            _sectionTitle(context, 'Accès rapide'),
            const SizedBox(height: 8),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                _quickChip(context, 'Dossier', Icons.folder_open_rounded, () {
                  Navigator.push(context, ClinovaPageRoute(page: PatientDossierTab(patientId: widget.patientId)));
                }),
                _quickChip(context, l.tabAppointments, Icons.calendar_month_rounded, () {
                  Navigator.push(context, ClinovaPageRoute(page: AppointmentsTab(patientId: widget.patientId)));
                }),
                _quickChip(context, l.followUp, Icons.monitor_heart_rounded, () {
                  Navigator.push(context, ClinovaPageRoute(page: SuiviTab(patientId: widget.patientId)));
                }),
                _quickChip(context, l.tabFinance, Icons.receipt_long_rounded, () {
                  Navigator.push(context, ClinovaPageRoute(page: FinanceTab(patientId: widget.patientId)));
                }),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _sectionTitle(BuildContext context, String text) {
    return Text(
      text,
      style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800, letterSpacing: -0.3),
    );
  }

  Widget _quickChip(BuildContext context, String label, IconData icon, VoidCallback onTap) {
    return ActionChip(
      avatar: Icon(icon, size: 18, color: AppTheme.primary),
      label: Text(label),
      onPressed: () {
        HapticFeedback.selectionClick();
        onTap();
      },
      backgroundColor: AppTheme.primaryLight.withValues(alpha: 0.5),
      side: BorderSide(color: AppTheme.primary.withValues(alpha: 0.2)),
    );
  }
}

class SkeletonDashboard extends StatelessWidget {
  const SkeletonDashboard({super.key});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        const SkeletonWidget(height: 72, borderRadius: 16),
        const SizedBox(height: 16),
        Row(
          children: [
            Expanded(child: SkeletonWidget(height: 100, borderRadius: 16)),
            const SizedBox(width: 12),
            Expanded(child: SkeletonWidget(height: 100, borderRadius: 16)),
          ],
        ),
        const SizedBox(height: 16),
        const SkeletonWidget(height: 140, borderRadius: 16),
      ],
    );
  }
}

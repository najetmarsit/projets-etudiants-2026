import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import '../services/api_service.dart';
import '../models/health_indicator_model.dart';
import '../models/patient_model.dart';
import '../theme/app_theme.dart';
import '../utils/clinova_formatters.dart';
import '../utils/error_message.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/skeleton_widgets.dart';
import '../config/app_assets.dart';

class SuiviTab extends StatefulWidget {
  final int patientId;

  const SuiviTab({super.key, required this.patientId});

  @override
  State<SuiviTab> createState() => _SuiviTabState();
}

class _SuiviTabState extends State<SuiviTab> {
  List<HealthIndicator> _indicators = [];
  PatientModel? _patient;
  bool _loading = true;
  String? _error;

  int _ts(String? isoish) {
    if (isoish == null || isoish.isEmpty) return 0;
    final s = (isoish.contains(' ') && !isoish.contains('T'))
        ? isoish.replaceFirst(' ', 'T')
        : isoish;
    return DateTime.tryParse(s)?.millisecondsSinceEpoch ?? 0;
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({bool forceRefresh = false}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final results = await Future.wait([
        ApiService.getHealthIndicators(widget.patientId,
            forceRefresh: forceRefresh),
        ApiService.getPatient(widget.patientId, forceRefresh: forceRefresh),
      ]);
      final list = results[0] as List<HealthIndicator>;
      final patient = results[1] as PatientModel;
      // Robustesse : si l'API renvoie des dates non-ISO, on trie quand même correctement.
      list.sort((a, b) => _ts(b.recordedAt).compareTo(_ts(a.recordedAt)));
      if (mounted) {
        setState(() {
          _indicators = list;
          _patient = patient;
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

  String _painLabel(AppLocalizations l, int? p) {
    if (p == null) return '—';
    if (p <= 3) return l.followUpPainLow;
    if (p <= 6) return l.followUpPainModerate;
    return l.followUpPainSevere;
  }

  String _tension(HealthIndicator h) {
    if (h.bloodPressureSystolic == null || h.bloodPressureDiastolic == null) {
      return '—';
    }
    return '${h.bloodPressureSystolic}/${h.bloodPressureDiastolic}';
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;
    return RefreshIndicator(
      color: AppTheme.primary,
      onRefresh: () => _load(forceRefresh: true),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ClinovaPageHeader(
              title: l.postOpFollowUp,
              subtitle: l.followUpSubtitle,
              icon: Icons.monitor_heart_rounded,
              trailing: ClinovaHeaderThumb(
                assetPath: AppAssets.illustrationCareTeam,
                semanticLabel: l.postOpFollowUp,
              ),
            ),
            const SizedBox(height: 18),
            ClinovaModernCard(
              child: Text(
                l.followUpReadOnlyVitalsHint,
                style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: AppTheme.textMuted,
                      height: 1.45,
                    ),
              ),
            ),
            if (_patient != null) ...[
              const SizedBox(height: 22),
              Text(
                l.followUpDoctorObs,
                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w700, color: AppTheme.primary),
              ),
              const SizedBox(height: 10),
              ClinovaModernCard(
                child: Text(
                  _patient!.doctorObservations?.trim().isNotEmpty == true
                      ? _patient!.doctorObservations!
                      : l.followUpDoctorObsEmpty,
                  style: Theme.of(context)
                      .textTheme
                      .bodyMedium
                      ?.copyWith(height: 1.45),
                ),
              ),
            ],
            const SizedBox(height: 22),
            Text(l.followUpLastReadings,
                style: Theme.of(context)
                    .textTheme
                    .titleSmall
                    ?.copyWith(fontWeight: FontWeight.w700)),
            const SizedBox(height: 12),
            if (_loading)
              const SizedBox(
                height: 120,
                child: SkeletonList(itemCount: 4),
              )
            else if (_error != null)
              ClinovaModernCard(
                child: Column(
                  children: [
                    Text(_error!,
                        textAlign: TextAlign.center,
                        style: TextStyle(
                            color: Colors.red.shade800,
                            fontWeight: FontWeight.w700)),
                    const SizedBox(height: 12),
                    Text(l.errorRetryHint,
                        textAlign: TextAlign.center,
                        style: TextStyle(
                            color: AppTheme.textMuted,
                            fontWeight: FontWeight.w600)),
                    const SizedBox(height: 16),
                    FilledButton(onPressed: _load, child: Text(l.actionRetry)),
                  ],
                ),
              )
            else if (_indicators.isEmpty)
              ClinovaModernCard(
                child: ClinovaEmptyState(
                  title: 'Aucune donnée',
                  text: l.followUpNoData,
                  icon: Icons.monitor_heart_outlined,
                ),
              )
            else
              _buildIndicatorsContent(l),
          ],
        ),
      ),
    );
  }

  String _vitalLabel(String raw) => ClinovaFormatters.vitalLabelWithoutParenSuffix(raw);

  Widget _buildIndicatorsContent(AppLocalizations l) {
    final last = _indicators.first;
    final when = ClinovaFormatters.formatIsoDateTime(context, last.recordedAt);
    final heartLabel = _vitalLabel(l.followUpStatHeart);
    final tempLabel = _vitalLabel(l.followUpStatTemp);
    final glucoseLabel = _vitalLabel(l.followUpStatGlucose);
    final bpLabel = _vitalLabel(l.followUpStatBp);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        ClinovaModernCard(
          child: Column(
            children: [
              Wrap(
                alignment: WrapAlignment.spaceAround,
                spacing: 12,
                runSpacing: 16,
                children: [
                  _statChip(
                    Icons.favorite_rounded,
                    '$heartLabel\n',
                    last.heartRate != null ? '${last.heartRate} bpm' : '—',
                    AppTheme.primary,
                  ),
                  _statChip(
                    Icons.thermostat_rounded,
                    '$tempLabel\n',
                    '${last.temperature ?? "—"} °C',
                    last.temperature != null && last.temperature! > 38
                        ? Colors.red
                        : AppTheme.violet,
                  ),
                  _statChip(
                    Icons.bloodtype_rounded,
                    '$glucoseLabel\n',
                    last.bloodGlucose != null
                        ? '${last.bloodGlucose} mmol/L'
                        : '—',
                    Colors.teal.shade700,
                  ),
                  _statChip(
                    Icons.monitor_heart_outlined,
                    '$bpLabel\n',
                    _tension(last),
                    AppTheme.violet,
                  ),
                  _statChip(Icons.healing_rounded, l.followUpStatPain,
                      _painLabel(l, last.painLevel), AppTheme.primary),
                ],
              ),
              const SizedBox(height: 14),
              Text(
                l.followUpLastUpdated(when),
                style: Theme.of(context)
                    .textTheme
                    .bodySmall
                    ?.copyWith(color: AppTheme.textMuted),
              ),
            ],
          ),
        ),
        const SizedBox(height: 18),
        Text(l.followUpHistoryRecent,
            style: Theme.of(context)
                .textTheme
                .titleSmall
                ?.copyWith(fontWeight: FontWeight.w700)),
        const SizedBox(height: 10),
        ..._indicators.take(8).map(
              (h) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: ClinovaModernCard(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
                  child: ListTile(
                    leading: Icon(Icons.history_rounded,
                        color: AppTheme.primary.withValues(alpha: 0.9)),
                    title: Text(
                      [
                        if (h.heartRate != null) 'FC ${h.heartRate} bpm',
                        '${l.followUpStatTempShort} ${h.temperature ?? "—"} °C',
                        if (h.bloodGlucose != null)
                          '${l.followUpStatGlucoseShort} ${h.bloodGlucose}',
                        if (h.bloodPressureSystolic != null &&
                            h.bloodPressureDiastolic != null)
                          'TA ${_tension(h)}',
                        '${l.followUpStatPainShort} ${h.painLevel ?? "—"}/10',
                      ].join(' · '),
                      style: const TextStyle(fontSize: 13),
                    ),
                    subtitle: Text(
                      ClinovaFormatters.formatIsoDateTime(
                          context, h.recordedAt),
                      style: const TextStyle(fontSize: 12),
                    ),
                  ),
                ),
              ),
            ),
      ],
    );
  }

  Widget _statChip(IconData icon, String label, String value, Color color) {
    return SizedBox(
      width: 140,
      child: Column(
        children: [
          Icon(icon, size: 32, color: color),
          const SizedBox(height: 6),
          Text(label,
              textAlign: TextAlign.center,
              style: const TextStyle(
                  fontSize: 11, color: AppTheme.textMuted, height: 1.2)),
          Text(value,
              textAlign: TextAlign.center,
              style: TextStyle(
                  fontSize: 16, fontWeight: FontWeight.w800, color: color)),
        ],
      ),
    );
  }
}

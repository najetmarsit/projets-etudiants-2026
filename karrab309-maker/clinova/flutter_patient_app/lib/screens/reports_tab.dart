import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import '../services/api_service.dart';
import '../models/report_model.dart';
import '../theme/app_theme.dart';
import '../utils/clinova_formatters.dart';
import '../utils/error_message.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/skeleton_widgets.dart';
import '../config/app_assets.dart';

/// Rapports médicaux (comptes rendus) associés au patient.
class ReportsTab extends StatefulWidget {
  final int patientId;

  const ReportsTab({super.key, required this.patientId});

  @override
  State<ReportsTab> createState() => _ReportsTabState();
}

class _ReportsTabState extends State<ReportsTab> {
  List<ReportModel> _reports = [];
  bool _loading = true;
  String? _error;

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
      final list = await ApiService.getReports(widget.patientId);
      if (mounted) {
        setState(() {
          _reports = list;
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

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;
    return RefreshIndicator(
      color: AppTheme.primary,
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        children: [
          ClinovaPageHeader(
            title: l.reportsTitle,
            subtitle: l.reportsSubtitle,
            icon: Icons.medical_information_outlined,
            trailing: ClinovaHeaderThumb(
              assetPath: AppAssets.illustrationDocuments,
              semanticLabel: l.reportsTitle,
            ),
          ),
          const SizedBox(height: 18),
          if (_loading)
            const SkeletonList(itemCount: 4)
          else if (_error != null)
            ClinovaModernCard(
              child: Column(
                children: [
                  Text(_error!, textAlign: TextAlign.center, style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 12),
                  Text(l.errorRetryHint, textAlign: TextAlign.center, style: TextStyle(color: AppTheme.textMuted, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 16),
                  FilledButton(onPressed: _load, child: Text(l.actionRetry)),
                ],
              ),
            )
          else if (_reports.isEmpty)
            ClinovaModernCard(
              child: ClinovaEmptyState(
                title: 'Aucun rapport',
                text: l.noReports,
                icon: Icons.assignment_outlined,
                illustrationAsset: AppAssets.bannerWellness,
                showIconBadge: false,
              ),
            )
          else
            ..._reports.map(
              (r) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: ClinovaModernCard(
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const ClinovaListTileThumbnail(
                        assetPath: AppAssets.illustrationDocuments,
                        width: 70,
                        height: 70,
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              r.reportType,
                              style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16, height: 1.25),
                            ),
                            const SizedBox(height: 10),
                            if (r.createdAt != null)
                              ClinovaPill(
                                text: ClinovaFormatters.formatIsoDateTime(context, r.createdAt),
                                variant: ClinovaPillVariant.muted,
                              ),
                            if (r.content != null && r.content!.trim().isNotEmpty) ...[
                              const SizedBox(height: 12),
                              Text(r.content!, style: const TextStyle(height: 1.45)),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

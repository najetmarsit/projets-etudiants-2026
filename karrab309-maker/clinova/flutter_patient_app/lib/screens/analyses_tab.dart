import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import '../services/api_service.dart';
import '../models/report_model.dart';
import '../models/lab_document_model.dart';
import '../theme/app_theme.dart';
import '../utils/clinova_formatters.dart';
import '../utils/error_message.dart';
import '../widgets/clinova_ui.dart';
import '../config/app_assets.dart';

class AnalysesTab extends StatefulWidget {
  final int patientId;

  const AnalysesTab({super.key, required this.patientId});

  @override
  State<AnalysesTab> createState() => _AnalysesTabState();
}

class _AnalysesTabState extends State<AnalysesTab> {
  List<ReportModel> _reports = [];
  List<LabDocumentModel> _labDocs = [];
  bool _loading = true;
  String? _error;
  int? _openingLabId;
  /// 0 = laboratoire (PDF), 1 = rapports médecin
  int _section = 0;

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
      final reports = await ApiService.getReports(widget.patientId);
      final labs = await ApiService.getLabDocuments();
      final mine = labs.where((d) => d.patientId == widget.patientId).toList();
      if (mounted) {
        setState(() {
          _reports = reports;
          _labDocs = mine;
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

  Future<void> _openLabPdf(LabDocumentModel d) async {
    setState(() => _openingLabId = d.id);
    try {
      await ApiService.openLabDocumentPdf(d.id, d.originalFilename);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
      }
    } finally {
      if (mounted) setState(() => _openingLabId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;
    return RefreshIndicator(
      color: AppTheme.primary,
      onRefresh: _load,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ClinovaPageHeader(
              title: l.analyses,
              subtitle: l.moreAnalysesDesc,
              icon: Icons.picture_as_pdf_rounded,
              trailing: ClinovaHeaderThumb(
                assetPath: AppAssets.illustrationDocuments,
                semanticLabel: l.analyses,
              ),
            ),
            const SizedBox(height: 18),
            if (!_loading && _error == null)
              ClinovaModernCard(
                padding: const EdgeInsets.all(10),
                child: SegmentedButton<int>(
                  style: SegmentedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 6),
                    side: BorderSide(color: AppTheme.border.withValues(alpha: 0.75)),
                    selectedForegroundColor: AppTheme.primaryDark,
                    selectedBackgroundColor: AppTheme.primaryLight,
                    foregroundColor: AppTheme.textMuted,
                    backgroundColor: AppTheme.surface,
                  ),
                  showSelectedIcon: false,
                  segments: [
                    ButtonSegment<int>(
                      value: 0,
                      icon: const Icon(Icons.science_rounded, size: 20),
                      label: const Text('Laboratoire', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 12)),
                    ),
                    ButtonSegment<int>(
                      value: 1,
                      icon: const Icon(Icons.article_rounded, size: 20),
                      label: Text(l.reportsTitle, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 11)),
                    ),
                  ],
                  selected: {_section},
                  onSelectionChanged: (Set<int> next) {
                    if (next.isEmpty) return;
                    setState(() => _section = next.first);
                  },
                ),
              ),
            if (!_loading && _error == null) const SizedBox(height: 14),
            if (_loading)
              const Center(child: Padding(padding: EdgeInsets.all(28), child: CircularProgressIndicator()))
            else if (_error != null)
              ClinovaModernCard(
                child: Column(
                  children: [
                    Text(
                      _error!,
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 16),
                    FilledButton(onPressed: _load, child: Text(l.actionRetry)),
                  ],
                ),
              )
            else ...[
              if (_section == 0) ...[
                if (_labDocs.isEmpty)
                  ClinovaModernCard(
                    child: ClinovaEmptyState(
                      title: 'Aucune analyse',
                      text: 'Aucune analyse PDF pour le moment.',
                      icon: Icons.picture_as_pdf_outlined,
                      illustrationAsset: AppAssets.illustrationSmall,
                      showIconBadge: false,
                    ),
                  )
                else
                  ..._labDocs.map(
                    (d) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: ClinovaModernCard(
                        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
                        child: ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          leading: Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: AppTheme.primaryLight.withValues(alpha: 0.85),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(color: AppTheme.primary.withValues(alpha: 0.2)),
                            ),
                            child: Icon(Icons.picture_as_pdf_rounded, color: AppTheme.primaryDark, size: 26),
                          ),
                          title: Text(d.title, style: const TextStyle(fontWeight: FontWeight.w800)),
                          subtitle: Text(d.originalFilename, style: const TextStyle(fontSize: 12, color: AppTheme.textMuted)),
                          trailing: _openingLabId == d.id
                              ? const SizedBox(width: 26, height: 26, child: CircularProgressIndicator(strokeWidth: 2))
                              : IconButton(
                                  icon: const Icon(Icons.open_in_new_rounded),
                                  color: AppTheme.primary,
                                  onPressed: () => _openLabPdf(d),
                                ),
                        ),
                      ),
                    ),
                  ),
              ],
              if (_section == 1) ...[
                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton.icon(
                    onPressed: _load,
                    icon: const Icon(Icons.refresh_rounded, size: 18),
                    label: Text(l.actionRetry),
                  ),
                ),
                const SizedBox(height: 4),
                if (_reports.isEmpty)
                  ClinovaModernCard(
                    child: ClinovaEmptyState(
                      title: l.noReports,
                      text: l.reportsSubtitle,
                      icon: Icons.medical_information_outlined,
                      illustrationAsset: AppAssets.bannerWellness,
                      showIconBadge: false,
                    ),
                  )
                else
                  ..._reports.map(
                    (r) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: ClinovaModernCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              r.reportType,
                              style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w900),
                            ),
                            const SizedBox(height: 10),
                            if (r.createdAt != null)
                              ClinovaPill(
                                text: ClinovaFormatters.formatIsoDateTime(context, r.createdAt),
                                variant: ClinovaPillVariant.muted,
                              ),
                            if (r.content != null && r.content!.isNotEmpty) ...[
                              const SizedBox(height: 12),
                              Text(r.content!, style: Theme.of(context).textTheme.bodyMedium?.copyWith(height: 1.45)),
                            ],
                          ],
                        ),
                      ),
                    ),
                  ),
              ],
            ],
          ],
        ),
      ),
    );
  }
}

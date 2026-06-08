import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import '../config/api_config.dart';
import '../services/api_service.dart';
import '../models/patient_model.dart';
import '../theme/app_theme.dart';
import '../utils/clinova_formatters.dart';
import '../utils/error_message.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/skeleton_widgets.dart';
import '../config/app_assets.dart';

/// Informations administratives et médicales du patient (hors facturation détaillée).
class PatientDossierTab extends StatefulWidget {
  final int patientId;

  const PatientDossierTab({super.key, required this.patientId});

  @override
  State<PatientDossierTab> createState() => _PatientDossierTabState();
}

class _PatientDossierTabState extends State<PatientDossierTab> {
  PatientModel? _patient;
  bool _loading = true;
  String? _error;
  String? _publicUrl;

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
        ApiService.myPatient(forceRefresh: forceRefresh),
        ApiService.getPatient(widget.patientId, forceRefresh: forceRefresh),
      ]);
      final my = results[0] as Map<String, dynamic>;
      final p = results[1] as PatientModel;
      final token = (my['qr_public_token'] ?? '') as String;
      final publicUrl = token.isEmpty ? null : '${ApiConfig.publicAppOrigin}/public/dossier/${Uri.encodeComponent(token)}';
      if (mounted) {
        setState(() {
          _patient = p;
          _publicUrl = publicUrl;
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

  bool _hasMedicalContent(PatientModel p) {
    return [
      p.diagnosis,
      p.prescribedTreatment,
      p.currentIllness,
      p.medicalHistory,
      p.doctorObservations,
      p.preOpReport,
      p.postOpReport,
    ].any((s) => s != null && s.trim().isNotEmpty);
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;
    return RefreshIndicator(
      color: AppTheme.primary,
      onRefresh: () => _load(forceRefresh: true),
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        children: [
          ClinovaPageHeader(
            title: l.dossierTitle,
            subtitle: l.dossierSubtitle,
            icon: Icons.folder_open_rounded,
            trailing: ClinovaHeaderThumb(
              assetPath: AppAssets.illustrationDocuments,
              semanticLabel: l.dossierTitle,
            ),
          ),
          const SizedBox(height: 18),
          if (_loading)
            const SkeletonList(itemCount: 5)
          else if (_error != null)
            ClinovaModernCard(
              child: Column(
                children: [
                  Text(_error!, textAlign: TextAlign.center, style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 12),
                  Text(l.errorRetryHint, textAlign: TextAlign.center, style: TextStyle(color: AppTheme.textMuted, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 16),
                  FilledButton(onPressed: () => _load(forceRefresh: true), child: Text(l.actionRetry)),
                ],
              ),
            )
          else if (_publicUrl != null) ...[
            ClinovaModernCard(
              padding: EdgeInsets.zero,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const ClinovaCardTopMedia(
                    assetPath: AppAssets.bannerWellness,
                    height: 76,
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(18, 16, 18, 18),
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(18),
                            border: Border.all(color: Colors.black.withValues(alpha: 0.06)),
                          ),
                          child: QrImageView(
                            data: _publicUrl!,
                            size: 200,
                            backgroundColor: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 12),
                        OutlinedButton.icon(
                          onPressed: () async {
                            final u = Uri.tryParse(_publicUrl!);
                            if (u != null) await launchUrl(u, mode: LaunchMode.externalApplication);
                          },
                          icon: const Icon(Icons.open_in_new_rounded),
                          label: Text(l.openPublicLink),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            if (_patient != null) ..._buildSections(context, l, _patient!),
          ] else if (_patient != null)
            ..._buildSections(context, l, _patient!),
        ],
      ),
    );
  }

  List<Widget> _buildSections(BuildContext context, AppLocalizations l, PatientModel p) {
    return [
      ClinovaModernCard(
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const ClinovaListTileThumbnail(assetPath: AppAssets.bannerPatient),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    l.identitySection,
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 10),
                  _row(l.fieldFullName, p.displayName),
                  _row(l.fieldAgeGender, p.subtitle),
                  if (p.phone != null && p.phone!.isNotEmpty) _row(l.fieldPhone, p.phone!),
                  if (p.address != null && p.address!.isNotEmpty) _row(l.fieldAddress, p.address!),
                ],
              ),
            ),
          ],
        ),
      ),
      const SizedBox(height: 12),
      if (p.admissionAt != null || p.dischargeAt != null) ...[
        ClinovaModernCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                l.hospitalizationSection,
                style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 10),
              if (p.admissionAt != null) _row(l.fieldAdmission, ClinovaFormatters.formatIsoDateTime(context, p.admissionAt)),
              if (p.dischargeAt != null) _row(l.fieldDischarge, ClinovaFormatters.formatIsoDateTime(context, p.dischargeAt)),
            ],
          ),
        ),
        const SizedBox(height: 12),
      ],
      if (p.assignedDoctorName != null && p.assignedDoctorName!.isNotEmpty) ...[
        ClinovaModernCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                l.doctorSection,
                style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 8),
              Text(
                l.doctorLine(p.assignedDoctorName!),
                style: const TextStyle(fontWeight: FontWeight.w600, height: 1.35),
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
      ],
      ClinovaModernCard(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              l.medicalSection,
              style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 10),
            if (!_hasMedicalContent(p))
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Row(
                  children: [
                    Icon(Icons.info_outline_rounded, color: AppTheme.textMuted, size: 22),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        l.medicalSectionEmpty,
                        style: TextStyle(color: AppTheme.textMuted, height: 1.4),
                      ),
                    ),
                  ],
                ),
              )
            else ...[
              if (p.diagnosis != null && p.diagnosis!.isNotEmpty) _block(l.labelDiagnosis, p.diagnosis!),
              if (p.prescribedTreatment != null && p.prescribedTreatment!.isNotEmpty) _block(l.labelTreatment, p.prescribedTreatment!),
              if (p.currentIllness != null && p.currentIllness!.isNotEmpty) _block(l.labelIllness, p.currentIllness!),
              if (p.medicalHistory != null && p.medicalHistory!.isNotEmpty) _block(l.labelHistory, p.medicalHistory!),
              if (p.doctorObservations != null && p.doctorObservations!.isNotEmpty) _block(l.labelObservations, p.doctorObservations!),
              if (p.preOpReport != null && p.preOpReport!.isNotEmpty) _block(l.labelPreOp, p.preOpReport!),
              if (p.postOpReport != null && p.postOpReport!.isNotEmpty) _block(l.labelPostOp, p.postOpReport!),
            ],
          ],
        ),
      ),
    ];
  }

  Widget _row(String k, String v) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 112,
            child: Text(k, style: TextStyle(color: AppTheme.textMuted, fontSize: 13)),
          ),
          Expanded(child: Text(v, style: const TextStyle(fontWeight: FontWeight.w500, height: 1.35))),
        ],
      ),
    );
  }

  Widget _block(String title, String body) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: TextStyle(color: AppTheme.textMuted, fontSize: 12, fontWeight: FontWeight.w600)),
          const SizedBox(height: 4),
          Text(body, style: const TextStyle(height: 1.4)),
        ],
      ),
    );
  }
}

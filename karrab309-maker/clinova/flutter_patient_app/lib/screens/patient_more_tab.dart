import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import 'analyses_tab.dart';
import 'messages_tab.dart';
import 'profile_tab.dart';
import 'patient_dossier_tab.dart';
import 'finance_tab.dart';
import 'reports_tab.dart';
import 'settings_screen.dart';
import '../theme/app_theme.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/clinova_page_route.dart';
import '../config/app_assets.dart';

/// Accès aux écrans secondaires : analyses labo, messages, profil.
class PatientMoreTab extends StatelessWidget {
  final int patientId;

  const PatientMoreTab({super.key, required this.patientId});

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;
    return SingleChildScrollView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          ClinovaPageHeader(
            title: l.moreTabTitle,
            subtitle: l.moreTabSubtitle,
            icon: Icons.apps_rounded,
            trailing: ClinovaHeaderThumb(
              assetPath: AppAssets.bannerWellness,
              semanticLabel: l.moreTabTitle,
            ),
          ),
          const SizedBox(height: 18),
          _tile(
            context,
            illustrationAsset: AppAssets.bannerPatient,
            icon: Icons.folder_open_rounded,
            title: l.tabPatientRecord,
            subtitle: l.moreTabSubtitle,
            onTap: () => Navigator.of(context).push(
              ClinovaPageRoute<void>(
                page: _SubPageScaffold(title: l.tabPatientRecord, child: PatientDossierTab(patientId: patientId)),
              ),
            ),
          ),
          _tile(
            context,
            illustrationAsset: AppAssets.bannerSecondary,
            icon: Icons.receipt_long_rounded,
            title: l.tabFinance,
            subtitle: l.moreTabSubtitle,
            onTap: () => Navigator.of(context).push(
              ClinovaPageRoute<void>(
                page: _SubPageScaffold(title: l.tabFinance, child: FinanceTab(patientId: patientId)),
              ),
            ),
          ),
          _tile(
            context,
            illustrationAsset: AppAssets.illustrationDocuments,
            icon: Icons.article_rounded,
            title: l.tabReports,
            subtitle: l.moreTabSubtitle,
            onTap: () => Navigator.of(context).push(
              ClinovaPageRoute<void>(
                page: _SubPageScaffold(title: l.tabReports, child: ReportsTab(patientId: patientId)),
              ),
            ),
          ),
          _tile(
            context,
            illustrationAsset: AppAssets.illustrationDocuments,
            icon: Icons.biotech_rounded,
            title: l.analyses,
            subtitle: l.moreAnalysesDesc,
            onTap: () => Navigator.of(context).push(
              ClinovaPageRoute<void>(
                page: _SubPageScaffold(title: l.analyses, child: AnalysesTab(patientId: patientId)),
              ),
            ),
          ),
          _tile(
            context,
            illustrationAsset: AppAssets.bannerSecondary,
            icon: Icons.chat_rounded,
            title: l.messages,
            subtitle: l.moreMessagesDesc,
            onTap: () => Navigator.of(context).push(
              ClinovaPageRoute<void>(
                page: _SubPageScaffold(title: l.messages, child: MessagesTab(patientId: patientId)),
              ),
            ),
          ),
          _tile(
            context,
            illustrationAsset: AppAssets.bannerPatient,
            icon: Icons.person_rounded,
            title: l.profile,
            subtitle: l.moreProfileDesc,
            onTap: () => Navigator.of(context).push(
              ClinovaPageRoute<void>(
                page: _SubPageScaffold(title: l.profile, child: const ProfileTab(patientSlimLayout: true)),
              ),
            ),
          ),
          _tile(
            context,
            illustrationAsset: AppAssets.bannerWellness,
            icon: Icons.settings_rounded,
            title: 'Paramètres',
            subtitle: 'Thème, accessibilité, taille du texte',
            onTap: () => Navigator.of(context).push(
              ClinovaPageRoute<void>(page: const SettingsScreen()),
            ),
          ),
        ],
      ),
    );
  }

  Widget _tile(
    BuildContext context, {
    required String illustrationAsset,
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          child: ClinovaModernCard(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            child: Row(
              children: [
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    ClinovaListTileThumbnail(
                      assetPath: illustrationAsset,
                      width: 80,
                      height: 80,
                      borderRadius: 18,
                    ),
                    Positioned(
                      right: -4,
                      bottom: -4,
                      child: Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: AppTheme.surface,
                          shape: BoxShape.circle,
                          border: Border.all(color: AppTheme.border.withValues(alpha: 0.8)),
                          boxShadow: AppTheme.cardShadow,
                        ),
                        child: Icon(icon, color: AppTheme.primary, size: 18),
                      ),
                    ),
                  ],
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                      const SizedBox(height: 4),
                      Text(subtitle, style: TextStyle(color: AppTheme.textMuted, fontSize: 12, height: 1.35)),
                    ],
                  ),
                ),
                Icon(Icons.chevron_right_rounded, color: AppTheme.textMuted),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SubPageScaffold extends StatelessWidget {
  final String title;
  final Widget child;

  const _SubPageScaffold({required this.title, required this.child});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(gradient: AppTheme.pageBackgroundGradient),
        child: child,
      ),
    );
  }
}

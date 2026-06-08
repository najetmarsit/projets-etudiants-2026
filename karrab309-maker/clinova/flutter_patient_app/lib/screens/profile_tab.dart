import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import 'package:image_picker/image_picker.dart';
import '../services/api_service.dart';
import '../models/user_model.dart';
import '../theme/app_theme.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/skeleton_widgets.dart';
import '../config/app_assets.dart';

class ProfileTab extends StatefulWidget {
  /// Mode patient (app mobile) : pas de grande bannière sous le titre, vignette uniquement dans l’en-tête.
  final bool patientSlimLayout;

  final bool staffTabActive;
  final int staffHomeRefreshNonce;

  const ProfileTab({
    super.key,
    this.patientSlimLayout = false,
    this.staffTabActive = false,
    this.staffHomeRefreshNonce = 0,
  });

  @override
  State<ProfileTab> createState() => _ProfileTabState();
}

class _ProfileTabState extends State<ProfileTab> {
  bool _loading = true;
  String? _error;
  String? _message;
  UserModel? _user;
  String? _availability;
  bool _savingAvailability = false;
  Timer? _availabilityHeartbeat;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _availabilityHeartbeat?.cancel();
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant ProfileTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.staffTabActive && widget.staffHomeRefreshNonce != oldWidget.staffHomeRefreshNonce) {
      _load();
    }
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final u = await ApiService.me();
      String? availability;
      if (u.role == 'Doctor') {
        try {
          final row = await ApiService.doctorAvailabilityGet();
          availability = (row['status'] as String?) ?? 'available';
        } catch (_) {
          availability = 'available';
        }
      }
      if (mounted) {
        setState(() {
          _user = u;
          _availability = availability;
          _loading = false;
        });

        // Heartbeat : maintenir last_seen_at à jour automatiquement (sans spammer le web).
        _availabilityHeartbeat?.cancel();
        if (u.role == 'Doctor') {
          _availabilityHeartbeat = Timer.periodic(const Duration(seconds: 60), (_) async {
            try {
              await ApiService.doctorAvailabilityGet();
            } catch (_) {
              // ignore
            }
          });
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _loading = false;
        });
      }
    }
  }

  Future<void> _setAvailability(String status) async {
    if (_savingAvailability) return;
    setState(() {
      _savingAvailability = true;
      _message = null;
      _error = null;
    });
    try {
      final row = await ApiService.doctorAvailabilitySet(status);
      final next = (row['status'] as String?) ?? status;
      if (!mounted) return;
      setState(() {
        _availability = next;
        _savingAvailability = false;
        _message = 'Disponibilité mise à jour.';
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _savingAvailability = false;
        _error = e.toString();
      });
    }
  }

  Future<void> _pickAndUploadPhoto() async {
    final picker = ImagePicker();
    final XFile? x = await picker.pickImage(source: ImageSource.gallery, maxWidth: 800, imageQuality: 85);
    if (x == null) return;
    setState(() {
      _message = null;
      _error = null;
    });
    try {
      final bytes = await x.readAsBytes();
      var name = x.name.trim();
      if (name.isEmpty) name = 'profile.jpg';
      final u = await ApiService.uploadProfilePhoto(bytes, filename: name);
      if (mounted) {
        setState(() {
          _user = u;
          _message = 'Photo enregistrée.';
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _error = e.toString());
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;

    Widget pageIntro() => Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ClinovaPageHeader(
              title: l.profile,
              subtitle: l.moreProfileDesc,
              icon: Icons.person_rounded,
              trailing: widget.patientSlimLayout
                  ? ClinovaHeaderThumb(
                      assetPath: AppAssets.bannerPatient,
                      semanticLabel: l.profile,
                    )
                  : null,
            ),
            if (!widget.patientSlimLayout) ...[
              const SizedBox(height: 12),
              ClinovaIllustrationBanner(
                assetPath: AppAssets.bannerWellness,
                height: 88,
                semanticLabel: l.profile,
              ),
            ],
            const SizedBox(height: 16),
          ],
        );

    if (_loading) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        children: [
          pageIntro(),
          ClinovaModernCard(
            padding: const EdgeInsets.symmetric(vertical: 32),
            child: const Padding(
              padding: EdgeInsets.all(16),
              child: SkeletonList(itemCount: 4),
            ),
          ),
        ],
      );
    }
    if (_error != null) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        children: [
          pageIntro(),
          ClinovaModernCard(
            child: Column(
              children: [
                Text(_error!, textAlign: TextAlign.center, style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w700)),
                const SizedBox(height: 16),
                FilledButton(onPressed: _load, child: Text(l.actionRetry)),
              ],
            ),
          ),
        ],
      );
    }
    final u = _user;
    if (u == null) return const SizedBox.shrink();

    final profilePhotoUrl = u.profilePhotoUrl;
    final name = u.name.isNotEmpty ? u.name : u.username;

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        pageIntro(),
        ClinovaModernCard(
          child: Column(
            children: [
              GestureDetector(
                onTap: _pickAndUploadPhoto,
                child: Stack(
                  alignment: Alignment.bottomRight,
                  children: [
                    CircleAvatar(
                      radius: 58,
                      backgroundColor: AppTheme.primaryLight,
                      backgroundImage: profilePhotoUrl != null ? NetworkImage(profilePhotoUrl) : null,
                      child: profilePhotoUrl == null
                          ? Text(
                              name.isNotEmpty ? name[0].toUpperCase() : '?',
                              style: const TextStyle(fontSize: 48, color: AppTheme.primary, fontWeight: FontWeight.w900),
                            )
                          : null,
                    ),
                    Container(
                      padding: const EdgeInsets.all(9),
                      decoration: BoxDecoration(
                        gradient: AppTheme.heroGradient,
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(color: AppTheme.primary.withValues(alpha: 0.35), blurRadius: 12, offset: const Offset(0, 4)),
                        ],
                      ),
                      child: const Icon(Icons.camera_alt_rounded, color: Colors.white, size: 22),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              Text(name, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900, letterSpacing: -0.4)),
              const SizedBox(height: 4),
              Text(u.email, style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: AppTheme.textMuted, fontWeight: FontWeight.w600)),
              const SizedBox(height: 14),
              if (_message != null)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.green.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(AppTheme.radiusSm),
                    border: Border.all(color: Colors.green.withValues(alpha: 0.22)),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.check_circle_rounded, color: Colors.green.shade700),
                      const SizedBox(width: 10),
                      Expanded(child: Text(_message!, style: TextStyle(color: Colors.green.shade800, fontWeight: FontWeight.w700))),
                    ],
                  ),
                ),
            ],
          ),
        ),
        if (u.role == 'Doctor') ...[
          const SizedBox(height: 12),
          ClinovaModernCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text('Disponibilité', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
                const SizedBox(height: 8),
                Text(
                  'Ce statut est synchronisé en temps réel avec le web (Réception).',
                  style: TextStyle(color: AppTheme.textMuted, fontWeight: FontWeight.w600, height: 1.35),
                ),
                const SizedBox(height: 14),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    ChoiceChip(
                      label: const Text('Disponible'),
                      selected: (_availability ?? 'available') == 'available',
                      onSelected: _savingAvailability ? null : (_) => _setAvailability('available'),
                    ),
                    ChoiceChip(
                      label: const Text('Occupé'),
                      selected: (_availability ?? 'available') == 'busy',
                      onSelected: _savingAvailability ? null : (_) => _setAvailability('busy'),
                    ),
                    ChoiceChip(
                      label: const Text('En appel'),
                      selected: (_availability ?? 'available') == 'on_call',
                      onSelected: _savingAvailability ? null : (_) => _setAvailability('on_call'),
                    ),
                    ChoiceChip(
                      label: const Text('Hors ligne'),
                      selected: (_availability ?? 'available') == 'offline',
                      onSelected: _savingAvailability ? null : (_) => _setAvailability('offline'),
                    ),
                  ],
                ),
                if (_savingAvailability) ...[
                  const SizedBox(height: 12),
                  const LinearProgressIndicator(minHeight: 3),
                ],
              ],
            ),
          ),
        ],
      ],
    );
  }
}

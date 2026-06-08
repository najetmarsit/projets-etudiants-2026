import 'package:flutter/material.dart';
import '../models/alert_model.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/skeleton_widgets.dart';
import '../services/cache_service.dart';
import '../config/app_assets.dart';
import 'patient_detail_screen.dart';

class DoctorAlertsTab extends StatefulWidget {
  final bool staffTabActive;
  final int staffHomeRefreshNonce;

  const DoctorAlertsTab({
    super.key,
    this.staffTabActive = false,
    this.staffHomeRefreshNonce = 0,
  });

  @override
  State<DoctorAlertsTab> createState() => _DoctorAlertsTabState();
}

class _DoctorAlertsTabState extends State<DoctorAlertsTab>
    with WidgetsBindingObserver {
  bool _loading = true;
  String? _error;
  List<AlertModel> _items = const [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _load();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _load();
  }

  @override
  void didUpdateWidget(DoctorAlertsTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.staffTabActive &&
        widget.staffHomeRefreshNonce != oldWidget.staffHomeRefreshNonce) {
      _load();
    }
  }

  Future<void> _load({bool forceRefresh = false}) async {
    const cacheKey = 'alerts:all:200';
    final cached = !forceRefresh
        ? CacheService().getStale<List<AlertModel>>(cacheKey)
        : null;
    if (cached != null) {
      setState(() {
        _items = List<AlertModel>.from(cached)
          ..sort((a, b) => b.createdAt.compareTo(a.createdAt));
        _loading = false;
        _error = null;
      });
      if (!CacheService().isStale(cacheKey)) return;
    } else {
      setState(() {
        _loading = true;
        _error = null;
      });
    }
    try {
      final list = await ApiService.getAlerts(forceRefresh: true);
      list.sort((a, b) => b.createdAt.compareTo(a.createdAt));
      if (!mounted) return;
      setState(() {
        _items = list;
        _loading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.message;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _error = 'Erreur réseau';
        _loading = false;
      });
    }
  }

  Future<void> _ack(AlertModel a) async {
    try {
      await ApiService.acknowledgeAlert(a.id);
      await _load();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      color: AppTheme.primary,
      onRefresh: () => _load(forceRefresh: true),
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        children: [
          const ClinovaPageHeader(
            title: 'Alertes',
            subtitle: 'Événements à traiter (suivi patient)',
            icon: Icons.warning_amber_rounded,
          ),
          const SizedBox(height: 12),
          const ClinovaIllustrationBanner(
            assetPath: AppAssets.illustrationCareTeam,
            height: 88,
            semanticLabel: 'Alertes',
          ),
          const SizedBox(height: 16),
          if (_loading)
            const SkeletonList(itemCount: 5)
          else if (_error != null)
            ClinovaModernCard(
              child: Column(
                children: [
                  Text(_error!,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                          color: Colors.red.shade800,
                          fontWeight: FontWeight.w700)),
                  const SizedBox(height: 16),
                  FilledButton(
                      onPressed: _load, child: const Text('Réessayer')),
                ],
              ),
            )
          else if (_items.isEmpty)
            const ClinovaModernCard(
              child: ClinovaEmptyState(
                title: 'Aucune alerte',
                text: 'Tout est calme.',
                icon: Icons.notifications_none_rounded,
                illustrationAsset: AppAssets.bannerDoctor,
                showIconBadge: false,
              ),
            )
          else
            ..._items.map((a) {
              final urgent = a.isUrgent;
              final canAck = !a.isAcked;
              final status = a.status.replaceAll('_', ' ');
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: InkWell(
                  borderRadius: BorderRadius.circular(20),
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(
                        builder: (_) =>
                            PatientDetailScreen(patientId: a.patientId)),
                  ),
                  child: ClinovaModernCard(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 12, vertical: 10),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CircleAvatar(
                          backgroundColor: urgent
                              ? Colors.red.shade50
                              : AppTheme.accentLight,
                          child: Icon(
                            Icons.warning_amber_rounded,
                            color:
                                urgent ? Colors.red.shade700 : AppTheme.accent,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  ClinovaPill(
                                    text: canAck ? 'À traiter' : 'Traité',
                                    variant: canAck
                                        ? ClinovaPillVariant.newItem
                                        : ClinovaPillVariant.muted,
                                  ),
                                  const SizedBox(width: 8),
                                  ClinovaPill(
                                    text: urgent ? 'Urgent' : 'Normal',
                                    variant: urgent
                                        ? ClinovaPillVariant.danger
                                        : ClinovaPillVariant.muted,
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(
                                a.message,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w900),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                'Type: ${a.indicatorType} • Valeur: ${a.value} • Statut: $status',
                                style: TextStyle(
                                    color: AppTheme.textMuted,
                                    fontWeight: FontWeight.w600),
                              ),
                              const SizedBox(height: 10),
                              if (canAck)
                                FilledButton(
                                  onPressed: () => _ack(a),
                                  style: FilledButton.styleFrom(
                                    backgroundColor: urgent
                                        ? Colors.red.shade700
                                        : AppTheme.primary,
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 14, vertical: 10),
                                  ),
                                  child: const Text('Marquer lu'),
                                )
                              else
                                Row(
                                  children: [
                                    Icon(Icons.check_circle_rounded,
                                        color: Colors.green.shade700, size: 18),
                                    const SizedBox(width: 6),
                                    Text('Traité',
                                        style: TextStyle(
                                            color: Colors.green.shade800,
                                            fontWeight: FontWeight.w800)),
                                  ],
                                ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              );
            }),
        ],
      ),
    );
  }
}

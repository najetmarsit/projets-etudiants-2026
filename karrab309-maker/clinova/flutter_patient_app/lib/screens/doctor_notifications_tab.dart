import 'package:flutter/material.dart';
import '../models/notification_model.dart';
import '../services/api_service.dart';
import '../services/cache_service.dart';
import '../theme/app_theme.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/skeleton_widgets.dart';
import '../config/app_assets.dart';

class DoctorNotificationsTab extends StatefulWidget {
  final bool staffTabActive;
  final int staffHomeRefreshNonce;

  const DoctorNotificationsTab({
    super.key,
    this.staffTabActive = false,
    this.staffHomeRefreshNonce = 0,
  });

  @override
  State<DoctorNotificationsTab> createState() => _DoctorNotificationsTabState();
}

class _DoctorNotificationsTabState extends State<DoctorNotificationsTab>
    with WidgetsBindingObserver {
  bool _loading = true;
  String? _error;
  List<NotificationModel> _items = const [];

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
  void didUpdateWidget(DoctorNotificationsTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.staffTabActive &&
        widget.staffHomeRefreshNonce != oldWidget.staffHomeRefreshNonce) {
      _load();
    }
  }

  Future<void> _load({bool forceRefresh = false}) async {
    final cacheKey = 'notifications:all:80';
    final cached = !forceRefresh
        ? CacheService().getStale<List<NotificationModel>>(cacheKey)
        : null;
    if (cached != null) {
      setState(() {
        _items = cached;
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
      final list =
          await ApiService.getNotifications(limit: 80, forceRefresh: true);
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

  Future<void> _ack(NotificationModel n) async {
    try {
      await ApiService.acknowledgeNotification(n.id);
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
            title: 'Notifications',
            subtitle: 'Dernières notifications reçues',
            icon: Icons.notifications_rounded,
          ),
          const SizedBox(height: 12),
          const ClinovaIllustrationBanner(
            assetPath: AppAssets.emptySchedule,
            height: 88,
            semanticLabel: 'Notifications',
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
                title: 'Aucune notification',
                text: 'Les nouvelles notifications apparaîtront ici.',
                icon: Icons.notifications_off_rounded,
                illustrationAsset: AppAssets.bannerSecondary,
                showIconBadge: false,
              ),
            )
          else
            ..._items.map((n) {
              final urgent = n.isUrgent;
              final canAck = n.isUnacknowledged;
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: ClinovaModernCard(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      CircleAvatar(
                        backgroundColor:
                            urgent ? Colors.red.shade50 : AppTheme.primaryLight,
                        child: Icon(
                          urgent
                              ? Icons.priority_high_rounded
                              : Icons.notifications_rounded,
                          color:
                              urgent ? Colors.red.shade700 : AppTheme.primary,
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
                                  text: urgent ? 'Urgent' : 'Info',
                                  variant: urgent
                                      ? ClinovaPillVariant.danger
                                      : ClinovaPillVariant.muted,
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    n.title,
                                    style: const TextStyle(
                                        fontWeight: FontWeight.w900),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            Text(
                              (n.body ?? '').isEmpty ? n.type : (n.body ?? ''),
                              style: TextStyle(
                                  color: AppTheme.textMuted,
                                  fontWeight: FontWeight.w600,
                                  height: 1.35),
                              maxLines: 3,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 10),
                            Row(
                              children: [
                                if (canAck)
                                  FilledButton(
                                    onPressed: () => _ack(n),
                                    style: FilledButton.styleFrom(
                                      backgroundColor: urgent
                                          ? Colors.red.shade700
                                          : AppTheme.primary,
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 14, vertical: 10),
                                    ),
                                    child: const Text('Accuser'),
                                  )
                                else
                                  Row(
                                    children: [
                                      Icon(Icons.check_circle_rounded,
                                          color: Colors.green.shade700,
                                          size: 18),
                                      const SizedBox(width: 6),
                                      Text('Ack',
                                          style: TextStyle(
                                              color: Colors.green.shade800,
                                              fontWeight: FontWeight.w800)),
                                    ],
                                  ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }),
        ],
      ),
    );
  }
}

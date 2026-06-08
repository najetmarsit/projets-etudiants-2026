import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import '../models/patient_model.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/skeleton_widgets.dart';
import '../config/app_assets.dart';
import 'login_screen.dart';
import 'patient_detail_screen.dart';
import 'patient_home_screen.dart';

class PatientsListScreen extends StatefulWidget {
  /// Si vrai (patient sans dossier puis création côté web) : passage auto à l’accueil dès qu’un dossier existe.
  final bool autoOpenSinglePatient;

  /// Accueil infirmier/médecin : pas de bouton déconnexion ici (déjà dans l’en-tête global).
  final bool embedInStaffHome;

  /// Synchronisé avec l’onglet actif de l’accueil staff + [staffHomeRefreshNonce].
  final bool staffTabActive;
  final int staffHomeRefreshNonce;

  const PatientsListScreen({
    super.key,
    this.autoOpenSinglePatient = false,
    this.embedInStaffHome = false,
    this.staffTabActive = false,
    this.staffHomeRefreshNonce = 0,
  });

  @override
  State<PatientsListScreen> createState() => _PatientsListScreenState();
}

class _PatientsListScreenState extends State<PatientsListScreen>
    with WidgetsBindingObserver {
  final _scroll = ScrollController();
  final _searchCtrl = TextEditingController();
  List<PatientModel> _patients = [];
  String? _nextCursor;
  bool _hasMore = false;
  bool _loading = true;
  bool _loadingMore = false;
  String _searchQuery = '';
  String? _error;
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _scroll.addListener(_onScroll);
    _loadPatients(reset: true);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scroll.removeListener(_onScroll);
    _scroll.dispose();
    _searchCtrl.dispose();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  void _onScroll() {
    if (!_hasMore ||
        _loadingMore ||
        _loading ||
        _nextCursor == null ||
        _nextCursor!.isEmpty) return;
    final pos = _scroll.position;
    if (pos.pixels > pos.maxScrollExtent - 420) {
      _loadMore();
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _loadPatients(reset: true, forceRefresh: true);
    }
  }

  @override
  void didUpdateWidget(PatientsListScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.embedInStaffHome &&
        widget.staffTabActive &&
        widget.staffHomeRefreshNonce != oldWidget.staffHomeRefreshNonce) {
      _loadPatients(reset: true, forceRefresh: true);
    }
  }

  void _onSearchChanged(String value) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 380), () {
      final q = value.trim();
      if (q == _searchQuery) return;
      _searchQuery = q;
      _loadPatients(reset: true);
    });
  }

  Future<void> _loadPatients(
      {bool reset = false, bool forceRefresh = false}) async {
    if (reset) {
      _nextCursor = null;
      _hasMore = false;
    }
    if (!mounted) return;
    setState(() {
      _error = null;
      if (reset || _patients.isEmpty) {
        _loading = true;
      }
    });
    try {
      final page = await ApiService.getPatientsPage(
        cursor: null,
        search: _searchQuery,
        forceRefresh: forceRefresh,
      );
      if (!mounted) return;
      if (widget.autoOpenSinglePatient && page.items.isNotEmpty) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
              builder: (_) =>
                  PatientHomeScreen(patientId: page.items.first.id)),
        );
        return;
      }
      setState(() {
        _patients = List<PatientModel>.from(page.items);
        _nextCursor = page.nextCursor;
        _hasMore = page.hasMore;
        _loading = false;
      });
    } on ApiException catch (e) {
      if (e.statusCode == 401) {
        ApiService.setToken(null);
        if (!mounted) return;
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (_) => false,
        );
        return;
      }
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

  Future<void> _loadMore() async {
    if (_loadingMore ||
        !_hasMore ||
        _nextCursor == null ||
        _nextCursor!.isEmpty) return;
    setState(() => _loadingMore = true);
    try {
      final page = await ApiService.getPatientsPage(
        cursor: _nextCursor,
        search: _searchQuery,
        forceRefresh: false,
      );
      if (!mounted) return;
      setState(() {
        _patients.addAll(page.items);
        _nextCursor = page.nextCursor;
        _hasMore = page.hasMore;
        _loadingMore = false;
      });
    } catch (_) {
      if (mounted) setState(() => _loadingMore = false);
    }
  }

  Future<void> _logout() async {
    await ApiService.logout();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration:
            const BoxDecoration(gradient: AppTheme.pageBackgroundGradient),
        child: SafeArea(
          child: RefreshIndicator(
            color: AppTheme.primary,
            onRefresh: () => _loadPatients(reset: true, forceRefresh: true),
            child: CustomScrollView(
              controller: _scroll,
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      ClinovaPageHeader(
                        title: 'Patients',
                        subtitle: 'Liste paginée — recherche instantanée',
                        icon: Icons.people_rounded,
                        trailing: widget.embedInStaffHome
                            ? const ClinovaHeaderThumb(
                                assetPath: AppAssets.bannerSecondary,
                                semanticLabel: 'Patients',
                              )
                            : Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  IconButton(
                                    icon: const Icon(Icons.refresh_rounded),
                                    onPressed: _loading
                                        ? null
                                        : () => _loadPatients(
                                            reset: true, forceRefresh: true),
                                    tooltip: 'Actualiser',
                                    color: AppTheme.textMuted,
                                  ),
                                  IconButton(
                                    icon: const Icon(Icons.logout_rounded),
                                    onPressed: _logout,
                                    tooltip:
                                        AppLocalizations.of(context)!.logout,
                                    color: AppTheme.textMuted,
                                  ),
                                ],
                              ),
                      ),
                      if (!widget.embedInStaffHome) ...[
                        const SizedBox(height: 12),
                        const ClinovaIllustrationBanner(
                          assetPath: AppAssets.bannerSecondary,
                          height: 92,
                          semanticLabel: 'Patients',
                        ),
                      ],
                      const SizedBox(height: 14),
                      TextField(
                        controller: _searchCtrl,
                        onChanged: _onSearchChanged,
                        textInputAction: TextInputAction.search,
                        decoration: InputDecoration(
                          hintText: 'Rechercher (nom, prénom, téléphone, CIN…)',
                          prefixIcon: const Icon(Icons.search_rounded),
                          filled: true,
                          fillColor: Colors.white,
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(14)),
                          isDense: true,
                        ),
                      ),
                      const SizedBox(height: 16),
                    ]),
                  ),
                ),
                if (_loading && _patients.isEmpty)
                  const SliverToBoxAdapter(
                    child: Padding(
                      padding: EdgeInsets.all(16),
                      child: SkeletonList(itemCount: 6),
                    ),
                  )
                else if (_error != null)
                  SliverToBoxAdapter(
                    child: ClinovaModernCard(
                      padding: EdgeInsets.zero,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const ClinovaCardTopMedia(
                            assetPath: AppAssets.illustrationSmall,
                            height: 96,
                          ),
                          Padding(
                            padding: const EdgeInsets.all(18),
                            child: Column(
                              children: [
                                Text(_error!,
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                        color: Colors.red.shade800,
                                        fontWeight: FontWeight.w700)),
                                const SizedBox(height: 16),
                                FilledButton(
                                    onPressed: () => _loadPatients(reset: true),
                                    child: const Text('Réessayer')),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  )
                else if (_patients.isEmpty)
                  const SliverToBoxAdapter(
                    child: ClinovaModernCard(
                      child: ClinovaEmptyState(
                        title: 'Aucun dossier',
                        text:
                            'Aucun patient ne correspond à votre recherche ou à votre périmètre.',
                        icon: Icons.folder_off_rounded,
                        illustrationAsset: AppAssets.illustrationDocuments,
                      ),
                    ),
                  )
                else
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                    sliver: SliverList(
                      delegate: SliverChildBuilderDelegate(
                        (context, index) {
                          final p = _patients[index];
                          return Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: InkWell(
                              borderRadius:
                                  BorderRadius.circular(AppTheme.radiusXl),
                              onTap: () => Navigator.of(context).push(
                                MaterialPageRoute(
                                    builder: (_) =>
                                        PatientDetailScreen(patientId: p.id)),
                              ),
                              child: ClinovaModernCard(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 12, vertical: 8),
                                child: Row(
                                  children: [
                                    CircleAvatar(
                                      backgroundColor: AppTheme.primaryLight,
                                      backgroundImage:
                                          p.user?.profilePhotoUrl != null
                                              ? NetworkImage(
                                                  p.user!.profilePhotoUrl!)
                                              : null,
                                      child: p.user?.profilePhotoUrl == null
                                          ? Icon(Icons.person_rounded,
                                              color: AppTheme.primary)
                                          : null,
                                    ),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(p.displayName,
                                              style: const TextStyle(
                                                  fontWeight: FontWeight.w900)),
                                          const SizedBox(height: 2),
                                          Text(p.subtitle,
                                              style: TextStyle(
                                                  color: AppTheme.textMuted,
                                                  fontWeight: FontWeight.w600)),
                                        ],
                                      ),
                                    ),
                                    Icon(Icons.chevron_right_rounded,
                                        color: AppTheme.textMuted),
                                  ],
                                ),
                              ),
                            ),
                          );
                        },
                        childCount: _patients.length,
                      ),
                    ),
                  ),
                if (_loadingMore)
                  const SliverToBoxAdapter(
                    child: Padding(
                      padding: EdgeInsets.all(16),
                      child: Center(
                          child: SizedBox(
                              width: 28,
                              height: 28,
                              child:
                                  CircularProgressIndicator(strokeWidth: 2.5))),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import '../services/api_service.dart';
import '../models/lab_appointment_model.dart';
import '../models/operation_model.dart';
import '../theme/app_theme.dart';
import '../utils/clinova_formatters.dart';
import '../utils/error_message.dart';
import '../config/app_assets.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/skeleton_widgets.dart';

/// Rendez-vous et interventions (opérations) liés au patient.
class AppointmentsTab extends StatefulWidget {
  final int patientId;

  const AppointmentsTab({super.key, required this.patientId});

  @override
  State<AppointmentsTab> createState() => _AppointmentsTabState();
}

class _AppointmentsTabState extends State<AppointmentsTab> {
  List<OperationModel> _ops = [];
  List<LabAppointmentModel> _lab = [];
  bool _loading = true;
  String? _error;

  /// 0 = laboratoire, 1 = médecin
  int _appointmentSection = 0;

  // Labo (calendrier + demande)
  DateTime _cursorMonth =
      DateTime(DateTime.now().year, DateTime.now().month, 1);
  DateTime? _labScheduledAt;
  final _labNoteCtrl = TextEditingController();
  bool _labSaving = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _labNoteCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final allOps = await ApiService.getOperations();
      final mineOps =
          allOps.where((o) => o.patientId == widget.patientId).toList();
      mineOps.sort(
          (a, b) => (b.operationDate ?? '').compareTo(a.operationDate ?? ''));

      final lab = await ApiService.getLabAppointments();
      lab.sort((a, b) => b.scheduledAt.compareTo(a.scheduledAt));
      if (mounted) {
        setState(() {
          _ops = mineOps;
          _lab = lab;
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

  void _shiftMonth(int delta) {
    setState(() {
      _cursorMonth = DateTime(_cursorMonth.year, _cursorMonth.month + delta, 1);
    });
  }

  List<DateTime> _calendarCells() {
    final first = _cursorMonth;
    final startDow = (first.weekday % 7); // Mon=1..Sun=7 => Sun=0
    final start = first.subtract(Duration(days: startDow));
    return List<DateTime>.generate(42, (i) => start.add(Duration(days: i)));
  }

  bool _hasLabOnDay(DateTime day) {
    final y = day.year, m = day.month, d = day.day;
    for (final it in _lab) {
      if (it.scheduledAt.isEmpty) continue;
      final dt = DateTime.tryParse(it.scheduledAt);
      if (dt == null) continue;
      if (dt.year == y && dt.month == m && dt.day == d) return true;
    }
    return false;
  }

  Future<void> _pickLabDateTime() async {
    final now = DateTime.now();
    final pickedDate = await showDatePicker(
      context: context,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 2),
      initialDate: _labScheduledAt ?? now,
    );
    if (pickedDate == null) return;
    if (!mounted) return;
    final pickedTime = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(_labScheduledAt ?? now),
    );
    if (pickedTime == null) return;
    setState(() {
      _labScheduledAt = DateTime(pickedDate.year, pickedDate.month,
          pickedDate.day, pickedTime.hour, pickedTime.minute);
    });
  }

  Future<void> _submitLab() async {
    if (_labSaving || _labScheduledAt == null) return;
    setState(() => _labSaving = true);
    try {
      await ApiService.createLabAppointment(
        scheduledAtIso: _labScheduledAt!.toIso8601String(),
        note:
            _labNoteCtrl.text.trim().isEmpty ? null : _labNoteCtrl.text.trim(),
      );
      _labNoteCtrl.clear();
      _labScheduledAt = null;
      await _load();
    } finally {
      if (mounted) setState(() => _labSaving = false);
    }
  }

  Future<void> _cancelLab(LabAppointmentModel a) async {
    await ApiService.cancelLabAppointment(a.id);
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;
    final cells = _calendarCells();
    final today = DateTime.now();
    return RefreshIndicator(
      color: AppTheme.primary,
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        children: [
          ClinovaPageHeader(
            title: l.appointmentsTitle,
            subtitle: l.appointmentsSubtitle,
            icon: Icons.event_available_rounded,
            trailing: ClinovaHeaderThumb(
              assetPath: AppAssets.emptySchedule,
              semanticLabel: l.appointmentsTitle,
            ),
          ),
          const SizedBox(height: 18),
          if (!_loading && _error == null)
            ClinovaModernCard(
              padding: const EdgeInsets.all(10),
              child: SegmentedButton<int>(
                style: SegmentedButton.styleFrom(
                  padding:
                      const EdgeInsets.symmetric(vertical: 10, horizontal: 6),
                  visualDensity: VisualDensity.standard,
                  side: BorderSide(
                      color: AppTheme.border.withValues(alpha: 0.75)),
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
                    label: Text(l.labTitle,
                        style: const TextStyle(
                            fontWeight: FontWeight.w800, fontSize: 12)),
                  ),
                  ButtonSegment<int>(
                    value: 1,
                    icon: const Icon(Icons.medical_services_rounded, size: 20),
                    label: Text(
                      l.doctorAppointmentsTitle,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontWeight: FontWeight.w800, fontSize: 11),
                    ),
                  ),
                ],
                selected: {_appointmentSection},
                onSelectionChanged: (Set<int> next) {
                  if (next.isEmpty) return;
                  setState(() => _appointmentSection = next.first);
                },
              ),
            ),
          if (!_loading && _error == null) const SizedBox(height: 14),
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
          else ...[
            if (_appointmentSection == 0)
              ClinovaModernCard(
                padding: EdgeInsets.zero,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const ClinovaCardTopMedia(
                      assetPath: AppAssets.illustrationSmall,
                      height: 64,
                    ),
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                  child: Text(l.labTitle,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.w900))),
                              IconButton(
                                onPressed: () => _shiftMonth(-1),
                                tooltip: l.labCalendarPrev,
                                icon: const Icon(Icons.chevron_left_rounded),
                              ),
                              IconButton(
                                onPressed: () => _shiftMonth(1),
                                tooltip: l.labCalendarNext,
                                icon: const Icon(Icons.chevron_right_rounded),
                              ),
                            ],
                          ),
                          Text(
                            ClinovaFormatters.formatMonthYear(
                                context, _cursorMonth),
                            textAlign: TextAlign.center,
                            style: TextStyle(
                                color: AppTheme.textMuted,
                                fontWeight: FontWeight.w700),
                          ),
                          const SizedBox(height: 10),
                          GridView.count(
                            crossAxisCount: 7,
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            children: [
                              for (final w
                                  in ClinovaFormatters.weekdayShortHeaders(
                                      context))
                                Center(
                                    child: Text(w,
                                        style: TextStyle(
                                            color: AppTheme.textMuted,
                                            fontWeight: FontWeight.w800,
                                            fontSize: 11))),
                            ],
                          ),
                          const SizedBox(height: 6),
                          GridView.builder(
                            itemCount: cells.length,
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 7,
                              mainAxisSpacing: 6,
                              crossAxisSpacing: 6,
                            ),
                            itemBuilder: (_, i) {
                              final d = cells[i];
                              final muted = d.month != _cursorMonth.month;
                              final isToday = d.year == today.year &&
                                  d.month == today.month &&
                                  d.day == today.day;
                              final hasLab = _hasLabOnDay(d);
                              return Container(
                                decoration: BoxDecoration(
                                  color: isToday
                                      ? AppTheme.primary.withValues(alpha: 0.14)
                                      : Colors.black.withValues(alpha: 0.03),
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(
                                      color:
                                          Colors.white.withValues(alpha: 0.10)),
                                ),
                                child: Stack(
                                  alignment: Alignment.center,
                                  children: [
                                    Text(
                                      '${d.day}',
                                      style: TextStyle(
                                        color: muted
                                            ? AppTheme.textMuted
                                                .withValues(alpha: 0.55)
                                            : null,
                                        fontWeight: FontWeight.w800,
                                      ),
                                    ),
                                    if (hasLab)
                                      Positioned(
                                        bottom: 6,
                                        child: Container(
                                          width: 6,
                                          height: 6,
                                          decoration: BoxDecoration(
                                              color: AppTheme.primary,
                                              borderRadius:
                                                  BorderRadius.circular(99)),
                                        ),
                                      ),
                                  ],
                                ),
                              );
                            },
                          ),
                          const SizedBox(height: 10),
                          Row(
                            children: [
                              Container(
                                  width: 6,
                                  height: 6,
                                  decoration: BoxDecoration(
                                      color: AppTheme.primary,
                                      borderRadius: BorderRadius.circular(99))),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  l.labCalendarLegend,
                                  style: TextStyle(
                                      color: AppTheme.textMuted,
                                      fontWeight: FontWeight.w700,
                                      fontSize: 12),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text(l.labWhenLabel,
                              style: TextStyle(
                                  color: AppTheme.textMuted,
                                  fontWeight: FontWeight.w800,
                                  fontSize: 12)),
                          const SizedBox(height: 8),
                          OutlinedButton(
                            onPressed: _labSaving ? null : _pickLabDateTime,
                            child: Text(
                              _labScheduledAt == null
                                  ? l.labWhenPick
                                  : ClinovaFormatters.formatDateTime(
                                      context, _labScheduledAt!),
                              style:
                                  const TextStyle(fontWeight: FontWeight.w700),
                            ),
                          ),
                          const SizedBox(height: 10),
                          Text(l.labNoteLabel,
                              style: TextStyle(
                                  color: AppTheme.textMuted,
                                  fontWeight: FontWeight.w800,
                                  fontSize: 12)),
                          const SizedBox(height: 8),
                          TextField(
                            controller: _labNoteCtrl,
                            enabled: !_labSaving,
                            decoration: InputDecoration(
                              hintText: l.labNotePlaceholder,
                              border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(14)),
                              isDense: true,
                              contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 12, vertical: 12),
                            ),
                          ),
                          const SizedBox(height: 12),
                          FilledButton(
                            onPressed: (_labSaving || _labScheduledAt == null)
                                ? null
                                : _submitLab,
                            child: _labSaving
                                ? const SizedBox(
                                    height: 18,
                                    width: 18,
                                    child: CircularProgressIndicator(
                                        strokeWidth: 2))
                                : Text(l.labSubmit),
                          ),
                          const SizedBox(height: 12),
                          Text(l.labListTitle,
                              style:
                                  const TextStyle(fontWeight: FontWeight.w900)),
                          const SizedBox(height: 8),
                          if (_lab.isEmpty)
                            ClinovaEmptyState(
                              title: l.commonNoData,
                              text: l.labSubtitle,
                              icon: Icons.biotech_rounded,
                              illustrationAsset: AppAssets.illustrationSmall,
                              showIconBadge: false,
                            )
                          else
                            ..._lab.map((a) {
                              final dt = DateTime.tryParse(a.scheduledAt);
                              final when = dt == null
                                  ? a.scheduledAt
                                  : ClinovaFormatters.formatDateTime(
                                      context, dt);
                              final pending =
                                  a.status.toLowerCase() == 'pending';
                              return Column(
                                children: [
                                  const Divider(height: 16),
                                  Row(
                                    children: [
                                      Expanded(
                                          child: Text(
                                              '$when — ${a.status.toUpperCase()}',
                                              style: const TextStyle(
                                                  fontWeight:
                                                      FontWeight.w700))),
                                      if (pending)
                                        TextButton(
                                          onPressed: () => _cancelLab(a),
                                          child: Text(l.commonCancel,
                                              style: const TextStyle(
                                                  color: Colors.red)),
                                        ),
                                    ],
                                  ),
                                  if (a.note != null &&
                                      a.note!.trim().isNotEmpty)
                                    Align(
                                      alignment: Alignment.centerLeft,
                                      child: Padding(
                                        padding: const EdgeInsets.only(top: 6),
                                        child: Text(a.note!,
                                            style: TextStyle(
                                                color: AppTheme.textMuted,
                                                height: 1.35)),
                                      ),
                                    ),
                                ],
                              );
                            }),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            if (_appointmentSection == 1) ...[
              if (_ops.isEmpty)
                ClinovaModernCard(
                  child: ClinovaEmptyState(
                    title: l.commonNoData,
                    text: l.noAppointments,
                    icon: Icons.event_busy_rounded,
                    illustrationAsset: AppAssets.bannerWellness,
                    showIconBadge: false,
                  ),
                )
              else
                ..._ops.map(
                  (o) => Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: ClinovaModernCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const ClinovaListTileThumbnail(
                                assetPath: AppAssets.bannerPatient,
                                width: 68,
                                height: 62,
                                borderRadius: 16,
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  o.operationType,
                                  style: const TextStyle(
                                      fontWeight: FontWeight.w900,
                                      fontSize: 16,
                                      height: 1.25),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              if (o.operationDate != null)
                                ClinovaPill(
                                  text: ClinovaFormatters.formatIsoDateTime(
                                      context, o.operationDate),
                                  variant: ClinovaPillVariant.muted,
                                ),
                              const SizedBox(width: 8),
                              if (o.doctorName != null &&
                                  o.doctorName!.isNotEmpty)
                                Expanded(
                                  child: Text(
                                    l.doctorLine(o.doctorName!),
                                    style: TextStyle(
                                        color: AppTheme.textMuted,
                                        fontWeight: FontWeight.w700),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                            ],
                          ),
                          if (o.notes != null &&
                              o.notes!.trim().isNotEmpty) ...[
                            const SizedBox(height: 12),
                            Text(o.notes!, style: const TextStyle(height: 1.4)),
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
    );
  }
}

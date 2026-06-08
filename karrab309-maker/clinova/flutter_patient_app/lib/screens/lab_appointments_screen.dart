import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import '../config/api_config.dart';
import '../models/lab_appointment_model.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../utils/clinova_formatters.dart';
import '../utils/error_message.dart';
import '../widgets/clinova_ui.dart';

class LabAppointmentsScreen extends StatefulWidget {
  const LabAppointmentsScreen({super.key});

  @override
  State<LabAppointmentsScreen> createState() => _LabAppointmentsScreenState();
}

class _LabAppointmentsScreenState extends State<LabAppointmentsScreen> {
  DateTime _cursorMonth = DateTime(DateTime.now().year, DateTime.now().month, 1);
  List<LabAppointmentModel> _items = [];
  bool _loading = true;
  String? _error;

  DateTime? _scheduledAt;
  final _noteCtrl = TextEditingController();
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _noteCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final list = await ApiService.getLabAppointments();
      list.sort((a, b) => b.scheduledAt.compareTo(a.scheduledAt));
      if (!mounted) return;
      setState(() {
        _items = list;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = userFacingError(e);
        _loading = false;
      });
    }
  }

  void _shiftMonth(int delta) {
    setState(() {
      _cursorMonth = DateTime(_cursorMonth.year, _cursorMonth.month + delta, 1);
    });
  }

  String _monthHeading(BuildContext context) {
    final d = _cursorMonth;
    return ClinovaFormatters.formatMonthYear(context, d);
  }

  List<DateTime?> _calendarCells() {
    final first = _cursorMonth;
    final startDow = (first.weekday % 7); // Mon=1..Sun=7 => Sun=0
    final start = first.subtract(Duration(days: startDow));
    return List<DateTime?>.generate(42, (i) => start.add(Duration(days: i)));
  }

  bool _hasLabOnDay(DateTime day) {
    final y = day.year, m = day.month, d = day.day;
    for (final it in _items) {
      if (it.scheduledAt.isEmpty) continue;
      final dt = DateTime.tryParse(it.scheduledAt);
      if (dt == null) continue;
      if (dt.year == y && dt.month == m && dt.day == d) return true;
    }
    return false;
  }

  Future<void> _pickDateTime() async {
    final now = DateTime.now();
    final pickedDate = await showDatePicker(
      context: context,
      firstDate: DateTime(now.year - 1),
      lastDate: DateTime(now.year + 2),
      initialDate: _scheduledAt ?? now,
    );
    if (pickedDate == null) return;
    if (!mounted) return;
    final pickedTime = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(_scheduledAt ?? now),
    );
    if (pickedTime == null) return;
    setState(() {
      _scheduledAt = DateTime(pickedDate.year, pickedDate.month, pickedDate.day, pickedTime.hour, pickedTime.minute);
    });
  }

  Future<void> _submit() async {
    if (_saving || _scheduledAt == null) return;
    setState(() => _saving = true);
    try {
      await ApiService.createLabAppointment(
        scheduledAtIso: _scheduledAt!.toIso8601String(),
        note: _noteCtrl.text.trim().isEmpty ? null : _noteCtrl.text.trim(),
      );
      _noteCtrl.clear();
      _scheduledAt = null;
      await _load();
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  Future<void> _cancel(LabAppointmentModel a) async {
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
            title: l.labTitle,
            subtitle: l.labSubtitle,
            icon: Icons.calendar_month_rounded,
          ),
          const SizedBox(height: 16),

          // Calendrier (équivalent web: PATIENT_PORTAL.LAB_CALENDAR_TITLE + navigation mois)
          ClinovaModernCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(l.labCalendarTitle, style: const TextStyle(fontWeight: FontWeight.w900)),
                    ),
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
                  _monthHeading(context),
                  textAlign: TextAlign.center,
                  style: TextStyle(color: AppTheme.textMuted, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 10),
                GridView.count(
                  crossAxisCount: 7,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  children: [
                    for (final w in ClinovaFormatters.weekdayShortHeaders(context))
                      Center(child: Text(w, style: TextStyle(color: AppTheme.textMuted, fontWeight: FontWeight.w800, fontSize: 11))),
                  ],
                ),
                const SizedBox(height: 6),
                GridView.builder(
                  itemCount: cells.length,
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 7, mainAxisSpacing: 6, crossAxisSpacing: 6),
                  itemBuilder: (_, i) {
                    final d = cells[i]!;
                    final muted = d.month != _cursorMonth.month;
                    final isToday = d.year == today.year && d.month == today.month && d.day == today.day;
                    final hasLab = _hasLabOnDay(d);
                    return Container(
                      decoration: BoxDecoration(
                        color: isToday ? AppTheme.primary.withValues(alpha: 0.14) : Colors.black.withValues(alpha: 0.03),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.white.withValues(alpha: 0.10)),
                      ),
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          Text(
                            '${d.day}',
                            style: TextStyle(
                              color: muted ? AppTheme.textMuted.withValues(alpha: 0.55) : null,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          if (hasLab)
                            Positioned(
                              bottom: 6,
                              child: Container(
                                width: 6,
                                height: 6,
                                decoration: BoxDecoration(color: AppTheme.primary, borderRadius: BorderRadius.circular(99)),
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
                    Container(width: 6, height: 6, decoration: BoxDecoration(color: AppTheme.primary, borderRadius: BorderRadius.circular(99))),
                    const SizedBox(width: 8),
                    Expanded(child: Text(l.labCalendarLegend, style: TextStyle(color: AppTheme.textMuted, fontWeight: FontWeight.w700, fontSize: 12))),
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 12),

          // Demande RDV labo (équivalent web: datetime + note + submit)
          ClinovaModernCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(l.labWhenLabel, style: TextStyle(color: AppTheme.textMuted, fontWeight: FontWeight.w800, fontSize: 12)),
                const SizedBox(height: 8),
                OutlinedButton(
                  onPressed: _saving ? null : _pickDateTime,
                  child: Text(
                    _scheduledAt == null ? l.labWhenPick : ClinovaFormatters.formatDateTime(context, _scheduledAt!),
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                ),
                const SizedBox(height: 10),
                Text(l.labNoteLabel, style: TextStyle(color: AppTheme.textMuted, fontWeight: FontWeight.w800, fontSize: 12)),
                const SizedBox(height: 8),
                TextField(
                  controller: _noteCtrl,
                  enabled: !_saving,
                  decoration: InputDecoration(
                    hintText: l.labNotePlaceholder,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                    isDense: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                ),
                const SizedBox(height: 12),
                FilledButton(
                  onPressed: (_saving || _scheduledAt == null) ? null : _submit,
                  child: _saving
                      ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2))
                      : Text(l.labSubmit),
                ),
                const SizedBox(height: 6),
                Text(
                  '${l.labPublicHint} ${ApiConfig.publicAppOrigin}',
                  style: TextStyle(color: AppTheme.textMuted, fontSize: 11, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),

          const SizedBox(height: 12),

          // Liste
          ClinovaModernCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(l.labListTitle, style: const TextStyle(fontWeight: FontWeight.w900)),
                const SizedBox(height: 10),
                if (_loading)
                  const Center(child: Padding(padding: EdgeInsets.all(12), child: CircularProgressIndicator()))
                else if (_error != null)
                  Text(_error!, textAlign: TextAlign.center, style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w800))
                else if (_items.isEmpty)
                  ClinovaEmptyState(title: l.commonNoData, text: l.labSubtitle, icon: Icons.biotech_rounded)
                else
                  ..._items.map(
                    (a) {
                      final dt = DateTime.tryParse(a.scheduledAt);
                      final when = dt == null ? a.scheduledAt : ClinovaFormatters.formatDateTime(context, dt);
                      final pending = a.status.toLowerCase() == 'pending';
                      return Column(
                        children: [
                          const Divider(height: 16),
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  '$when — ${a.status.toUpperCase()}',
                                  style: const TextStyle(fontWeight: FontWeight.w700),
                                ),
                              ),
                              if (pending)
                                TextButton(
                                  onPressed: () => _cancel(a),
                                  child: Text(l.commonCancel, style: const TextStyle(color: Colors.red)),
                                ),
                            ],
                          ),
                          if (a.note != null && a.note!.trim().isNotEmpty)
                            Align(
                              alignment: Alignment.centerLeft,
                              child: Padding(
                                padding: const EdgeInsets.only(top: 6),
                                child: Text(a.note!, style: TextStyle(color: AppTheme.textMuted, height: 1.35)),
                              ),
                            ),
                        ],
                      );
                    },
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}


import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';

class AssistantClinovaFab extends StatefulWidget {
  const AssistantClinovaFab({super.key});

  @override
  State<AssistantClinovaFab> createState() => _AssistantClinovaFabState();
}

class _AssistantClinovaFabState extends State<AssistantClinovaFab> {
  bool _open = false;
  bool _loading = false;
  final _ctrl = TextEditingController();
  final List<({bool fromUser, String text})> _msgs = [];

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_msgs.isEmpty) {
      final l = AppLocalizations.of(context)!;
      _msgs.add((fromUser: false, text: l.assistantWelcome));
    }
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final t = _ctrl.text.trim();
    if (t.isEmpty || _loading) return;
    setState(() {
      _ctrl.clear();
      _msgs.add((fromUser: true, text: t));
      _loading = true;
    });
    try {
      final reply = await ApiService.chatMessage(t);
      if (!mounted) return;
      setState(() {
        _msgs.add((fromUser: false, text: reply.isEmpty ? '…' : reply));
      });
    } catch (_) {
      if (!mounted) return;
      final l = AppLocalizations.of(context)!;
      setState(() {
        _msgs.add((fromUser: false, text: l.assistantNetworkError));
      });
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;

    if (!_open) {
      return FloatingActionButton(
        heroTag: 'assistantClinovaFab',
        onPressed: () => setState(() => _open = true),
        tooltip: l.assistantTooltip,
        backgroundColor: AppTheme.primary,
        child: const Icon(Icons.chat_bubble_rounded),
      );
    }

    return Material(
      color: Colors.transparent,
      child: Container(
        width: 340,
        constraints: const BoxConstraints(maxWidth: 360),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor.withValues(alpha: 0.98),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.18),
              blurRadius: 24,
              offset: const Offset(0, 12),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 10, 10, 8),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      l.assistantTitle,
                      style: const TextStyle(fontWeight: FontWeight.w800),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  IconButton(
                    onPressed: _loading ? null : () => setState(() => _open = false),
                    icon: const Icon(Icons.close_rounded),
                    tooltip: MaterialLocalizations.of(context).closeButtonTooltip,
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            ConstrainedBox(
              constraints: const BoxConstraints(maxHeight: 260),
              child: ListView.builder(
                padding: const EdgeInsets.all(12),
                itemCount: _msgs.length + (_loading ? 1 : 0),
                itemBuilder: (ctx, i) {
                  if (_loading && i == _msgs.length) {
                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      child: Text('…', style: TextStyle(color: AppTheme.textMuted, fontWeight: FontWeight.w700)),
                    );
                  }
                  final m = _msgs[i];
                  final align = m.fromUser ? CrossAxisAlignment.end : CrossAxisAlignment.start;
                  final bg = m.fromUser ? AppTheme.primary : Colors.black.withValues(alpha: 0.05);
                  final fg = m.fromUser ? Colors.white : Theme.of(context).colorScheme.onSurface;
                  return Column(
                    crossAxisAlignment: align,
                    children: [
                      Container(
                        margin: const EdgeInsets.symmetric(vertical: 4),
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                        decoration: BoxDecoration(
                          color: bg,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(m.text, style: TextStyle(color: fg, height: 1.35)),
                      ),
                    ],
                  );
                },
              ),
            ),
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.fromLTRB(10, 8, 10, 10),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _ctrl,
                      enabled: !_loading,
                      textInputAction: TextInputAction.send,
                      onSubmitted: (_) => _send(),
                      decoration: InputDecoration(
                        hintText: l.assistantPlaceholder,
                        isDense: true,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  FilledButton(
                    onPressed: _loading ? null : _send,
                    child: Text(l.assistantSend),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}


import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../models/message_model.dart';
import '../theme/app_theme.dart';
import '../widgets/clinova_ui.dart';
import '../config/app_assets.dart';

class MessagesTab extends StatefulWidget {
  final int patientId;

  const MessagesTab({super.key, required this.patientId});

  @override
  State<MessagesTab> createState() => _MessagesTabState();
}

class _MessagesTabState extends State<MessagesTab> {
  List<MessageModel> _messages = [];
  bool _loading = true;
  String? _error;
  int? _currentUserId;
  final _textController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadUser();
    _load();
  }

  @override
  void dispose() {
    _textController.dispose();
    super.dispose();
  }

  Future<void> _loadUser() async {
    try {
      final u = await ApiService.me();
      if (mounted) setState(() => _currentUserId = u.id);
    } catch (_) {}
  }

  int? _doctorId;

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      // Charger d'abord tout pour déduire le médecin, puis conversation triée (sync API)
      List<MessageModel> list = await ApiService.getMessages();
      int? doctorId;
      for (final m in list) {
        final other = m.senderId == _currentUserId ? m.receiverId : m.senderId;
        if (other != _currentUserId) {
          doctorId = other;
          break;
        }
      }
      if (doctorId == null) {
        final patient = await ApiService.getPatient(widget.patientId);
        doctorId = patient.primaryDoctorUserId;
      }
      if (doctorId != null) {
        list = await ApiService.getMessages(withUserId: doctorId);
        _doctorId = doctorId;
      }
      if (mounted) {
        setState(() {
          _messages = list..sort((a, b) => (a.createdAt ?? '').compareTo(b.createdAt ?? ''));
          _loading = false;
        });
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

  Future<void> _send() async {
    final text = _textController.text.trim();
    final receiverId = _doctorId;
    if (text.isEmpty && _pendingAttachment == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Saisissez un message ou joignez un fichier.')));
      return;
    }
    if (receiverId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Aucun médecin rattaché à votre dossier (opération). Contactez l’accueil.'),
        ),
      );
      return;
    }
    _textController.clear();
    final file = _pendingAttachment;
    _pendingAttachment = null;
    if (mounted) setState(() {});
    try {
      await ApiService.sendMessage(receiverId, text, attachment: file);
      _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  File? _pendingAttachment;

  Future<void> _pickAttachment() async {
    final picker = ImagePicker();
    final XFile? x = await picker.pickImage(source: ImageSource.gallery, maxWidth: 1920, imageQuality: 85);
    if (x != null) {
      setState(() => _pendingAttachment = File(x.path));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;
    return Column(
      children: [
        Expanded(
          child: RefreshIndicator(
            color: AppTheme.primary,
            onRefresh: _load,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
              children: [
                ClinovaPageHeader(
                  title: l.messages,
                  subtitle: l.moreMessagesDesc,
                  icon: Icons.chat_bubble_rounded,
                  trailing: ClinovaHeaderThumb(
                    assetPath: AppAssets.bannerSecondary,
                    semanticLabel: l.messages,
                  ),
                ),
                const SizedBox(height: 18),
                if (_loading)
                  const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()))
                else if (_error != null)
                  ClinovaModernCard(
                    child: Column(
                      children: [
                        Text(_error!, textAlign: TextAlign.center, style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w700)),
                        const SizedBox(height: 16),
                        FilledButton(onPressed: _load, child: Text(l.actionRetry)),
                      ],
                    ),
                  )
                else if (_messages.isEmpty)
                  ClinovaModernCard(
                    child: ClinovaEmptyState(
                      title: 'Aucun message',
                      text: _doctorId != null
                          ? 'Aucun message pour l’instant. Vous pouvez écrire à votre médecin ci-dessous.'
                          : 'Aucun message. Dès qu’une opération est enregistrée avec un médecin, vous pourrez échanger ici.',
                      icon: Icons.chat_bubble_outline_rounded,
                      illustrationAsset: AppAssets.bannerWellness,
                      showIconBadge: false,
                    ),
                  )
                else
                  ..._messages.map((m) {
                    final isMe = m.senderId == _currentUserId;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: Align(
                        alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
                        child: Container(
                          constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.82),
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          decoration: BoxDecoration(
                            gradient: isMe ? AppTheme.heroGradient : null,
                            color: isMe ? null : AppTheme.surface.withValues(alpha: 0.92),
                            border: isMe ? null : Border.all(color: AppTheme.border),
                            borderRadius: BorderRadius.circular(20),
                            boxShadow: isMe ? AppTheme.cardShadow : AppTheme.cardShadow,
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (m.attachmentUrl != null)
                                Padding(
                                  padding: const EdgeInsets.only(bottom: 6),
                                  child: InkWell(
                                    onTap: () async {
                                      try {
                                        await launchUrl(Uri.parse(m.attachmentUrl!), mode: LaunchMode.externalApplication);
                                      } catch (_) {}
                                    },
                                    child: Text(
                                      '📎 Fichier joint',
                                      style: TextStyle(
                                        fontSize: 13,
                                        color: isMe ? Colors.white70 : AppTheme.primary,
                                        decoration: TextDecoration.underline,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                                ),
                              Text(
                                m.content,
                                style: TextStyle(color: isMe ? Colors.white : AppTheme.text, fontSize: 15, fontWeight: FontWeight.w600, height: 1.35),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                m.createdAt ?? '',
                                style: TextStyle(fontSize: 11, color: isMe ? Colors.white70 : AppTheme.textMuted, fontWeight: FontWeight.w700),
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  }),
              ],
            ),
          ),
        ),
        Container(
          padding: const EdgeInsets.fromLTRB(10, 12, 10, 10),
          decoration: BoxDecoration(
            color: AppTheme.surface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
            boxShadow: AppTheme.cardShadow,
            border: Border(top: BorderSide(color: AppTheme.border.withValues(alpha: 0.65))),
          ),
          child: SafeArea(
            child: Row(
              children: [
                IconButton(
                  onPressed: _pickAttachment,
                  icon: const Icon(Icons.attach_file),
                  color: AppTheme.primary,
                ),
                Expanded(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      if (_pendingAttachment != null)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 4),
                          child: Row(
                            children: [
                              Text('Fichier: ${_pendingAttachment!.path.split(RegExp(r'[/\\]')).last}', style: const TextStyle(fontSize: 12, color: AppTheme.primary)),
                              IconButton(
                                icon: const Icon(Icons.close, size: 18),
                                onPressed: () => setState(() => _pendingAttachment = null),
                                padding: EdgeInsets.zero,
                                constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                              ),
                            ],
                          ),
                        ),
                      TextField(
                        controller: _textController,
                        decoration: InputDecoration(
                          hintText: 'Votre message…',
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(20)),
                          filled: true,
                          fillColor: AppTheme.background,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        ),
                        maxLines: 1,
                        onSubmitted: (_) => _send(),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filled(
                  onPressed: _send,
                  icon: const Icon(Icons.send),
                  style: IconButton.styleFrom(backgroundColor: AppTheme.primary),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

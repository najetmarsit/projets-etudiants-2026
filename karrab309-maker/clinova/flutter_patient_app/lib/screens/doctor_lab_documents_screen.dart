import 'package:flutter/material.dart';
import '../models/lab_document_model.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../widgets/clinova_ui.dart';

class DoctorLabDocumentsScreen extends StatefulWidget {
  final int patientId;

  const DoctorLabDocumentsScreen({super.key, required this.patientId});

  @override
  State<DoctorLabDocumentsScreen> createState() => _DoctorLabDocumentsScreenState();
}

class _DoctorLabDocumentsScreenState extends State<DoctorLabDocumentsScreen> {
  bool _loading = true;
  String? _error;
  List<LabDocumentModel> _items = const [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final list = await ApiService.getLabDocumentsForPatient(widget.patientId);
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

  Future<void> _open(LabDocumentModel d) async {
    try {
      await ApiService.openLabDocumentPdf(d.id, d.originalFilename);
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Ouverture impossible')));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(gradient: AppTheme.pageBackgroundGradient),
        child: SafeArea(
          bottom: false,
          child: RefreshIndicator(
            color: AppTheme.primary,
            onRefresh: _load,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
              children: [
                const ClinovaPageHeader(
                  title: 'Analyses (PDF)',
                  subtitle: 'Documents laboratoire associés au patient.',
                  icon: Icons.picture_as_pdf_rounded,
                ),
                const SizedBox(height: 16),
                if (_loading)
                  const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()))
                else if (_error != null)
                  ClinovaModernCard(
                    child: Column(
                      children: [
                        Text(_error!, textAlign: TextAlign.center, style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w700)),
                        const SizedBox(height: 16),
                        FilledButton(onPressed: _load, child: const Text('Réessayer')),
                      ],
                    ),
                  )
                else if (_items.isEmpty)
                  const ClinovaModernCard(
                    child: ClinovaEmptyState(
                      title: 'Aucun document',
                      text: 'Aucun document laboratoire pour le moment.',
                      icon: Icons.folder_off_outlined,
                    ),
                  )
                else
                  ..._items.map(
                    (d) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: ClinovaModernCard(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 6),
                        child: ListTile(
                          leading: Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: Colors.red.shade50,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Icon(Icons.picture_as_pdf_rounded, color: Colors.red.shade700),
                          ),
                          title: Text(d.title, style: const TextStyle(fontWeight: FontWeight.w900)),
                          subtitle: Text(d.originalFilename, style: const TextStyle(color: AppTheme.textMuted)),
                          trailing: IconButton(
                            icon: const Icon(Icons.open_in_new_rounded),
                            onPressed: () => _open(d),
                          ),
                        ),
                      ),
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


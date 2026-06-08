import 'package:flutter/material.dart';
import '../models/user_model.dart';
import '../models/patient_model.dart';
import '../models/health_indicator_model.dart';
import '../models/nursing_note_model.dart';
import '../models/payment_models.dart';
import '../services/api_service.dart';
import '../theme/app_theme.dart';
import '../utils/clinova_formatters.dart';
import '../widgets/clinova_ui.dart';
import '../config/app_assets.dart';
import '../utils/error_message.dart';
import 'doctor_lab_documents_screen.dart';
import 'messages_tab.dart';

class PatientDetailScreen extends StatefulWidget {
  final int patientId;

  const PatientDetailScreen({super.key, required this.patientId});

  @override
  State<PatientDetailScreen> createState() => _PatientDetailScreenState();
}

class _PatientDetailScreenState extends State<PatientDetailScreen> {
  PatientModel? _patient;
  bool _loading = true;
  String? _error;

  // Champs médecin (édition)
  bool _isDoctor = false;
  bool _isNurse = false;
  bool _saving = false;
  String? _saveMsg;
  final _diagCtrl = TextEditingController();
  final _treatCtrl = TextEditingController();
  final _obsCtrl = TextEditingController();
  final _historyCtrl = TextEditingController();
  final _illnessCtrl = TextEditingController();

  // Infirmier : constantes + notes + urgence
  List<NursingNoteModel> _nursingNotes = [];
  bool _savingNursingNote = false;
  final _nursingNoteCtrl = TextEditingController();
  final _urgentCtrl = TextEditingController();

  // Infirmier : saisie constantes vitales
  bool _savingVitals = false;
  List<HealthIndicator> _vitals = [];
  final _heartCtrl = TextEditingController();
  final _tempCtrl = TextEditingController();
  final _glyCtrl = TextEditingController();
  final _bpSysCtrl = TextEditingController();
  final _bpDiaCtrl = TextEditingController();

  // Bilan / actes facturables (staff)
  PaymentBalanceModel? _balance;
  bool _loadingBalance = false;
  String? _balanceError;
  String _billKind = 'medication';
  final _billLabelCtrl = TextEditingController();
  bool _addingBill = false;

  @override
  void initState() {
    super.initState();
    _loadPatient();
  }

  @override
  void dispose() {
    _diagCtrl.dispose();
    _treatCtrl.dispose();
    _obsCtrl.dispose();
    _historyCtrl.dispose();
    _illnessCtrl.dispose();
    _nursingNoteCtrl.dispose();
    _urgentCtrl.dispose();
    _heartCtrl.dispose();
    _tempCtrl.dispose();
    _glyCtrl.dispose();
    _bpSysCtrl.dispose();
    _bpDiaCtrl.dispose();
    _billLabelCtrl.dispose();
    super.dispose();
  }

  int? _parseInt(String s) => int.tryParse(s.trim());
  double? _parseDouble(String s) => double.tryParse(s.trim().replaceAll(',', '.'));

  Future<void> _saveVitals() async {
    if (!_isNurse || _savingVitals) return;

    final hr = _parseInt(_heartCtrl.text);
    final temp = _parseDouble(_tempCtrl.text);
    final gly = _parseDouble(_glyCtrl.text);
    final sys = _parseInt(_bpSysCtrl.text);
    final dia = _parseInt(_bpDiaCtrl.text);

    String? err;
    if (hr == null || hr < 30 || hr > 220) {
      err = 'Fréquence cardiaque invalide (30–220 bpm).';
    } else if (temp == null || temp < 30 || temp > 45) {
      err = 'Température invalide (30–45 °C).';
    } else if (gly == null || gly < 1 || gly > 35) {
      err = 'Glycémie invalide (1–35 mmol/L).';
    } else if (sys == null || sys < 60 || sys > 250 || dia == null || dia < 30 || dia > 150) {
      err = 'Tension invalide (systolique 60–250, diastolique 30–150).';
    } else if (sys <= dia) {
      err = 'La systolique doit être supérieure à la diastolique.';
    }
    if (err != null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(err)));
      return;
    }

    setState(() {
      _savingVitals = true;
      _error = null;
    });
    try {
      await ApiService.createHealthIndicator(
        patientId: widget.patientId,
        heartRate: hr!,
        temperature: temp!,
        bloodGlucose: gly!,
        bloodPressureSystolic: sys!,
        bloodPressureDiastolic: dia!,
      );
      _heartCtrl.clear();
      _tempCtrl.clear();
      _glyCtrl.clear();
      _bpSysCtrl.clear();
      _bpDiaCtrl.clear();
      _vitals = await ApiService.getHealthIndicators(widget.patientId);
      await _loadPatient(forceRefresh: true);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Constantes enregistrées.')));
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.message;
      });
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (e) {
      if (!mounted) return;
      final msg = userFacingError(e);
      setState(() {
        _error = msg;
      });
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
    } finally {
      if (mounted) setState(() => _savingVitals = false);
    }
  }

  Future<void> _loadPatient({bool forceRefresh = false}) async {
    setState(() {
      _loading = true;
      _error = null;
      _saveMsg = null;
    });
    try {
      final wave1 = await Future.wait<dynamic>([
        ApiService.me(forceRefresh: forceRefresh),
        ApiService.getPatient(widget.patientId, forceRefresh: forceRefresh),
      ]);
      final me = wave1[0] as UserModel;
      final p = wave1[1] as PatientModel;
      _isDoctor = me.role == 'Doctor';
      _isNurse = me.role == 'Nurse';

      PaymentBalanceModel? balance;
      String? balanceErr;
      if (_isDoctor || _isNurse) {
        try {
          balance = await ApiService.getPaymentBalanceForPatient(widget.patientId);
          balanceErr = null;
        } catch (e) {
          balance = null;
          balanceErr = e.toString();
        }
      } else {
        balance = null;
        balanceErr = null;
      }

      List<NursingNoteModel> notes = [];
      List<HealthIndicator> vitals = [];
      if (_isNurse) {
        final duo = await Future.wait<dynamic>([
          ApiService.getNursingNotes(widget.patientId),
          ApiService.getHealthIndicators(widget.patientId, forceRefresh: forceRefresh),
        ]);
        notes = duo[0] as List<NursingNoteModel>;
        vitals = duo[1] as List<HealthIndicator>;
      }

      if (!mounted) return;
      setState(() {
        _patient = p;
        _balance = balance;
        _balanceError = balanceErr;
        _nursingNotes = notes;
        _vitals = vitals;
        _diagCtrl.text = p.diagnosis ?? '';
        _treatCtrl.text = p.prescribedTreatment ?? '';
        _obsCtrl.text = p.doctorObservations ?? '';
        _historyCtrl.text = p.medicalHistory ?? '';
        _illnessCtrl.text = p.currentIllness ?? '';
        _loading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.message;
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

  Future<void> _refreshBalance() async {
    if (!(_isDoctor || _isNurse)) return;
    setState(() {
      _loadingBalance = true;
      _balanceError = null;
    });
    try {
      final b = await ApiService.getPaymentBalanceForPatient(widget.patientId);
      if (!mounted) return;
      setState(() {
        _balance = b;
        _loadingBalance = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _balance = null;
        _balanceError = e.toString();
        _loadingBalance = false;
      });
    }
  }

  Future<void> _addBillItem() async {
    if (_addingBill) return;
    final label = _billLabelCtrl.text.trim();
    if (label.isEmpty) return;
    setState(() {
      _addingBill = true;
      _balanceError = null;
    });
    try {
      await ApiService.addPatientBillingItem(patientId: widget.patientId, kind: _billKind, label: label);
      _billLabelCtrl.clear();
      await _refreshBalance();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _balanceError = e.toString();
      });
    } finally {
      if (mounted) setState(() => _addingBill = false);
    }
  }

  Future<void> _saveDoctorFields() async {
    if (_saving) return;
    setState(() {
      _saving = true;
      _saveMsg = null;
      _error = null;
    });
    try {
      final updated = await ApiService.updatePatientDoctorFields(
        widget.patientId,
        medicalHistory: _historyCtrl.text.trim(),
        diagnosis: _diagCtrl.text.trim(),
        currentIllness: _illnessCtrl.text.trim(),
        prescribedTreatment: _treatCtrl.text.trim(),
        doctorObservations: _obsCtrl.text.trim(),
      );
      if (!mounted) return;
      setState(() {
        _patient = updated;
        _saving = false;
        _saveMsg = 'Enregistré.';
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _saving = false;
        _error = e.message;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _saving = false;
        _error = userFacingError(e);
      });
    }
  }

  Future<void> _addNursingNote() async {
    if (!_isNurse || _savingNursingNote) return;
    final note = _nursingNoteCtrl.text.trim();
    if (note.isEmpty) return;
    setState(() {
      _savingNursingNote = true;
      _error = null;
    });
    try {
      await ApiService.createNursingNote(widget.patientId, note);
      _nursingNoteCtrl.clear();
      _nursingNotes = await ApiService.getNursingNotes(widget.patientId);
      if (!mounted) return;
      setState(() => _savingNursingNote = false);
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Observation enregistrée')));
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _savingNursingNote = false;
        _error = e.message;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _savingNursingNote = false;
        _error = userFacingError(e);
      });
    }
  }

  Future<void> _signalUrgent() async {
    if (!_isNurse) return;
    final msg = _urgentCtrl.text.trim();
    if (msg.isEmpty) return;
    try {
      await ApiService.signalUrgent(widget.patientId, message: msg);
      _urgentCtrl.clear();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Signalement urgent envoyé')));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(userFacingError(e))));
    }
  }

  Widget _miniStat(String label, String value) {
    return SizedBox(
      width: 110,
      child: Column(
        children: [
          Text(label, style: const TextStyle(fontSize: 11, color: AppTheme.textMuted, fontWeight: FontWeight.w700)),
          const SizedBox(height: 4),
          Text(value, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900)),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final p = _patient;
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(gradient: AppTheme.pageBackgroundGradient),
        child: SafeArea(
          bottom: false,
          child: RefreshIndicator(
            color: AppTheme.primary,
            onRefresh: () => _loadPatient(forceRefresh: true),
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
              children: [
                ClinovaPageHeader(
                  title: p?.displayName ?? 'Détail patient',
                  subtitle: 'Fiche patient (équipe soignante).',
                  icon: Icons.person_rounded,
                  leading: ((_isDoctor || _isNurse) && Navigator.of(context).canPop())
                      ? IconButton(
                          tooltip: MaterialLocalizations.of(context).backButtonTooltip,
                          onPressed: () => Navigator.of(context).pop(),
                          icon: Icon(Icons.arrow_back_rounded, color: AppTheme.textMuted),
                          style: IconButton.styleFrom(padding: const EdgeInsets.all(8)),
                        )
                      : null,
                  trailing: (_isDoctor || _isNurse)
                      ? const ClinovaHeaderThumb(
                          assetPath: AppAssets.bannerPatient,
                          semanticLabel: 'Fiche patient',
                        )
                      : null,
                ),
                const SizedBox(height: 16),
                if (_loading)
                  const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()))
                else if (_error != null || p == null)
                  ClinovaModernCard(
                    child: Column(
                      children: [
                        Text(
                          _error ?? 'Patient non trouvé',
                          textAlign: TextAlign.center,
                          style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 16),
                        FilledButton(onPressed: () => _loadPatient(forceRefresh: true), child: const Text('Réessayer')),
                      ],
                    ),
                  )
                else ...[
                  ClinovaModernCard(
                    padding: (_isDoctor || _isNurse) ? EdgeInsets.zero : const EdgeInsets.all(18),
                    child: (_isDoctor || _isNurse)
                        ? Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              ClinovaCardTopMedia(
                                assetPath: AppAssets.bannerSecondary,
                                height: 84,
                              ),
                              Padding(
                                padding: const EdgeInsets.all(18),
                                child: Row(
                                  children: [
                                    CircleAvatar(
                                      radius: 34,
                                      backgroundColor: AppTheme.primaryLight,
                                      backgroundImage: p.user?.profilePhotoUrl != null
                                          ? NetworkImage(p.user!.profilePhotoUrl!)
                                          : null,
                                      child: p.user?.profilePhotoUrl == null
                                          ? Icon(Icons.person_rounded, size: 36, color: AppTheme.primary)
                                          : null,
                                    ),
                                    const SizedBox(width: 16),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            p.displayName,
                                            style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
                                          ),
                                          const SizedBox(height: 8),
                                          Wrap(
                                            spacing: 8,
                                            runSpacing: 8,
                                            children: [
                                              ClinovaPill(text: '${p.age} ans', variant: ClinovaPillVariant.muted),
                                              ClinovaPill(text: p.gender, variant: ClinovaPillVariant.muted),
                                              if (p.user?.email != null) ClinovaPill(text: p.user!.email, variant: ClinovaPillVariant.muted),
                                            ],
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          )
                        : Row(
                            children: [
                              CircleAvatar(
                                radius: 34,
                                backgroundColor: AppTheme.primaryLight,
                                backgroundImage: p.user?.profilePhotoUrl != null
                                    ? NetworkImage(p.user!.profilePhotoUrl!)
                                    : null,
                                child: p.user?.profilePhotoUrl == null
                                    ? Icon(Icons.person_rounded, size: 36, color: AppTheme.primary)
                                    : null,
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      p.displayName,
                                      style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
                                    ),
                                    const SizedBox(height: 8),
                                    Wrap(
                                      spacing: 8,
                                      runSpacing: 8,
                                      children: [
                                        ClinovaPill(text: '${p.age} ans', variant: ClinovaPillVariant.muted),
                                        ClinovaPill(text: p.gender, variant: ClinovaPillVariant.muted),
                                        if (p.user?.email != null) ClinovaPill(text: p.user!.email, variant: ClinovaPillVariant.muted),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                  ),
                  // Évite duplication : l’infirmier voit le bilan dans le bloc réordonné plus bas.
                  if (_isDoctor) ...[
                    const SizedBox(height: 14),
                    ClinovaModernCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Row(
                            children: [
                              const Expanded(
                                child: Text('Bilan (actes)', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
                              ),
                              IconButton(
                                tooltip: 'Actualiser',
                                onPressed: _loadingBalance ? null : _refreshBalance,
                                icon: const Icon(Icons.refresh_rounded),
                              ),
                            ],
                          ),
                          if (_loadingBalance)
                            const Padding(
                              padding: EdgeInsets.only(top: 6),
                              child: LinearProgressIndicator(minHeight: 3),
                            ),
                          const SizedBox(height: 10),
                          if (_balance != null) ...[
                            Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: [
                                ClinovaPill(text: 'DÛ ${_balance!.totalDue.toStringAsFixed(2)} ${_balance!.currency}', variant: ClinovaPillVariant.muted),
                                ClinovaPill(text: 'PAYÉ ${_balance!.totalPaid.toStringAsFixed(2)}', variant: ClinovaPillVariant.muted),
                                ClinovaPill(text: 'RESTE ${_balance!.remaining.toStringAsFixed(2)}', variant: _balance!.remaining > 0 ? ClinovaPillVariant.danger : ClinovaPillVariant.newItem),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Row(
                              children: [
                                Expanded(
                                  child: DropdownButtonFormField<String>(
                                    value: _billKind,
                                    decoration: const InputDecoration(labelText: 'Type'),
                                    items: const [
                                      DropdownMenuItem(value: 'medication', child: Text('Médicaments')),
                                      DropdownMenuItem(value: 'analysis', child: Text('Analyses')),
                                      DropdownMenuItem(value: 'meal', child: Text('Repas')),
                                      DropdownMenuItem(value: 'visit', child: Text('Visite')),
                                    ],
                                    onChanged: _addingBill ? null : (v) => setState(() => _billKind = v ?? 'medication'),
                                  ),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  flex: 2,
                                  child: TextField(
                                    controller: _billLabelCtrl,
                                    decoration: const InputDecoration(labelText: 'Libellé', hintText: 'Ex: Antibiotique...'),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            FilledButton.icon(
                              onPressed: _addingBill ? null : _addBillItem,
                              icon: _addingBill
                                  ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : const Icon(Icons.add_rounded),
                              label: const Text('Ajouter'),
                            ),
                            if (_balance!.billingBreakdown.isEmpty)
                              const Padding(
                                padding: EdgeInsets.only(top: 12),
                                child: Text('Aucun acte enregistré.', style: TextStyle(color: AppTheme.textMuted)),
                              )
                            else ...[
                              const SizedBox(height: 12),
                              ..._balance!.billingBreakdown.take(20).map(
                                (l) => Padding(
                                  padding: const EdgeInsets.only(bottom: 8),
                                  child: ClinovaModernCard(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                    child: Row(
                                      children: [
                                        Expanded(child: Text(l.label, style: const TextStyle(fontWeight: FontWeight.w700))),
                                        const SizedBox(width: 10),
                                        Text('${l.amount.toStringAsFixed(2)} ${_balance!.currency}', style: const TextStyle(fontWeight: FontWeight.w900)),
                                      ],
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ] else if (_balanceError != null) ...[
                            Text(_balanceError!, style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w700)),
                          ] else
                            const Text('Bilan indisponible.', style: TextStyle(color: AppTheme.textMuted)),
                        ],
                      ),
                    ),
                  ],
              if (_isDoctor) ...[
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: () => Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => DoctorLabDocumentsScreen(patientId: widget.patientId)),
                        ),
                        icon: const Icon(Icons.picture_as_pdf_rounded),
                        label: const Text('Analyses (PDF)'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: () => Navigator.of(context).push(
                          MaterialPageRoute(
                            builder: (_) => Scaffold(
                              appBar: AppBar(title: const Text('Messagerie')),
                              body: MessagesTab(patientId: widget.patientId),
                            ),
                          ),
                        ),
                        icon: const Icon(Icons.chat_bubble_rounded),
                        label: const Text('Messages'),
                      ),
                    ),
                  ],
                ),
              ],
              if (_isDoctor) ...[
                const SizedBox(height: 18),
                Text('Dossier médical (édition médecin)', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                const SizedBox(height: 8),
                if (_saveMsg != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: ClinovaModernCard(
                      padding: const EdgeInsets.all(12),
                      child: Row(
                        children: [
                          Icon(Icons.check_circle_rounded, color: Colors.green.shade700),
                          const SizedBox(width: 10),
                          Expanded(child: Text(_saveMsg!, style: TextStyle(color: Colors.green.shade800, fontWeight: FontWeight.w700))),
                        ],
                      ),
                    ),
                  ),
                ClinovaModernCard(
                  child: Column(
                    children: [
                      TextField(
                        controller: _diagCtrl,
                        decoration: const InputDecoration(labelText: 'Diagnostic'),
                        minLines: 1,
                        maxLines: 3,
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _illnessCtrl,
                        decoration: const InputDecoration(labelText: 'Maladie actuelle'),
                        minLines: 1,
                        maxLines: 3,
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _treatCtrl,
                        decoration: const InputDecoration(labelText: 'Traitement prescrit'),
                        minLines: 1,
                        maxLines: 4,
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _obsCtrl,
                        decoration: const InputDecoration(labelText: 'Observations médecin'),
                        minLines: 2,
                        maxLines: 6,
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _historyCtrl,
                        decoration: const InputDecoration(labelText: 'Antécédents'),
                        minLines: 2,
                        maxLines: 6,
                      ),
                      const SizedBox(height: 14),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: _saving ? null : _saveDoctorFields,
                          icon: _saving
                              ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                              : const Icon(Icons.save_rounded),
                          label: const Text('Enregistrer'),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              // Évite duplication : l’infirmier a un ordre dédié plus bas.
              if (!_isNurse) ...[
                if (p.medicalHistory != null && p.medicalHistory!.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  Text('Antécédents', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 8),
                  ClinovaModernCard(child: Text(p.medicalHistory!, style: const TextStyle(height: 1.45))),
                ],
                if (p.operations != null && p.operations!.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  Text('Opérations (${p.operations!.length})', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 8),
                  ...(p.operations!.map<Widget>((o) {
                    final op = o as Map<String, dynamic>;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: ClinovaModernCard(
                        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                        child: ListTile(
                          title: Text(op['operation_type']?.toString() ?? 'Opération', style: const TextStyle(fontWeight: FontWeight.w600)),
                          subtitle: Text(op['notes']?.toString() ?? ''),
                        ),
                      ),
                    );
                  })),
                ],
                if (p.healthIndicators != null && p.healthIndicators!.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  Text('Indicateurs de santé (${p.healthIndicators!.length})', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 8),
                  ...(p.healthIndicators!.map<Widget>((h) {
                    final ind = h as Map<String, dynamic>;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: ClinovaModernCard(
                        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                        child: ListTile(
                          title: Text(ind['indicator_type']?.toString() ?? 'Indicateur'),
                          subtitle: Text('Valeur: ${ind['value']} • ${ind['recorded_at']}'),
                        ),
                      ),
                    );
                  })),
                ],
                if (p.alerts != null && p.alerts!.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  Text('Alertes (${p.alerts!.length})', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 8),
                  ...(p.alerts!.map<Widget>((a) {
                    final alert = a as Map<String, dynamic>;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: ClinovaModernCard(
                        borderColor: AppTheme.accent.withValues(alpha: 0.35),
                        backgroundColor: AppTheme.accentLight.withValues(alpha: 0.45),
                        child: ListTile(
                          leading: Icon(Icons.warning_amber_rounded, color: AppTheme.accent),
                          title: Text(alert['indicator_type']?.toString() ?? 'Alerte', style: const TextStyle(fontWeight: FontWeight.w600)),
                          subtitle: Text(alert['message']?.toString() ?? ''),
                        ),
                      ),
                    );
                  })),
                ],
              ],
              // Ordre spécifique infirmier :
              // Alertes, Constantes vitales, Observations infirmières, Bilan, Signalement urgent,
              // Antécédents, Opérations, Indicateurs de santé.
              if (_isNurse) ...[
                if (p.alerts != null && p.alerts!.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  Text('Alertes (${p.alerts!.length})', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 8),
                  ...(p.alerts!.map<Widget>((a) {
                    final alert = a as Map<String, dynamic>;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: ClinovaModernCard(
                        borderColor: AppTheme.accent.withValues(alpha: 0.35),
                        backgroundColor: AppTheme.accentLight.withValues(alpha: 0.45),
                        child: ListTile(
                          leading: Icon(Icons.warning_amber_rounded, color: AppTheme.accent),
                          title: Text(alert['indicator_type']?.toString() ?? 'Alerte', style: const TextStyle(fontWeight: FontWeight.w600)),
                          subtitle: Text(alert['message']?.toString() ?? ''),
                        ),
                      ),
                    );
                  })),
                ],
                const SizedBox(height: 20),
                Text('Constantes vitales', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                const SizedBox(height: 8),
                ClinovaModernCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      if (_vitals.isNotEmpty) ...[
                        Wrap(
                          alignment: WrapAlignment.spaceAround,
                          spacing: 12,
                          runSpacing: 12,
                          children: [
                            _miniStat('FC', _vitals.first.heartRate != null ? '${_vitals.first.heartRate} bpm' : '—'),
                            _miniStat('Temp', _vitals.first.temperature != null ? '${_vitals.first.temperature} °C' : '—'),
                            _miniStat('Gly', _vitals.first.bloodGlucose != null ? '${_vitals.first.bloodGlucose} mmol/L' : '—'),
                            _miniStat(
                              'TA',
                              (_vitals.first.bloodPressureSystolic != null && _vitals.first.bloodPressureDiastolic != null)
                                  ? '${_vitals.first.bloodPressureSystolic}/${_vitals.first.bloodPressureDiastolic}'
                                  : '—',
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Text(
                          'Dernière mise à jour : ${ClinovaFormatters.formatIsoDateTime(context, _vitals.first.recordedAt)}',
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(color: AppTheme.textMuted),
                        ),
                        const Divider(height: 22),
                      ],
                      const Text(
                        'Fréquence cardiaque  · Température  · Glycémie  · Tension',
                        style: TextStyle(color: AppTheme.textMuted, height: 1.4),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _heartCtrl,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(labelText: 'Fréquence cardiaque (bpm)'),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextField(
                              controller: _tempCtrl,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: const InputDecoration(labelText: 'Température (°C)'),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _glyCtrl,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: const InputDecoration(labelText: 'Glycémie (mmol/L)'),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextField(
                              controller: _bpSysCtrl,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(labelText: 'Tension systolique'),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _bpDiaCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(labelText: 'Tension diastolique'),
                      ),
                      const SizedBox(height: 12),
                      FilledButton.icon(
                        onPressed: _savingVitals ? null : _saveVitals,
                        icon: _savingVitals
                            ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : Icon(Icons.favorite_rounded, color: Colors.white),
                        label: Text(_savingVitals ? 'Enregistrement…' : 'Enregistrer les constantes'),
                      ),
                      if (_vitals.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        Text('Historique récent', style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800)),
                        const SizedBox(height: 8),
                        ..._vitals.take(6).map(
                          (h) => Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: ClinovaModernCard(
                              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                              child: ListTile(
                                leading: Icon(Icons.monitor_heart_outlined, color: AppTheme.primary.withValues(alpha: 0.9)),
                                title: Text(
                                  [
                                    if (h.heartRate != null) 'FC ${h.heartRate} bpm',
                                    if (h.temperature != null) 'Temp ${h.temperature} °C',
                                    if (h.bloodGlucose != null) 'Gly ${h.bloodGlucose}',
                                    if (h.bloodPressureSystolic != null && h.bloodPressureDiastolic != null)
                                      'TA ${h.bloodPressureSystolic}/${h.bloodPressureDiastolic}',
                                  ].join(' · '),
                                  style: const TextStyle(fontSize: 13),
                                ),
                                subtitle: Text(
                                  ClinovaFormatters.formatIsoDateTime(context, h.recordedAt),
                                  style: const TextStyle(fontSize: 12),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                Text('Observations infirmières', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                const SizedBox(height: 8),
                ClinovaModernCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      TextField(
                        controller: _nursingNoteCtrl,
                        minLines: 2,
                        maxLines: 5,
                        decoration: const InputDecoration(
                          labelText: 'Ajouter une observation',
                          hintText: 'Ex: patient stable, douleur en baisse…',
                        ),
                      ),
                      const SizedBox(height: 12),
                      FilledButton.icon(
                        onPressed: _savingNursingNote ? null : _addNursingNote,
                        icon: _savingNursingNote
                            ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                            : const Icon(Icons.add_comment_rounded),
                        label: Text(_savingNursingNote ? 'Enregistrement…' : 'Enregistrer'),
                      ),
                      const Divider(height: 26),
                      if (_nursingNotes.isEmpty)
                        Text('Aucune observation.', style: TextStyle(color: AppTheme.textMuted))
                      else
                        ..._nursingNotes.take(10).map(
                          (n) => Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: ClinovaModernCard(
                              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                              child: ListTile(
                                leading: const Icon(Icons.note_alt_outlined),
                                title: Text(n.note, style: const TextStyle(fontWeight: FontWeight.w600)),
                                subtitle: Text(n.createdAt.isNotEmpty ? n.createdAt : '—'),
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                ClinovaModernCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          const Expanded(
                            child: Text('Bilan (actes)', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
                          ),
                          IconButton(
                            tooltip: 'Actualiser',
                            onPressed: _loadingBalance ? null : _refreshBalance,
                            icon: const Icon(Icons.refresh_rounded),
                          ),
                        ],
                      ),
                      if (_loadingBalance)
                        const Padding(
                          padding: EdgeInsets.only(top: 6),
                          child: LinearProgressIndicator(minHeight: 3),
                        ),
                      const SizedBox(height: 10),
                      if (_balance != null) ...[
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            ClinovaPill(text: 'DÛ ${_balance!.totalDue.toStringAsFixed(2)} ${_balance!.currency}', variant: ClinovaPillVariant.muted),
                            ClinovaPill(text: 'PAYÉ ${_balance!.totalPaid.toStringAsFixed(2)}', variant: ClinovaPillVariant.muted),
                            ClinovaPill(text: 'RESTE ${_balance!.remaining.toStringAsFixed(2)}', variant: _balance!.remaining > 0 ? ClinovaPillVariant.danger : ClinovaPillVariant.newItem),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: DropdownButtonFormField<String>(
                                value: _billKind,
                                decoration: const InputDecoration(labelText: 'Type'),
                                items: const [
                                  DropdownMenuItem(value: 'medication', child: Text('Médicaments')),
                                  DropdownMenuItem(value: 'analysis', child: Text('Analyses')),
                                  DropdownMenuItem(value: 'meal', child: Text('Repas')),
                                  DropdownMenuItem(value: 'visit', child: Text('Visite')),
                                ],
                                onChanged: _addingBill ? null : (v) => setState(() => _billKind = v ?? 'medication'),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              flex: 2,
                              child: TextField(
                                controller: _billLabelCtrl,
                                decoration: const InputDecoration(labelText: 'Libellé', hintText: 'Ex: Antibiotique...'),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        FilledButton.icon(
                          onPressed: _addingBill ? null : _addBillItem,
                          icon: _addingBill
                              ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                              : const Icon(Icons.add_rounded),
                          label: const Text('Ajouter'),
                        ),
                        if (_balance!.billingBreakdown.isEmpty)
                          const Padding(
                            padding: EdgeInsets.only(top: 12),
                            child: Text('Aucun acte enregistré.', style: TextStyle(color: AppTheme.textMuted)),
                          )
                        else ...[
                          const SizedBox(height: 12),
                          ..._balance!.billingBreakdown.take(20).map(
                            (l) => Padding(
                              padding: const EdgeInsets.only(bottom: 8),
                              child: ClinovaModernCard(
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                child: Row(
                                  children: [
                                    Expanded(child: Text(l.label, style: const TextStyle(fontWeight: FontWeight.w700))),
                                    const SizedBox(width: 10),
                                    Text('${l.amount.toStringAsFixed(2)} ${_balance!.currency}', style: const TextStyle(fontWeight: FontWeight.w900)),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        ],
                      ] else if (_balanceError != null) ...[
                        Text(_balanceError!, style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w700)),
                      ] else
                        const Text('Bilan indisponible.', style: TextStyle(color: AppTheme.textMuted)),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                ClinovaModernCard(
                  borderColor: Colors.red.shade200,
                  backgroundColor: Colors.red.shade50,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.warning_rounded, color: Colors.red.shade800),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Signalement urgent',
                              style: TextStyle(fontWeight: FontWeight.w900, color: Colors.red.shade800),
                            ),
                          ),
                          const ClinovaPill(text: 'URGENT', variant: ClinovaPillVariant.danger),
                        ],
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _urgentCtrl,
                        minLines: 2,
                        maxLines: 5,
                        decoration: const InputDecoration(labelText: 'Message urgent'),
                      ),
                      const SizedBox(height: 12),
                      FilledButton.icon(
                        style: FilledButton.styleFrom(backgroundColor: Colors.red.shade700),
                        onPressed: _signalUrgent,
                        icon: const Icon(Icons.send_rounded),
                        label: const Text('Envoyer'),
                      ),
                    ],
                  ),
                ),
                if (p.medicalHistory != null && p.medicalHistory!.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  Text('Antécédents', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 8),
                  ClinovaModernCard(child: Text(p.medicalHistory!, style: const TextStyle(height: 1.45))),
                ],
                if (p.operations != null && p.operations!.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  Text('Opérations (${p.operations!.length})', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 8),
                  ...(p.operations!.map<Widget>((o) {
                    final op = o as Map<String, dynamic>;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: ClinovaModernCard(
                        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                        child: ListTile(
                          title: Text(op['operation_type']?.toString() ?? 'Opération', style: const TextStyle(fontWeight: FontWeight.w600)),
                          subtitle: Text(op['notes']?.toString() ?? ''),
                        ),
                      ),
                    );
                  })),
                ],
                if (p.healthIndicators != null && p.healthIndicators!.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  Text('Indicateurs de santé (${p.healthIndicators!.length})', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 8),
                  ...(p.healthIndicators!.map<Widget>((h) {
                    final ind = h as Map<String, dynamic>;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: ClinovaModernCard(
                        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                        child: ListTile(
                          title: Text(ind['indicator_type']?.toString() ?? 'Indicateur'),
                          subtitle: Text('Valeur: ${ind['value']} • ${ind['recorded_at']}'),
                        ),
                      ),
                    );
                  })),
                ],
              ],
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}

import 'patient_model.dart';

/// Page de liste patients (pagination curseur côté API).
class PatientListResult {
  const PatientListResult({
    required this.items,
    this.nextCursor,
    required this.hasMore,
  });

  final List<PatientModel> items;
  final String? nextCursor;
  final bool hasMore;
}

class NursingNoteModel {
  final int id;
  final int patientId;
  final String note;
  final String createdAt;

  NursingNoteModel({
    required this.id,
    required this.patientId,
    required this.note,
    required this.createdAt,
  });

  factory NursingNoteModel.fromJson(Map<String, dynamic> json) {
    return NursingNoteModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      patientId: (json['patient_id'] as num?)?.toInt() ?? 0,
      note: (json['note'] ?? '').toString(),
      createdAt: (json['created_at'] ?? '').toString(),
    );
  }
}


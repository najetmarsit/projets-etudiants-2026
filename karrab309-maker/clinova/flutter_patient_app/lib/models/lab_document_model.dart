class LabDocumentModel {
  final int id;
  final int patientId;
  final String title;
  final String originalFilename;
  final String? createdAt;

  LabDocumentModel({
    required this.id,
    required this.patientId,
    required this.title,
    required this.originalFilename,
    this.createdAt,
  });

  factory LabDocumentModel.fromJson(Map<String, dynamic> json) {
    return LabDocumentModel(
      id: json['id'] as int,
      patientId: json['patient_id'] as int,
      title: json['title'] as String? ?? '',
      originalFilename: json['original_filename'] as String? ?? 'analyse.pdf',
      createdAt: json['created_at'] as String?,
    );
  }
}

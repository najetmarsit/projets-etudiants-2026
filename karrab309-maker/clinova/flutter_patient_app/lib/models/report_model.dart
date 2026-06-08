class ReportModel {
  final int id;
  final int patientId;
  final String reportType;
  final String? content;
  final String? createdAt;

  ReportModel({
    required this.id,
    required this.patientId,
    required this.reportType,
    this.content,
    this.createdAt,
  });

  factory ReportModel.fromJson(Map<String, dynamic> json) {
    return ReportModel(
      id: json['id'] as int,
      patientId: json['patient_id'] as int,
      reportType: json['report_type'] as String? ?? '',
      content: json['content'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }
}

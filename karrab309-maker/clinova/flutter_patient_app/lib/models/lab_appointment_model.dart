class LabAppointmentModel {
  final int id;
  final String scheduledAt;
  final String status;
  final String? note;

  LabAppointmentModel({
    required this.id,
    required this.scheduledAt,
    required this.status,
    this.note,
  });

  factory LabAppointmentModel.fromJson(Map<String, dynamic> json) {
    return LabAppointmentModel(
      id: (json['id'] as num).toInt(),
      scheduledAt: (json['scheduled_at'] ?? '') as String,
      status: (json['status'] ?? '') as String,
      note: json['note'] as String?,
    );
  }
}


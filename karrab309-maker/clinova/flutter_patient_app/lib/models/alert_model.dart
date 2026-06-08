class AlertModel {
  final int id;
  final int patientId;
  final int? assignedDoctorId;
  final String indicatorType;
  final String value;
  final String message;
  final String status;
  final String priority;
  final DateTime createdAt;
  final DateTime? acknowledgedAt;
  final DateTime? escalatedAt;

  AlertModel({
    required this.id,
    required this.patientId,
    required this.assignedDoctorId,
    required this.indicatorType,
    required this.value,
    required this.message,
    required this.status,
    required this.priority,
    required this.createdAt,
    required this.acknowledgedAt,
    required this.escalatedAt,
  });

  factory AlertModel.fromJson(Map<String, dynamic> json) {
    DateTime? parseDt(dynamic v) => v == null ? null : DateTime.tryParse(v.toString());
    return AlertModel(
      id: json['id'] as int,
      patientId: json['patient_id'] as int,
      assignedDoctorId: json['assigned_doctor_id'] as int?,
      indicatorType: json['indicator_type'] as String? ?? '',
      value: json['value']?.toString() ?? '',
      message: json['message'] as String? ?? '',
      status: json['status'] as String? ?? 'sent',
      priority: json['priority'] as String? ?? 'normal',
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? '') ?? DateTime.fromMillisecondsSinceEpoch(0),
      acknowledgedAt: parseDt(json['acknowledged_at']),
      escalatedAt: parseDt(json['escalated_at']),
    );
  }

  bool get isUrgent => priority.toLowerCase() == 'urgent';
  bool get isAcked => status.toLowerCase() == 'acknowledged' || acknowledgedAt != null;
}


class OperationModel {
  final int id;
  final int patientId;
  final String operationType;
  final String? operationDate;
  final String? notes;
  final String? doctorName;

  OperationModel({
    required this.id,
    required this.patientId,
    required this.operationType,
    this.operationDate,
    this.notes,
    this.doctorName,
  });

  factory OperationModel.fromJson(Map<String, dynamic> json) {
    final doctor = json['doctor'];
    String? docName;
    if (doctor is Map<String, dynamic>) {
      docName = doctor['name'] as String?;
    }
    return OperationModel(
      id: (json['id'] as num).toInt(),
      patientId: (json['patient_id'] as num).toInt(),
      operationType: json['operation_type'] as String? ?? '',
      operationDate: json['operation_date'] as String?,
      notes: json['notes'] as String?,
      doctorName: docName,
    );
  }
}

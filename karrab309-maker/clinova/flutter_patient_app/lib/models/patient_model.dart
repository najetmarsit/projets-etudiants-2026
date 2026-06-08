import 'user_model.dart';

class PatientModel {
  final int id;
  final int userId;
  final int age;
  final String gender;
  final String? medicalHistory;
  final String? doctorObservations;
  final UserModel? user;
  final List<dynamic>? operations;
  final List<dynamic>? healthIndicators;
  final List<dynamic>? alerts;
  final List<dynamic>? reports;

  final String? phone;
  final String? address;
  final String? diagnosis;
  final String? prescribedTreatment;
  final String? currentIllness;
  final String? preOpReport;
  final String? postOpReport;
  final String? admissionAt;
  final String? dischargeAt;
  final String? assignedDoctorName;

  /// `users.id` du médecin (première opération), pour messagerie sans message préalable.
  final int? primaryDoctorUserId;

  PatientModel({
    required this.id,
    required this.userId,
    required this.age,
    required this.gender,
    this.medicalHistory,
    this.doctorObservations,
    this.user,
    this.operations,
    this.healthIndicators,
    this.alerts,
    this.reports,
    this.phone,
    this.address,
    this.diagnosis,
    this.prescribedTreatment,
    this.currentIllness,
    this.preOpReport,
    this.postOpReport,
    this.admissionAt,
    this.dischargeAt,
    this.assignedDoctorName,
    this.primaryDoctorUserId,
  });

  String get displayName => user?.name ?? 'Patient #$id';
  String get subtitle => '$age ans • $gender';

  factory PatientModel.fromJson(Map<String, dynamic> json) {
    final ops = json['operations'] as List<dynamic>?;
    int? doctorId;
    if (ops != null && ops.isNotEmpty) {
      final first = ops.first;
      if (first is Map<String, dynamic>) {
        final v = first['doctor_id'];
        if (v is int) doctorId = v;
        if (v is num) doctorId = v.toInt();
      }
    }

    final ad = json['assigned_doctor'];
    String? assignedName;
    if (ad is Map<String, dynamic>) {
      assignedName = ad['name'] as String?;
    }

    return PatientModel(
      id: (json['id'] as num).toInt(),
      userId: (json['user_id'] as num).toInt(),
      age: (json['age'] as num?)?.toInt() ?? 0,
      gender: json['gender'] as String? ?? '',
      medicalHistory: json['medical_history'] as String?,
      doctorObservations: json['doctor_observations'] as String?,
      user: json['user'] != null ? UserModel.fromJson(json['user'] as Map<String, dynamic>) : null,
      operations: ops,
      healthIndicators: (json['health_indicators'] ?? json['healthIndicators']) as List<dynamic>?,
      alerts: json['alerts'] as List<dynamic>?,
      reports: json['reports'] as List<dynamic>?,
      phone: json['phone'] as String?,
      address: json['address'] as String?,
      diagnosis: json['diagnosis'] as String?,
      prescribedTreatment: json['prescribed_treatment'] as String?,
      currentIllness: json['current_illness'] as String?,
      preOpReport: json['pre_op_report'] as String?,
      postOpReport: json['post_op_report'] as String?,
      admissionAt: json['admission_at'] as String?,
      dischargeAt: json['discharge_at'] as String?,
      assignedDoctorName: assignedName,
      primaryDoctorUserId: doctorId,
    );
  }
}

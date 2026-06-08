import '../utils/media_url.dart';

/// Laravel peut renvoyer DECIMAL en JSON comme chaîne (ex. `"37.00"`).
int? _jsonIntNullable(dynamic v) {
  if (v == null) return null;
  if (v is int) return v;
  if (v is num) return v.toInt();
  if (v is String) return int.tryParse(v.trim());
  return null;
}

double? _jsonDoubleNullable(dynamic v) {
  if (v == null) return null;
  if (v is double) return v;
  if (v is int) return v.toDouble();
  if (v is num) return v.toDouble();
  if (v is String) return double.tryParse(v.trim());
  return null;
}

int _jsonIntRequired(dynamic v) {
  if (v is int) return v;
  if (v is num) return v.toInt();
  if (v is String) return int.tryParse(v.trim()) ?? 0;
  return 0;
}

class HealthIndicator {
  final int id;
  final int patientId;
  final int? heartRate;
  final double? bloodGlucose;
  final int? bloodPressureSystolic;
  final int? bloodPressureDiastolic;
  final int? painLevel;
  final double? temperature;
  final String? dressingStatus;
  final String recordedAt;
  final String? imagePath;
  /// URL complète de la photo de suivi (rempli par l'API)
  final String? imageUrl;

  HealthIndicator({
    required this.id,
    required this.patientId,
    this.heartRate,
    this.bloodGlucose,
    this.bloodPressureSystolic,
    this.bloodPressureDiastolic,
    this.painLevel,
    this.temperature,
    this.dressingStatus,
    required this.recordedAt,
    this.imagePath,
    this.imageUrl,
  });

  factory HealthIndicator.fromJson(Map<String, dynamic> json) {
    return HealthIndicator(
      id: _jsonIntRequired(json['id']),
      patientId: _jsonIntRequired(json['patient_id']),
      heartRate: _jsonIntNullable(json['heart_rate']),
      bloodGlucose: _jsonDoubleNullable(json['blood_glucose']),
      bloodPressureSystolic: _jsonIntNullable(json['blood_pressure_systolic']),
      bloodPressureDiastolic: _jsonIntNullable(json['blood_pressure_diastolic']),
      painLevel: _jsonIntNullable(json['pain_level']),
      temperature: _jsonDoubleNullable(json['temperature']),
      dressingStatus: json['dressing_status'] as String?,
      recordedAt: json['recorded_at'] as String? ?? '',
      imagePath: json['image_path'] as String?,
      imageUrl: resolveApiPublicUrl(json['image_url'] as String?),
    );
  }
}

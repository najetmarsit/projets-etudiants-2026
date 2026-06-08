import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../config/api_config.dart';
import 'cache_service.dart';
import '../models/user_model.dart';
import '../models/patient_list_result.dart';
import '../models/patient_model.dart';
import '../models/health_indicator_model.dart';
import '../models/report_model.dart';
import '../models/message_model.dart';
import '../models/lab_document_model.dart';
import '../models/operation_model.dart';
import '../models/payment_models.dart';
import '../models/notification_model.dart';
import '../models/alert_model.dart';
import '../models/nursing_note_model.dart';
import '../models/lab_appointment_model.dart';

class ApiService {
  static String? _token;
  static const _tokenKey = 'clinova.jwt';
  static final _cache = CacheService();
  static const _cacheDuration = Duration(minutes: 5);

  static void setToken(String? token) => _token = token;
  static String? get token => _token;

  static Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(_tokenKey);
  }

  static Future<void> _persistToken(String? token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    if (token == null || token.isEmpty) {
      await prefs.remove(_tokenKey);
    } else {
      await prefs.setString(_tokenKey, token);
    }
  }

  static Map<String, String> get _headers {
    final map = {'Content-Type': 'application/json', 'Accept': 'application/json'};
    if (_token != null) map['Authorization'] = 'Bearer $_token';
    return map;
  }

  static Future<Map<String, dynamic>> login(String username, String password) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/auth/login'),
      headers: _headers,
      body: jsonEncode({'username': username, 'password': password}),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode == 200 && data['success'] == true) {
      await _persistToken(data['token'] as String?);
      return data;
    }
    throw ApiException(data['message'] as String? ?? 'Erreur de connexion', response.statusCode);
  }

  /// Inscription réservée à l’app patient (rôle Patient sur l’API).
  static Future<Map<String, dynamic>> registerPatient({
    required String name,
    required String username,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/auth/register'),
      headers: _headers,
      body: jsonEncode({
        'name': name,
        'username': username,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'role': 'Patient',
      }),
    );
    Map<String, dynamic> data;
    try {
      data = jsonDecode(response.body) as Map<String, dynamic>;
    } catch (_) {
      throw ApiException('Réponse invalide du serveur', response.statusCode);
    }
    if ((response.statusCode == 200 || response.statusCode == 201) && data['success'] == true) {
      await _persistToken(data['token'] as String?);
      return data;
    }
    throw ApiException(_formatApiErrors(data), response.statusCode);
  }

  static String _formatApiErrors(Map<String, dynamic> data) {
    final errs = data['errors'];
    if (errs is Map) {
      final parts = <String>[];
      for (final e in errs.entries) {
        final v = e.value;
        if (v is List && v.isNotEmpty) {
          parts.add('${e.key}: ${v.first}');
        }
      }
      if (parts.isNotEmpty) return parts.join('\n');
    }
    return data['message'] as String? ?? 'Erreur';
  }

  static Future<UserModel> me({bool forceRefresh = false}) async {
    const cacheKey = 'auth:me';
    if (!forceRefresh) {
      final stale = _cache.getStale<UserModel>(cacheKey);
      if (stale != null) {
        if (_cache.isStale(cacheKey)) {
          unawaited(_fetchMe(cacheKey));
        }
        return stale;
      }
    }
    return _fetchMe(cacheKey);
  }

  static Future<UserModel> _fetchMe(String cacheKey) async {
    final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/auth/me'), headers: _headers);
    final user = await compute(_parseUserFromBody, response.body);
    if (response.statusCode != 200) {
      throw ApiException('Non authentifié', response.statusCode);
    }
    _cache.set(cacheKey, user, ttl: const Duration(minutes: 3), stale: const Duration(seconds: 45));
    return user;
  }

  static UserModel _parseUserFromBody(String body) {
    final data = jsonDecode(body) as Map<String, dynamic>;
    if (data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Non authentifié', 401);
    }
    return UserModel.fromJson(data['user'] as Map<String, dynamic>);
  }

  static Future<Map<String, dynamic>> myPatient({bool forceRefresh = false}) async {
    const cacheKey = 'auth:my-patient';
    if (!forceRefresh) {
      final stale = _cache.getStale<Map<String, dynamic>>(cacheKey);
      if (stale != null) {
        if (_cache.isStale(cacheKey)) {
          unawaited(_fetchMyPatient(cacheKey));
        }
        return Map<String, dynamic>.from(stale);
      }
    }
    return _fetchMyPatient(cacheKey);
  }

  static Future<Map<String, dynamic>> _fetchMyPatient(String cacheKey) async {
    final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/auth/my-patient'), headers: _headers);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Non authentifié', response.statusCode);
    }
    final map = Map<String, dynamic>.from(data['data'] as Map<String, dynamic>? ?? <String, dynamic>{});
    _cache.set(cacheKey, map, ttl: _cacheDuration, stale: const Duration(seconds: 60));
    return map;
  }

  static Future<String> chatMessage(String message) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/chat'),
      headers: _headers,
      body: jsonEncode({'message': message}),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return (data['reply'] ?? '') as String;
  }

  static Future<List<LabAppointmentModel>> getLabAppointments() async {
    final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/lab-appointments'), headers: _headers);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => LabAppointmentModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  static Future<LabAppointmentModel> createLabAppointment({
    required String scheduledAtIso,
    String? note,
  }) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/lab-appointments'),
      headers: _headers,
      body: jsonEncode({
        'scheduled_at': scheduledAtIso,
        if (note != null) 'note': note,
      }),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if ((response.statusCode != 200 && response.statusCode != 201) || data['success'] != true) {
      throw ApiException(_formatApiErrors(data), response.statusCode);
    }
    return LabAppointmentModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  static Future<void> cancelLabAppointment(int id) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/lab-appointments/$id/cancel'),
      headers: _headers,
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
  }

  /// Liste patients — première page uniquement (compatibilité).
  static Future<List<PatientModel>> getPatients({bool forceRefresh = false}) async {
    final page = await getPatientsPage(search: '', forceRefresh: forceRefresh);
    return page.items;
  }

  /// Pagination curseur + recherche serveur `q` + SWR sur la première page.
  static Future<PatientListResult> getPatientsPage({
    String? cursor,
    int perPage = 35,
    String search = '',
    bool forceRefresh = false,
  }) async {
    final q = search.trim();
    final isFirst = cursor == null || cursor.isEmpty;
    final cacheKey = 'patients:first:$q';
    if (isFirst && !forceRefresh) {
      final stale = _cache.getStale<PatientListResult>(cacheKey);
      if (stale != null) {
        if (_cache.isStale(cacheKey)) {
          unawaited(_fetchPatientsPage(cacheKey: cacheKey, cursor: null, perPage: perPage, search: q));
        }
        return stale;
      }
    }
    return _fetchPatientsPage(cacheKey: cacheKey, cursor: cursor, perPage: perPage, search: q);
  }

  static Future<PatientListResult> _fetchPatientsPage({
    required String cacheKey,
    String? cursor,
    required int perPage,
    required String search,
  }) async {
    final isFirst = cursor == null || cursor.isEmpty;
    final qp = <String, String>{
      'per_page': '$perPage',
      if (search.isNotEmpty) 'q': search,
    };
    if (!isFirst) {
      final c = cursor;
      if (c.isNotEmpty) qp['cursor'] = c;
    }
    final uri = Uri.parse('${ApiConfig.baseUrl}/patients').replace(queryParameters: qp);
    final response = await http.get(uri, headers: _headers);
    if (response.statusCode != 200) {
      throw ApiException('Erreur', response.statusCode);
    }
    final parsed = await compute(_parsePatientListPageIsolate, response.body);
    if (isFirst) {
      _cache.set(cacheKey, parsed, ttl: _cacheDuration, stale: const Duration(seconds: 45));
    }
    return parsed;
  }

  static Future<PatientModel> updatePatientDoctorFields(
    int id, {
    String? medicalHistory,
    String? diagnosis,
    String? currentIllness,
    String? prescribedTreatment,
    String? doctorObservations,
    String? preOpReport,
    String? postOpReport,
  }) async {
    final body = <String, dynamic>{};
    void put(String k, String? v) {
      if (v != null) body[k] = v;
    }

    put('medical_history', medicalHistory);
    put('diagnosis', diagnosis);
    put('current_illness', currentIllness);
    put('prescribed_treatment', prescribedTreatment);
    put('doctor_observations', doctorObservations);
    put('pre_op_report', preOpReport);
    put('post_op_report', postOpReport);

    final response = await http.patch(
      Uri.parse('${ApiConfig.baseUrl}/patients/$id'),
      headers: _headers,
      body: jsonEncode(body),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(_formatApiErrors(data), response.statusCode);
    }
    final updated = await compute(_parsePatientDetailIsolate, response.body);
    invalidatePatientCaches(id);
    _cache.set('patient:detail:$id', updated, ttl: _cacheDuration, stale: const Duration(seconds: 40));
    return updated;
  }

  static Future<PatientModel> getPatient(int id, {bool forceRefresh = false}) async {
    final key = 'patient:detail:$id';
    if (!forceRefresh) {
      final stale = _cache.getStale<PatientModel>(key);
      if (stale != null) {
        if (_cache.isStale(key)) {
          unawaited(_fetchPatientDetail(id, key));
        }
        return stale;
      }
    }
    return _fetchPatientDetail(id, key);
  }

  static Future<PatientModel> _fetchPatientDetail(int id, String cacheKey) async {
    final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/patients/$id'), headers: _headers);
    if (response.statusCode != 200) {
      throw ApiException('Erreur', response.statusCode);
    }
    final patient = await compute(_parsePatientDetailIsolate, response.body);
    _cache.set(cacheKey, patient, ttl: _cacheDuration, stale: const Duration(seconds: 40));
    return patient;
  }

  /// Invalide caches client liés à un patient (après mutation ou refresh forcé).
  static void invalidatePatientCaches(int patientId) {
    _cache.removeKeysWhere(
      (k) =>
          k == 'patient:detail:$patientId' ||
          k == 'health:$patientId' ||
          k == 'reports:$patientId' ||
          k == 'labdocs:$patientId' ||
          k.startsWith('patients:first:') ||
          k == 'patients:list' ||
          k == 'operations:list',
    );
    _cache.invalidate('auth:my-patient');
  }

  static Future<List<NotificationModel>> getNotifications({
    bool? unread,
    int limit = 50,
    bool forceRefresh = false,
  }) async {
    final cacheKey = 'notifications:${unread ?? 'all'}:$limit';
    if (!forceRefresh) {
      final stale = _cache.getStale<List<NotificationModel>>(cacheKey);
      if (stale != null) {
        if (_cache.isStale(cacheKey)) unawaited(_fetchNotifications(cacheKey, unread, limit));
        return stale;
      }
    }
    return _fetchNotifications(cacheKey, unread, limit);
  }

  static Future<List<NotificationModel>> _fetchNotifications(
    String cacheKey,
    bool? unread,
    int limit,
  ) async {
    final qp = <String, String>{'limit': '$limit'};
    if (unread != null) qp['unread'] = unread ? 'true' : 'false';
    final uri = Uri.parse('${ApiConfig.baseUrl}/notifications').replace(queryParameters: qp);
    final response = await http.get(uri, headers: _headers);
    if (response.statusCode != 200) {
      throw ApiException('Erreur', response.statusCode);
    }
    final list = await compute(_parseNotificationsFromBody, response.body);
    _cache.set(cacheKey, list, ttl: const Duration(minutes: 2), stale: const Duration(seconds: 30));
    return list;
  }

  static List<NotificationModel> _parseNotificationsFromBody(String body) {
    final data = jsonDecode(body) as Map<String, dynamic>;
    if (data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', 400);
    }
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => NotificationModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  static Future<NotificationModel> acknowledgeNotification(int id) async {
    final response = await http.patch(
      Uri.parse('${ApiConfig.baseUrl}/notifications/$id/acknowledge'),
      headers: _headers,
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return NotificationModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  static Future<List<AlertModel>> getAlerts({
    String? status,
    int limit = 200,
    bool forceRefresh = false,
  }) async {
    final cacheKey = 'alerts:${status ?? 'all'}:$limit';
    if (!forceRefresh) {
      final stale = _cache.getStale<List<AlertModel>>(cacheKey);
      if (stale != null) {
        if (_cache.isStale(cacheKey)) unawaited(_fetchAlerts(cacheKey, status, limit));
        return stale;
      }
    }
    return _fetchAlerts(cacheKey, status, limit);
  }

  static Future<List<AlertModel>> _fetchAlerts(String cacheKey, String? status, int limit) async {
    final qp = <String, String>{'limit': '$limit'};
    if (status != null && status.isNotEmpty) qp['status'] = status;
    final uri = Uri.parse('${ApiConfig.baseUrl}/alerts').replace(queryParameters: qp);
    final response = await http.get(uri, headers: _headers);
    if (response.statusCode != 200) {
      throw ApiException('Erreur', response.statusCode);
    }
    final list = await compute(_parseAlertsFromBody, response.body);
    _cache.set(cacheKey, list, ttl: const Duration(minutes: 2), stale: const Duration(seconds: 30));
    return list;
  }

  static List<AlertModel> _parseAlertsFromBody(String body) {
    final data = jsonDecode(body) as Map<String, dynamic>;
    if (data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', 400);
    }
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => AlertModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  static Future<void> acknowledgeAlert(int id) async {
    final response = await http.patch(
      Uri.parse('${ApiConfig.baseUrl}/alerts/$id/acknowledge'),
      headers: _headers,
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
  }

  static Future<void> logout() async {
    try {
      await http.post(Uri.parse('${ApiConfig.baseUrl}/auth/logout'), headers: _headers);
    } finally {
      await _persistToken(null);
      CacheService().clear();
    }
  }

  // ---------------------------
  // Doctor availability (RBAC: doctor)
  // ---------------------------

  /// GET /doctor/availability (met à jour last_seen_at côté API)
  static Future<Map<String, dynamic>> doctorAvailabilityGet() async {
    final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/doctor/availability'), headers: _headers);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return (data['data'] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
  }

  /// PATCH /doctor/availability (change le statut et broadcast vers le web)
  static Future<Map<String, dynamic>> doctorAvailabilitySet(String status) async {
    final response = await http.patch(
      Uri.parse('${ApiConfig.baseUrl}/doctor/availability'),
      headers: _headers,
      body: jsonEncode({'status': status}),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return (data['data'] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
  }

  // ---------------------------
  // Nursing notes (RBAC: nurse)
  // ---------------------------

  static Future<List<NursingNoteModel>> getNursingNotes(int patientId) async {
    final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/patients/$patientId/nursing-notes'), headers: _headers);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    final list = (data['data'] as List<dynamic>? ?? []);
    return list.map((e) => NursingNoteModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  static Future<NursingNoteModel> createNursingNote(int patientId, String note) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/patients/$patientId/nursing-notes'),
      headers: _headers,
      body: jsonEncode({'note': note}),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 201 && response.statusCode != 200) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return NursingNoteModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  static Future<void> signalUrgent(
    int patientId, {
    required String message,
    String priority = 'urgent',
  }) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/patients/$patientId/nursing-notes/signal-urgent'),
      headers: _headers,
      body: jsonEncode({
        'message': message,
        'priority': priority,
      }),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
  }

  /// GET /api/health-indicators?patient_id=
  static Future<List<HealthIndicator>> getHealthIndicators(int patientId, {bool forceRefresh = false}) async {
    final key = 'health:$patientId';
    if (!forceRefresh) {
      final stale = _cache.getStale<List<HealthIndicator>>(key);
      if (stale != null) {
        if (_cache.isStale(key)) {
          unawaited(_fetchHealthIndicators(patientId, key));
        }
        return stale;
      }
    }
    return _fetchHealthIndicators(patientId, key);
  }

  static Future<List<HealthIndicator>> _fetchHealthIndicators(int patientId, String key) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/health-indicators').replace(queryParameters: {'patient_id': '$patientId'});
    final response = await http.get(uri, headers: _headers);
    if (response.statusCode != 200) {
      throw ApiException('Erreur', response.statusCode);
    }
    final list = await compute(_parseHealthIndicatorsIsolate, response.body);
    _cache.set(key, list, ttl: _cacheDuration, stale: const Duration(seconds: 35));
    return list;
  }

  /// POST /api/health-indicators — saisie des constantes (réservée aux infirmiers côté API).
  static Future<HealthIndicator> createHealthIndicator({
    required int patientId,
    required int heartRate,
    required double temperature,
    required double bloodGlucose,
    required int bloodPressureSystolic,
    required int bloodPressureDiastolic,
  }) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/health-indicators'),
      headers: _headers,
      body: jsonEncode({
        'patient_id': patientId,
        'heart_rate': heartRate,
        'temperature': temperature,
        'blood_glucose': bloodGlucose,
        'blood_pressure_systolic': bloodPressureSystolic,
        'blood_pressure_diastolic': bloodPressureDiastolic,
      }),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if ((response.statusCode != 201 && response.statusCode != 200) || data['success'] != true) {
      throw ApiException(_formatApiErrors(data), response.statusCode);
    }
    final created = HealthIndicator.fromJson(data['data'] as Map<String, dynamic>);
    invalidatePatientCaches(patientId);
    return created;
  }

  /// GET /api/reports?patient_id=
  static Future<List<ReportModel>> getReports(int patientId, {bool forceRefresh = false}) async {
    final key = 'reports:$patientId';
    if (!forceRefresh) {
      final stale = _cache.getStale<List<ReportModel>>(key);
      if (stale != null) {
        if (_cache.isStale(key)) {
          unawaited(_fetchReports(patientId, key));
        }
        return stale;
      }
    }
    return _fetchReports(patientId, key);
  }

  static Future<List<ReportModel>> _fetchReports(int patientId, String key) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/reports').replace(queryParameters: {'patient_id': '$patientId'});
    final response = await http.get(uri, headers: _headers);
    if (response.statusCode != 200) {
      throw ApiException('Erreur', response.statusCode);
    }
    final list = await compute(_parseReportsIsolate, response.body);
    _cache.set(key, list, ttl: _cacheDuration, stale: const Duration(seconds: 45));
    return list;
  }

  /// GET /api/lab-documents — PDF du laboratoire pour le patient connecté.
  static Future<List<LabDocumentModel>> getLabDocuments() async {
    final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/lab-documents'), headers: _headers);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => LabDocumentModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  /// GET /api/lab-documents?patient_id= — pour médecin/admin
  static Future<List<LabDocumentModel>> getLabDocumentsForPatient(int patientId, {bool forceRefresh = false}) async {
    final key = 'labdocs:$patientId';
    if (!forceRefresh) {
      final stale = _cache.getStale<List<LabDocumentModel>>(key);
      if (stale != null) {
        if (_cache.isStale(key)) {
          unawaited(_fetchLabDocumentsForPatient(patientId, key));
        }
        return stale;
      }
    }
    return _fetchLabDocumentsForPatient(patientId, key);
  }

  static Future<List<LabDocumentModel>> _fetchLabDocumentsForPatient(int patientId, String key) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/lab-documents').replace(queryParameters: {'patient_id': '$patientId'});
    final response = await http.get(uri, headers: _headers);
    if (response.statusCode != 200) {
      throw ApiException('Erreur', response.statusCode);
    }
    final list = await compute(_parseLabDocumentsIsolate, response.body);
    _cache.set(key, list, ttl: _cacheDuration, stale: const Duration(seconds: 60));
    return list;
  }

  /// Télécharge et ouvre un PDF d’analyse (hors Web).
  static Future<void> openLabDocumentPdf(int id, String filename) async {
    if (kIsWeb) {
      throw ApiException('Ouvrez le PDF depuis l’app installée (Android / iOS / Windows).', 0);
    }
    final uri = Uri.parse('${ApiConfig.baseUrl}/lab-documents/$id/download');
    final headers = <String, String>{
      'Accept': 'application/pdf',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
    final res = await http.get(uri, headers: headers);
    if (res.statusCode != 200) {
      throw ApiException('Téléchargement impossible', res.statusCode);
    }
    final dir = await getTemporaryDirectory();
    var safe = filename.replaceAll(RegExp(r'[^\w.\-]'), '_');
    if (!safe.toLowerCase().endsWith('.pdf')) safe = '$safe.pdf';
    final file = File('${dir.path}/$safe');
    await file.writeAsBytes(res.bodyBytes);
    await OpenFilex.open(file.path);
  }

  /// GET /api/messages ou /api/messages?with_user_id=X (conversation synchronisée)
  static Future<List<MessageModel>> getMessages({int? withUserId}) async {
    final uri = withUserId != null
        ? Uri.parse('${ApiConfig.baseUrl}/messages').replace(queryParameters: {'with_user_id': '$withUserId'})
        : Uri.parse('${ApiConfig.baseUrl}/messages');
    final response = await http.get(uri, headers: _headers);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => MessageModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  /// POST /api/messages (texte et/ou pièce jointe)
  static Future<MessageModel> sendMessage(int receiverId, String content, {File? attachment}) async {
    if (attachment != null) {
      final request = http.MultipartRequest(
        'POST',
        Uri.parse('${ApiConfig.baseUrl}/messages'),
      );
      request.headers['Authorization'] = 'Bearer $_token';
      request.headers['Accept'] = 'application/json';
      request.fields['receiver_id'] = '$receiverId';
      if (content.isNotEmpty) request.fields['content'] = content;
      request.files.add(await http.MultipartFile.fromPath('attachment', attachment.path));
      final streamed = await request.send();
      final response = await http.Response.fromStream(streamed);
      final data = jsonDecode(response.body) as Map<String, dynamic>;
      if (response.statusCode != 201 && response.statusCode != 200) {
        throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
      }
      return MessageModel.fromJson(data['data'] as Map<String, dynamic>);
    }
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/messages'),
      headers: _headers,
      body: jsonEncode({'receiver_id': receiverId, 'content': content.isEmpty ? '[Fichier joint]' : content}),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 201 && response.statusCode != 200) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return MessageModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  /// POST /api/auth/profile-photo (photo de profil visible par le médecin).
  /// Utilise les octets (pas [File]) pour compatibilité **Web** (`MultipartFile.fromPath` exige `dart:io`).
  static Future<UserModel> uploadProfilePhoto(List<int> photoBytes, {String filename = 'profile.jpg'}) async {
    var safeName = filename.replaceAll(RegExp(r'[^\w.\-]'), '_');
    if (safeName.isEmpty) safeName = 'profile.jpg';
    if (!RegExp(r'\.(jpe?g|png|gif|webp)$', caseSensitive: false).hasMatch(safeName)) {
      safeName = '$safeName.jpg';
    }
    final request = http.MultipartRequest(
      'POST',
      Uri.parse('${ApiConfig.baseUrl}/auth/profile-photo'),
    );
    request.headers['Authorization'] = 'Bearer $_token';
    request.headers['Accept'] = 'application/json';
    request.files.add(
      http.MultipartFile.fromBytes('photo', photoBytes, filename: safeName),
    );
    final streamed = await request.send();
    final response = await http.Response.fromStream(streamed);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return UserModel.fromJson(data['user'] as Map<String, dynamic>);
  }

  /// GET /api/operations — le patient ne voit que ses interventions (rendez-vous).
  static Future<List<OperationModel>> getOperations({bool forceRefresh = false}) async {
    const key = 'operations:list';
    if (!forceRefresh) {
      final stale = _cache.getStale<List<OperationModel>>(key);
      if (stale != null) {
        if (_cache.isStale(key)) {
          unawaited(_fetchOperations(key));
        }
        return stale;
      }
    }
    return _fetchOperations(key);
  }

  static Future<List<OperationModel>> _fetchOperations(String key) async {
    final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/operations'), headers: _headers);
    if (response.statusCode != 200) {
      throw ApiException('Erreur', response.statusCode);
    }
    final list = await compute(_parseOperationsIsolate, response.body);
    _cache.set(key, list, ttl: _cacheDuration, stale: const Duration(seconds: 40));
    return list;
  }

  /// GET /api/payments/balance — solde, lignes de facturation, paiements récents.
  static Future<PaymentBalanceModel> getPaymentBalance() async {
    final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/payments/balance'), headers: _headers);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return PaymentBalanceModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  /// Staff: GET /api/payments/balance?patient_id= — bilan d'un patient précis.
  static Future<PaymentBalanceModel> getPaymentBalanceForPatient(int patientId) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/payments/balance').replace(queryParameters: {'patient_id': '$patientId'});
    final response = await http.get(uri, headers: _headers);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return PaymentBalanceModel.fromJson(data['data'] as Map<String, dynamic>);
  }

  /// Staff: POST /api/patients/{patientId}/billing/items — prix auto côté API.
  static Future<Map<String, dynamic>> addPatientBillingItem({
    required int patientId,
    required String kind, // visit, medication, analysis, meal
    required String label,
  }) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/patients/$patientId/billing/items'),
      headers: _headers,
      body: jsonEncode({'kind': kind, 'label': label}),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 201 && response.statusCode != 200) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return data;
  }

  /// GET /api/payments — tous les paiements du patient connecté.
  static Future<List<PaymentModel>> getPayments() async {
    final response = await http.get(Uri.parse('${ApiConfig.baseUrl}/payments'), headers: _headers);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    final list = data['data'] as List<dynamic>? ?? [];
    return list.map((e) => PaymentModel.fromJson(e as Map<String, dynamic>)).toList();
  }

  /// Télécharge et ouvre le PDF du reçu (hors Web).
  static Future<void> openPaymentReceiptPdf(int paymentId, String receiptNumber) async {
    if (kIsWeb) {
      throw ApiException('Ouvrez le reçu depuis l’app installée (Android / iOS / Windows).', 0);
    }
    final uri = Uri.parse('${ApiConfig.baseUrl}/payments/$paymentId/receipt');
    final headers = <String, String>{
      'Accept': 'application/pdf',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
    final res = await http.get(uri, headers: headers);
    if (res.statusCode != 200) {
      throw ApiException('Téléchargement impossible', res.statusCode);
    }
    final dir = await getTemporaryDirectory();
    final safe = receiptNumber.replaceAll(RegExp(r'[^\w.\-]'), '_');
    final file = File('${dir.path}/recu_paiement_$safe.pdf');
    await file.writeAsBytes(res.bodyBytes);
    await OpenFilex.open(file.path);
  }

  // --- Clinova AI UI Engine (design system dynamique) ---

  static Future<Map<String, dynamic>> generateAiUi({
    String screen = 'dashboard',
    String role = 'Doctor',
    String patientStatus = 'normal',
    String dataDensity = 'medium',
  }) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/ai-ui/generate'),
      headers: _headers,
      body: jsonEncode({
        'screen': screen,
        'role': role,
        'patient_status': patientStatus,
        'data_density': dataDensity,
      }),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur AI UI', response.statusCode);
    }
    return Map<String, dynamic>.from(data['data'] as Map? ?? {});
  }

  static Future<Map<String, dynamic>> postAiUiContext({
    String screen = 'dashboard',
    String role = 'Doctor',
    String patientStatus = 'normal',
    String dataDensity = 'medium',
  }) async {
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/ai-ui/context'),
      headers: _headers,
      body: jsonEncode({
        'screen': screen,
        'role': role,
        'patient_status': patientStatus,
        'data_density': dataDensity,
      }),
    );
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return Map<String, dynamic>.from(data['data'] as Map? ?? {});
  }

  static Future<Map<String, dynamic>> getAiScreenImages({
    required String screen,
    String mode = 'normal',
  }) async {
    final uri = Uri.parse('${ApiConfig.baseUrl}/ai-images/screen').replace(queryParameters: {
      'screen': screen,
      'mode': mode,
    });
    final response = await http.get(uri, headers: _headers);
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode != 200 || data['success'] != true) {
      throw ApiException(data['message'] as String? ?? 'Erreur', response.statusCode);
    }
    return Map<String, dynamic>.from(data['data'] as Map? ?? {});
  }
}

class ApiException implements Exception {
  final String message;
  final int statusCode;
  ApiException(this.message, this.statusCode);
  @override
  String toString() => message;
}

/// Parsing JSON patient dans un isolate (gros dossiers).
PatientListResult _parsePatientListPageIsolate(String body) {
  final data = jsonDecode(body) as Map<String, dynamic>;
  if (data['success'] != true) {
    throw ApiException(data['message'] as String? ?? 'Erreur', 400);
  }
  final raw = data['data'];
  final List<dynamic> list;
  if (raw is List) {
    list = raw;
  } else if (raw is Map && raw['data'] is List) {
    list = raw['data'] as List<dynamic>;
  } else {
    list = [];
  }
  final items = list.map((e) => PatientModel.fromJson(e as Map<String, dynamic>)).toList();
  final meta = data['meta'] as Map<String, dynamic>?;
  final next = meta?['next_cursor'] as String?;
  final hasMore = meta?['has_more'] == true;
  return PatientListResult(items: items, nextCursor: next, hasMore: hasMore);
}

PatientModel _parsePatientDetailIsolate(String body) {
  final data = jsonDecode(body) as Map<String, dynamic>;
  if (data['success'] != true) {
    throw ApiException(data['message'] as String? ?? 'Patient non trouvé', 400);
  }
  return PatientModel.fromJson(data['data'] as Map<String, dynamic>);
}

List<HealthIndicator> _parseHealthIndicatorsIsolate(String body) {
  final data = jsonDecode(body) as Map<String, dynamic>;
  if (data['success'] != true) {
    throw ApiException(data['message'] as String? ?? 'Erreur', 400);
  }
  final list = data['data'] as List<dynamic>? ?? [];
  return list.map((e) => HealthIndicator.fromJson(e as Map<String, dynamic>)).toList();
}

List<ReportModel> _parseReportsIsolate(String body) {
  final data = jsonDecode(body) as Map<String, dynamic>;
  if (data['success'] != true) {
    throw ApiException(data['message'] as String? ?? 'Erreur', 400);
  }
  final list = data['data'] as List<dynamic>? ?? [];
  return list.map((e) => ReportModel.fromJson(e as Map<String, dynamic>)).toList();
}

List<LabDocumentModel> _parseLabDocumentsIsolate(String body) {
  final data = jsonDecode(body) as Map<String, dynamic>;
  if (data['success'] != true) {
    throw ApiException(data['message'] as String? ?? 'Erreur', 400);
  }
  final list = data['data'] as List<dynamic>? ?? [];
  return list.map((e) => LabDocumentModel.fromJson(e as Map<String, dynamic>)).toList();
}

List<OperationModel> _parseOperationsIsolate(String body) {
  final data = jsonDecode(body) as Map<String, dynamic>;
  if (data['success'] != true) {
    throw ApiException(data['message'] as String? ?? 'Erreur', 400);
  }
  final list = data['data'] as List<dynamic>? ?? [];
  return list.map((e) => OperationModel.fromJson(e as Map<String, dynamic>)).toList();
}

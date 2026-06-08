import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map, tap } from 'rxjs';
import { apiConfig } from '../config/api.config';
import { ApiCacheService } from './api-cache.service';
import { NotificationItem } from '../models/notification.model';
import {
  Patient,
  Operation,
  HealthIndicator,
  Alert,
  Message,
  Report,
} from '../models/patient.model';

export interface ApiListResponse<T> {
  success: boolean;
  data: T[];
  message?: string;
  meta?: {
    next_cursor?: string | null;
    per_page?: number;
    has_more?: boolean;
    page?: number;
    count?: number;
  };
}

export interface ApiSingleResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

export interface PatientBillingItem {
  id: number;
  patient_id: number;
  kind: string;
  label: string;
  amount: number;
  performed_at?: string | null;
  created_by_user_id?: number | null;
  created_at?: string;
}

export interface NotificationListResponse {
  success: boolean;
  data: NotificationItem[];
}

@Injectable({ providedIn: 'root' })
export class ApiService {
  private http = inject(HttpClient);
  private cache = inject(ApiCacheService);

  private bgGet<T>(url: string, params?: HttpParams): Observable<T> {
    return this.http.get<T>(url, {
      params,
      headers: this.cache.backgroundHeaders(),
    });
  }

  /** Patient connecté : récupérer son dossier patient (dont token QR public). */
  getMyPatient(): Observable<ApiSingleResponse<Patient>> {
    return this.http.get<ApiSingleResponse<Patient>>(`${apiConfig.baseUrl}/auth/my-patient`);
  }

  updateLocale(locale: 'en' | 'fr' | 'ar'): Observable<{ success: boolean; locale: string }> {
    return this.http.patch<{ success: boolean; locale: string }>(
      `${apiConfig.baseUrl}/auth/locale`,
      { locale }
    );
  }

  // Dashboard (médecin / admin)
  getDashboardStats(forceRefresh = false): Observable<{
    success: boolean;
    data: {
      patients: number;
      doctors: number;
      appointments: number;
      alerts: number;
      secretaries?: number;
      payments_today?: number;
      payments_month?: number;
      recent_payments?: DashboardRecentPayment[];
    };
  }> {
    const key = 'dashboard:stats';
    if (forceRefresh) this.cache.invalidate(key);
    return this.cache.get(
      key,
      () =>
        this.http.get<{
          success: boolean;
          data: {
            patients: number;
            doctors: number;
            appointments: number;
            alerts: number;
            secretaries?: number;
            payments_today?: number;
            payments_month?: number;
            recent_payments?: DashboardRecentPayment[];
          };
        }>(`${apiConfig.baseUrl}/dashboard/stats`),
      { staleMs: 90_000, ttlMs: 180_000 }
    );
  }

  getDashboardChartData(days = 30): Observable<{ success: boolean; data: { labels: string[]; consultations: number[] } }> {
    const key = `dashboard:chart:${days}`;
    return this.cache.get(
      key,
      () =>
        this.http.get<{ success: boolean; data: { labels: string[]; consultations: number[] } }>(
          `${apiConfig.baseUrl}/dashboard/chart-data`,
          { params: { days: String(days) } }
        ),
      { staleMs: 120_000 }
    );
  }

  /** Admin / comptable : agrégats analytics (graphiques, sans données mockées). */
  getDashboardAnalytics(): Observable<{
    success: boolean;
    data: {
      alerts_by_status: { labels: string[]; values: number[] };
      patients_by_status: { labels: string[]; values: number[] };
      doctor_load: { labels: string[]; values: number[] };
      revenue_by_month: { labels: string[]; values: number[] };
      consultations_30d: { labels: string[]; values: number[] };
      admissions_month: { labels: string[]; values: number[] };
    };
  }> {
    return this.cache.get(
      'dashboard:analytics',
      () =>
        this.http.get<{
      success: boolean;
      data: {
        alerts_by_status: { labels: string[]; values: number[] };
        patients_by_status: { labels: string[]; values: number[] };
        doctor_load: { labels: string[]; values: number[] };
        revenue_by_month: { labels: string[]; values: number[] };
        consultations_30d: { labels: string[]; values: number[] };
        admissions_month: { labels: string[]; values: number[] };
      };
    }>(`${apiConfig.baseUrl}/dashboard/analytics`),
      { staleMs: 120_000 }
    );
  }

  getDoctorAnalytics(): Observable<{
    success: boolean;
    data: {
      assigned_patients: number;
      active_patients: number;
      pending_alerts: number;
      consultations_30d: { labels: string[]; values: number[] };
    };
  }> {
    return this.cache.get(
      'dashboard:analytics:doctor',
      () =>
        this.http.get<{
      success: boolean;
      data: {
        assigned_patients: number;
        active_patients: number;
        pending_alerts: number;
        consultations_30d: { labels: string[]; values: number[] };
      };
    }>(`${apiConfig.baseUrl}/dashboard/analytics/doctor`),
      { staleMs: 90_000 }
    );
  }

  getLabAnalytics(): Observable<{
    success: boolean;
    data: {
      documents_total: number;
      documents_month: number;
      patients_referenced: number;
      appointments_by_status: { labels: string[]; values: number[] };
      documents_by_month: { labels: string[]; values: number[] };
    };
  }> {
    return this.http.get<{
      success: boolean;
      data: {
        documents_total: number;
        documents_month: number;
        patients_referenced: number;
        appointments_by_status: { labels: string[]; values: number[] };
        documents_by_month: { labels: string[]; values: number[] };
      };
    }>(`${apiConfig.baseUrl}/dashboard/analytics/lab`);
  }

  getRecentAppointments(limit = 10): Observable<ApiListResponse<Operation>> {
    return this.http.get<ApiListResponse<Operation>>(
      `${apiConfig.baseUrl}/dashboard/recent-appointments`,
      { params: { limit: String(limit) } }
    );
  }

  getDoctors(forceRefresh = false): Observable<ApiListResponse<import('../models/user.model').User>> {
    const key = 'doctors:list';
    if (forceRefresh) this.cache.invalidate(key);
    return this.cache.get(
      key,
      () =>
        this.http.get<ApiListResponse<import('../models/user.model').User>>(
          `${apiConfig.baseUrl}/doctors`
        ),
      { staleMs: 45_000, ttlMs: 120_000 }
    );
  }

  getSecretaryAnalytics(forceRefresh = false): Observable<{
    success: boolean;
    data: {
      admissions_month: number;
      pending_specialist_appointments: number;
      doctors_available: number;
      specialist_by_status: { labels: string[]; values: number[] };
      admissions_chart: { labels: string[]; values: number[] };
    };
  }> {
    const key = 'dashboard:analytics:secretary';
    if (forceRefresh) this.cache.invalidate(key);
    return this.cache.get(
      key,
      () =>
        this.http.get<{
          success: boolean;
          data: {
            admissions_month: number;
            pending_specialist_appointments: number;
            doctors_available: number;
            specialist_by_status: { labels: string[]; values: number[] };
            admissions_chart: { labels: string[]; values: number[] };
          };
        }>(`${apiConfig.baseUrl}/dashboard/analytics/secretary`),
      { staleMs: 90_000, ttlMs: 180_000 }
    );
  }

  /** Admin : liste des infirmiers (orientation / notifications). */
  getNurses(): Observable<ApiListResponse<import('../models/user.model').User>> {
    return this.http.get<ApiListResponse<import('../models/user.model').User>>(
      `${apiConfig.baseUrl}/dashboard/nurses`
    );
  }

  // Patients
  getUsersForAssignment(): Observable<{ success: boolean; data: { id: number; name: string; username: string; email?: string }[] }> {
    return this.http.get<{ success: boolean; data: { id: number; name: string; username: string; email?: string }[] }>(
      `${apiConfig.baseUrl}/patients/users-for-assignment`
    );
  }

  getPatients(options?: { perPage?: number; cursor?: string; forceRefresh?: boolean }): Observable<ApiListResponse<Patient>> {
    const perPage = options?.perPage ?? 50;
    const cursor = options?.cursor ?? '';
    const key = `patients:list:${perPage}:${cursor}`;
    if (options?.forceRefresh) this.cache.invalidate(key);
    let params = new HttpParams().set('per_page', String(perPage));
    if (cursor) params = params.set('cursor', cursor);
    return this.cache.get(
      key,
      () =>
        this.http
          .get<ApiListResponse<Patient> | { success: boolean; data: Patient[] | { data?: Patient[] }; meta?: ApiListResponse<Patient>['meta'] }>(
            `${apiConfig.baseUrl}/patients`,
            { params }
          )
          .pipe(map((r) => this.normalizePatientsResponse(r))),
      { staleMs: 45_000, ttlMs: 120_000 }
    );
  }

  private normalizePatientsResponse(
    r: ApiListResponse<Patient> | { success: boolean; data: Patient[] | { data?: Patient[] }; meta?: ApiListResponse<Patient>['meta'] }
  ): ApiListResponse<Patient> {
    const raw = r.data;
    const list = Array.isArray(raw) ? raw : (raw as { data?: Patient[] })?.data ?? [];
    return { success: r.success, data: list, meta: (r as ApiListResponse<Patient>).meta };
  }

  getPatient(id: number): Observable<ApiSingleResponse<Patient>> {
    const key = `patient:${id}`;
    return this.cache.get(
      key,
      () => this.http.get<ApiSingleResponse<Patient>>(`${apiConfig.baseUrl}/patients/${id}`),
      { staleMs: 60_000 }
    );
  }

  invalidatePatientsCache(): void {
    this.cache.invalidatePrefix('patients:');
  }

  /** Peut inclure `new_user` pour créer le compte mobile depuis le dashboard. */
  createPatient(body: Partial<Patient> | Record<string, unknown>): Observable<ApiSingleResponse<Patient>> {
    return this.http.post<ApiSingleResponse<Patient>>(`${apiConfig.baseUrl}/patients`, body).pipe(
      tap(() => this.invalidatePatientsCache())
    );
  }

  updatePatient(id: number, body: Partial<Patient>): Observable<ApiSingleResponse<Patient>> {
    return this.http.put<ApiSingleResponse<Patient>>(`${apiConfig.baseUrl}/patients/${id}`, body).pipe(
      tap(() => {
        this.cache.invalidate(`patient:${id}`);
        this.invalidatePatientsCache();
      })
    );
  }

  /** Admin : assigner ou retirer le médecin principal d’un patient. */
  adminAssignPatientDoctor(patientId: number, assigned_doctor_id: number | null): Observable<ApiSingleResponse<Patient>> {
    return this.http.post<ApiSingleResponse<Patient>>(`${apiConfig.baseUrl}/admin/patients/${patientId}/assign-doctor`, {
      assigned_doctor_id,
    });
  }

  /** Admin : orienter médecin(s) et/ou infirmier(s) vers un patient (message + résumé patient). */
  adminNotifyStaffAboutPatient(
    patientId: number,
    body: {
      message: string;
      title?: string;
      doctor_user_id?: number | null;
      nurse_user_ids?: number[];
    }
  ): Observable<{ success: boolean; message?: string; errors?: Record<string, string[]> }> {
    return this.http.post<{ success: boolean; message?: string; errors?: Record<string, string[]> }>(
      `${apiConfig.baseUrl}/patients/${patientId}/notify-staff`,
      body
    );
  }

  /** @deprecated Préférer adminNotifyStaffAboutPatient ; même endpoint côté API. */
  adminNotifyDoctorAboutPatient(
    patientId: number,
    body: { doctor_user_id: number; message: string; title?: string }
  ): Observable<{ success: boolean; message?: string; errors?: Record<string, string[]> }> {
    return this.adminNotifyStaffAboutPatient(patientId, {
      doctor_user_id: body.doctor_user_id,
      message: body.message,
      ...(body.title ? { title: body.title } : {}),
    });
  }

  doctorAvailabilityGet(): Observable<ApiSingleResponse<{ doctor_id: number; status: string; last_seen_at?: string | null }>> {
    return this.http.get<ApiSingleResponse<{ doctor_id: number; status: string; last_seen_at?: string | null }>>(
      `${apiConfig.baseUrl}/doctor/availability`
    );
  }

  doctorAvailabilitySet(status: 'available' | 'busy' | 'offline' | 'on_call'): Observable<ApiSingleResponse<{ doctor_id: number; status: string; last_seen_at?: string | null }>> {
    return this.http.patch<ApiSingleResponse<{ doctor_id: number; status: string; last_seen_at?: string | null }>>(
      `${apiConfig.baseUrl}/doctor/availability`,
      { status }
    );
  }

  deletePatient(id: number): Observable<{ success: boolean; message?: string }> {
    return this.http.delete<{ success: boolean; message?: string }>(
      `${apiConfig.baseUrl}/patients/${id}`
    );
  }

  // Operations
  getOperations(patientId?: number): Observable<ApiListResponse<Operation>> {
    let params = new HttpParams();
    if (patientId) params = params.set('patient_id', patientId);
    return this.http.get<ApiListResponse<Operation>>(`${apiConfig.baseUrl}/operations`, {
      params: params.keys().length ? params : undefined,
    });
  }

  getOperation(id: number): Observable<ApiSingleResponse<Operation>> {
    return this.http.get<ApiSingleResponse<Operation>>(
      `${apiConfig.baseUrl}/operations/${id}`
    );
  }

  createOperation(body: Partial<Operation>): Observable<ApiSingleResponse<Operation>> {
    return this.http.post<ApiSingleResponse<Operation>>(
      `${apiConfig.baseUrl}/operations`,
      body
    );
  }

  updateOperation(
    id: number,
    body: Partial<Operation>
  ): Observable<ApiSingleResponse<Operation>> {
    return this.http.put<ApiSingleResponse<Operation>>(
      `${apiConfig.baseUrl}/operations/${id}`,
      body
    );
  }

  deleteOperation(id: number): Observable<{ success: boolean }> {
    return this.http.delete<{ success: boolean }>(`${apiConfig.baseUrl}/operations/${id}`);
  }

  // Health indicators
  getHealthIndicators(patientId?: number): Observable<ApiListResponse<HealthIndicator>> {
    let params = new HttpParams();
    if (patientId) params = params.set('patient_id', patientId);
    return this.http.get<ApiListResponse<HealthIndicator>>(
      `${apiConfig.baseUrl}/health-indicators`,
      { params: params.keys().length ? params : undefined }
    );
  }

  createHealthIndicator(
    body: Partial<HealthIndicator>
  ): Observable<ApiSingleResponse<HealthIndicator>> {
    return this.http.post<ApiSingleResponse<HealthIndicator>>(
      `${apiConfig.baseUrl}/health-indicators`,
      body
    );
  }

  updateHealthIndicator(
    id: number,
    body: Partial<HealthIndicator>
  ): Observable<ApiSingleResponse<HealthIndicator>> {
    return this.http.put<ApiSingleResponse<HealthIndicator>>(
      `${apiConfig.baseUrl}/health-indicators/${id}`,
      body
    );
  }

  deleteHealthIndicator(id: number): Observable<{ success: boolean }> {
    return this.http.delete<{ success: boolean }>(
      `${apiConfig.baseUrl}/health-indicators/${id}`
    );
  }

  // Alerts
  getAlerts(patientId?: number, status?: string): Observable<ApiListResponse<Alert>> {
    let params = new HttpParams();
    if (patientId) params = params.set('patient_id', patientId);
    if (status) params = params.set('status', status);
    return this.http.get<ApiListResponse<Alert>>(`${apiConfig.baseUrl}/alerts`, {
      params: params.keys().length ? params : undefined,
    });
  }

  acknowledgeAlert(id: number): Observable<ApiSingleResponse<Alert>> {
    return this.http.patch<ApiSingleResponse<Alert>>(
      `${apiConfig.baseUrl}/alerts/${id}/acknowledge`,
      {}
    );
  }

  // Messages (sync Dashboard / app mobile)
  getMessages(withUserId?: number, page = 1, perPage = 50): Observable<ApiListResponse<Message>> {
    const key = `messages:${withUserId ?? 'all'}:${page}:${perPage}`;
    let params = new HttpParams().set('page', String(page)).set('per_page', String(perPage));
    if (withUserId) params = params.set('with_user_id', String(withUserId));
    return this.cache.get(
      key,
      () =>
        this.http.get<ApiListResponse<Message>>(`${apiConfig.baseUrl}/messages`, {
          params,
        }),
      { staleMs: 30_000 }
    );
  }

  getMessage(id: number): Observable<ApiSingleResponse<Message>> {
    return this.http.get<ApiSingleResponse<Message>>(
      `${apiConfig.baseUrl}/messages/${id}`
    );
  }

  sendMessage(receiver_id: number, content: string, attachment?: File): Observable<ApiSingleResponse<Message>> {
    if (attachment) {
      const form = new FormData();
      form.append('receiver_id', String(receiver_id));
      if (content) form.append('content', content);
      form.append('attachment', attachment);
      return this.http.post<ApiSingleResponse<Message>>(`${apiConfig.baseUrl}/messages`, form);
    }
    return this.http.post<ApiSingleResponse<Message>>(`${apiConfig.baseUrl}/messages`, {
      receiver_id,
      content: content || '[Fichier joint]',
    });
  }

  markMessageRead(id: number): Observable<ApiSingleResponse<Message>> {
    return this.http.patch<ApiSingleResponse<Message>>(
      `${apiConfig.baseUrl}/messages/${id}/read`,
      {}
    );
  }

  // Reports
  getReports(patientId?: number): Observable<ApiListResponse<Report>> {
    let params = new HttpParams();
    if (patientId) params = params.set('patient_id', patientId);
    return this.http.get<ApiListResponse<Report>>(`${apiConfig.baseUrl}/reports`, {
      params: params.keys().length ? params : undefined,
    });
  }

  getReport(id: number): Observable<ApiSingleResponse<Report>> {
    return this.http.get<ApiSingleResponse<Report>>(`${apiConfig.baseUrl}/reports/${id}`);
  }

  createReport(patient_id: number, report_type: string, content?: string): Observable<ApiSingleResponse<Report>> {
    return this.http.post<ApiSingleResponse<Report>>(`${apiConfig.baseUrl}/reports`, {
      patient_id,
      report_type,
      content: content ?? undefined,
    });
  }

  uploadProfilePhoto(file: File): Observable<{ success: boolean; user: import('../models/user.model').User; profile_photo_url?: string }> {
    const form = new FormData();
    form.append('photo', file);
    return this.http.post<{ success: boolean; user: import('../models/user.model').User; profile_photo_url?: string }>(
      `${apiConfig.baseUrl}/auth/profile-photo`,
      form
    );
  }

  deleteProfilePhoto(): Observable<{ success: boolean; message?: string }> {
    return this.http.delete<{ success: boolean; message?: string }>(
      `${apiConfig.baseUrl}/auth/profile-photo`
    );
  }

  getLabDocuments(patientId?: number): Observable<ApiListResponse<import('../models/patient.model').LabDocument>> {
    let params = new HttpParams();
    if (patientId) params = params.set('patient_id', String(patientId));
    return this.http.get<ApiListResponse<import('../models/patient.model').LabDocument>>(
      `${apiConfig.baseUrl}/lab-documents`,
      { params: params.keys().length ? params : undefined }
    );
  }

  uploadLabDocument(
    patientId: number,
    title: string,
    file: File,
    notifyTo: 'patient' | 'doctor' = 'patient'
  ): Observable<ApiSingleResponse<import('../models/patient.model').LabDocument>> {
    const form = new FormData();
    form.append('patient_id', String(patientId));
    form.append('title', title);
    form.append('file', file);
    form.append('notify_to', notifyTo);
    return this.http.post<ApiSingleResponse<import('../models/patient.model').LabDocument>>(
      `${apiConfig.baseUrl}/lab-documents`,
      form
    );
  }

  downloadLabDocumentBlob(id: number): Observable<Blob> {
    return this.http.get(`${apiConfig.baseUrl}/lab-documents/${id}/download`, {
      responseType: 'blob',
    });
  }

  uploadDressingPhoto(patientId: number, file: File, pain_level?: number, temperature?: number, dressing_status?: string): Observable<ApiSingleResponse<HealthIndicator> & { image_url?: string }> {
    const form = new FormData();
    form.append('image', file);
    if (pain_level != null) form.append('pain_level', String(pain_level));
    if (temperature != null) form.append('temperature', String(temperature));
    if (dressing_status) form.append('dressing_status', dressing_status);
    return this.http.post<ApiSingleResponse<HealthIndicator> & { image_url?: string }>(
      `${apiConfig.baseUrl}/patients/${patientId}/dressing-photo`,
      form
    );
  }

  /** Dossier public (sans JWT), affiché via lien QR. */
  getPublicPatientDossier(token: string): Observable<PublicDossierApiResponse> {
    return this.http.get<PublicDossierApiResponse>(
      `${apiConfig.baseUrl}/public/patient-dossier/${encodeURIComponent(token)}`
    );
  }

  /** Création de compte par l’admin (identifiants optionnellement envoyés par e-mail). */
  adminCreateUser(body: {
    name: string;
    username: string;
    email: string;
    password?: string;
    role: string;
    /** Obligatoire si role === Doctor */
    specialty?: string;
    send_credentials?: boolean;
    phone?: string;
  }): Observable<AdminCreateUserResponse> {
    return this.http.post<AdminCreateUserResponse>(`${apiConfig.baseUrl}/admin/users`, body);
  }

  /** Assistant conversationnel (patient connecté). */
  chatMessage(message: string): Observable<{ success: boolean; reply: string }> {
    return this.http.post<{ success: boolean; reply: string }>(`${apiConfig.baseUrl}/chat`, { message });
  }

  /** Patient : solde total dû / payé / reste. Admin/compta : passer patient_id en query. */
  getPaymentBalance(patientId?: number): Observable<PaymentBalanceResponse> {
    const url = patientId != null
      ? `${apiConfig.baseUrl}/payments/balance?patient_id=${patientId}`
      : `${apiConfig.baseUrl}/payments/balance`;
    return this.http.get<PaymentBalanceResponse>(url);
  }

  /** Staff: ajouter un acte facturable (prix auto côté API). */
  postPatientBillingItem(
    patientId: number,
    body: { kind: 'visit' | 'medication' | 'analysis' | 'meal'; label: string; performed_at?: string | null }
  ): Observable<ApiSingleResponse<PatientBillingItem>> {
    return this.http.post<ApiSingleResponse<PatientBillingItem>>(
      `${apiConfig.baseUrl}/patients/${patientId}/billing/items`,
      body
    );
  }

  // Notifications (staff web + patient mobile)
  getNotifications(params?: {
    unread?: boolean;
    limit?: number;
    audience?: string;
    page?: number;
    perPage?: number;
    forceRefresh?: boolean;
  }): Observable<NotificationListResponse> {
    const key = `notifications:${JSON.stringify(params ?? {})}`;
    if (params?.forceRefresh) this.cache.invalidate(key);
    let httpParams = new HttpParams();
    if (params?.unread != null) httpParams = httpParams.set('unread', String(params.unread));
    if (params?.limit != null) httpParams = httpParams.set('limit', String(params.limit));
    if (params?.audience) httpParams = httpParams.set('audience', params.audience);
    if (params?.page != null) httpParams = httpParams.set('page', String(params.page));
    if (params?.perPage != null) httpParams = httpParams.set('per_page', String(params.perPage));
    return this.cache.get(
      key,
      () =>
        this.http.get<NotificationListResponse>(`${apiConfig.baseUrl}/notifications`, {
          params: httpParams.keys().length ? httpParams : undefined,
        }),
      { staleMs: 25_000 }
    );
  }

  markNotificationRead(id: number): Observable<{ success: boolean; data: NotificationItem }> {
    return this.http
      .patch<{ success: boolean; data: NotificationItem }>(`${apiConfig.baseUrl}/notifications/${id}/read`, {})
      .pipe(tap(() => this.cache.invalidatePrefix('notifications:')));
  }

  acknowledgeNotification(id: number): Observable<{ success: boolean; data: NotificationItem }> {
    return this.http.patch<{ success: boolean; data: NotificationItem }>(`${apiConfig.baseUrl}/notifications/${id}/acknowledge`, {});
  }

  /** Caissier : patients sortis avec solde restant. */
  getCashierDischargePending(limit = 30): Observable<{ success: boolean; data: CashierDischargeRow[] }> {
    return this.http.get<{ success: boolean; data: CashierDischargeRow[] }>(
      `${apiConfig.baseUrl}/payments/cashier/discharge-pending?limit=${limit}`
    );
  }

  /** Statistiques entrants / sortants (admin, comptable). */
  getAdmissionsStats(from?: string, to?: string): Observable<AdmissionsStatsResponse> {
    let q = '';
    if (from) q += `from=${encodeURIComponent(from)}`;
    if (to) q += (q ? '&' : '') + `to=${encodeURIComponent(to)}`;
    return this.http.get<AdmissionsStatsResponse>(
      `${apiConfig.baseUrl}/dashboard/admissions-stats${q ? '?' + q : ''}`
    );
  }

  /** Patient : intention Stripe / PayPal pour paiement mobile. */
  postOnlinePaymentIntent(body: { provider: 'stripe' | 'paypal'; amount?: number }): Observable<OnlineIntentResponse> {
    return this.http.post<OnlineIntentResponse>(`${apiConfig.baseUrl}/payments/online/intent`, body);
  }

  /** Patient : enregistrer un paiement Stripe après succès côté SDK. */
  confirmStripePayment(payment_intent_id: string): Observable<StripeConfirmResponse> {
    return this.http.post<StripeConfirmResponse>(`${apiConfig.baseUrl}/payments/online/confirm-stripe`, {
      payment_intent_id,
    });
  }

  /** Admin + comptable : vue agrégée trésorerie / P&L sur une période. */
  getFinancialOverview(from?: string, to?: string): Observable<FinancialOverviewResponse> {
    let q = '';
    if (from) q += `from=${encodeURIComponent(from)}`;
    if (to) q += (q ? '&' : '') + `to=${encodeURIComponent(to)}`;
    return this.http.get<FinancialOverviewResponse>(
      `${apiConfig.baseUrl}/dashboard/financial-overview${q ? '?' + q : ''}`
    );
  }

  getPayments(patientId?: number): Observable<ApiListResponse<PaymentRecord>> {
    const url =
      patientId != null
        ? `${apiConfig.baseUrl}/payments?patient_id=${patientId}`
        : `${apiConfig.baseUrl}/payments`;
    return this.http.get<ApiListResponse<PaymentRecord>>(url);
  }

  /** Admin uniquement : enregistrement manuel d’un paiement. */
  postPayment(body: PostPaymentBody): Observable<ApiSingleResponse<PaymentRecord>> {
    return this.http.post<ApiSingleResponse<PaymentRecord>>(`${apiConfig.baseUrl}/payments`, body);
  }

  getLabAppointments(): Observable<ApiListResponse<LabAppointment>> {
    return this.http.get<ApiListResponse<LabAppointment>>(`${apiConfig.baseUrl}/lab-appointments`);
  }

  postLabAppointment(body: { scheduled_at: string; patient_note?: string }): Observable<ApiSingleResponse<LabAppointment>> {
    return this.http.post<ApiSingleResponse<LabAppointment>>(`${apiConfig.baseUrl}/lab-appointments`, body);
  }

  patchLabAppointment(
    id: number,
    body: { status?: string; lab_note?: string; scheduled_at?: string }
  ): Observable<ApiSingleResponse<LabAppointment>> {
    return this.http.patch<ApiSingleResponse<LabAppointment>>(`${apiConfig.baseUrl}/lab-appointments/${id}`, body);
  }

  cancelLabAppointment(id: number): Observable<{ success: boolean; message?: string }> {
    return this.http.post<{ success: boolean; message?: string }>(
      `${apiConfig.baseUrl}/lab-appointments/${id}/cancel`,
      {}
    );
  }

  getInventoryMovements(params?: { from?: string; to?: string; direction?: string }): Observable<ApiListResponse<InventoryMovement>> {
    let httpParams = new HttpParams();
    if (params?.from) httpParams = httpParams.set('from', params.from);
    if (params?.to) httpParams = httpParams.set('to', params.to);
    if (params?.direction) httpParams = httpParams.set('direction', params.direction);
    return this.http.get<ApiListResponse<InventoryMovement>>(`${apiConfig.baseUrl}/inventory-movements`, {
      params: httpParams,
    });
  }

  postInventoryMovement(body: InventoryMovementBody): Observable<ApiSingleResponse<InventoryMovement>> {
    return this.http.post<ApiSingleResponse<InventoryMovement>>(`${apiConfig.baseUrl}/inventory-movements`, body);
  }

  patchInventoryMovement(id: number, body: Partial<InventoryMovementBody>): Observable<ApiSingleResponse<InventoryMovement>> {
    return this.http.patch<ApiSingleResponse<InventoryMovement>>(`${apiConfig.baseUrl}/inventory-movements/${id}`, body);
  }

  deleteInventoryMovement(id: number): Observable<{ success: boolean; message?: string }> {
    return this.http.delete<{ success: boolean; message?: string }>(`${apiConfig.baseUrl}/inventory-movements/${id}`);
  }
}

export interface PaymentBalancePayload {
  total_due: number;
  total_paid: number;
  remaining: number;
  currency: string;
  billing_breakdown: { label: string; amount: number }[];
  billing_notes?: string | null;
  payments: {
    id: number;
    amount: string | number;
    total_amount: string | number;
    currency: string;
    paid_at: string | null;
    status?: string;
    provider: string | null;
    receipt_number: string;
  }[];
}

export interface DashboardRecentPayment {
  id: number;
  patient_id: number | null;
  amount: string | number;
  total_amount: string | number;
  currency: string;
  paid_at: string | null;
  status?: string;
  provider: string | null;
  receipt_number: string;
  created_at?: string;
  patient?: { user?: { name?: string } };
  recordedBy?: { name?: string };
}

export interface PaymentBalanceResponse {
  success: boolean;
  data: PaymentBalancePayload;
}

export interface CashierDischargeRow {
  patient: { id: number; name?: string; discharge_at?: string };
  balance: PaymentBalancePayload;
}

export interface AdmissionsStatsResponse {
  success: boolean;
  data: { from: string; to: string; entrants: number; sortants: number };
}

export interface OnlineIntentResponse {
  success: boolean;
  message?: string;
  code?: string;
  data?: {
    provider: string;
    payment_intent_id?: string;
    client_secret?: string;
    publishable_key?: string;
    order_id?: string;
    approval_url?: string;
    amount: number;
    currency?: string;
  };
}

export interface StripeConfirmResponse {
  success: boolean;
  message?: string;
  data?: { duplicate?: boolean; [key: string]: unknown };
}

export interface FinancialOverviewData {
  from: string;
  to: string;
  cash_in_from_patients: number;
  cash_out_inventory_purchases: number;
  inventory_consumption_value: number;
  net_estimated: number;
  profit_loss_simple: number;
  payments_by_patient: { patient_id: number | null; total_paid: number; patient_name?: string | null }[];
}

export interface FinancialOverviewResponse {
  success: boolean;
  data: FinancialOverviewData;
}

export interface PaymentRecord {
  id: number;
  patient_id: number | null;
  amount: string | number;
  total_amount: string | number;
  currency: string;
  paid_at: string | null;
  created_at?: string;
  status?: string;
  provider: string | null;
  receipt_number: string;
  recorded_by?: number | null;
  patient?: { user?: { name?: string } };
  recordedBy?: { name?: string };
}

export interface PostPaymentBody {
  patient_id: number;
  amount: number;
  /** Si omis, le backend utilise le montant dû du patient ou le montant versé. */
  total_amount?: number;
  currency?: string;
  paid_at?: string;
  /** paid = encaissé (défaut), pending = en attente de confirmation */
  status?: 'paid' | 'pending';
  payer_name?: string;
  national_id?: string;
  email?: string;
  phone?: string;
  city?: string;
  file_label?: string;
}

export interface LabAppointment {
  id: number;
  patient_id: number;
  scheduled_at: string;
  status: string;
  patient_note?: string | null;
  lab_note?: string | null;
  patient?: { user?: { name?: string } };
}

export interface InventoryMovement {
  id: number;
  movement_date: string;
  direction: 'in' | 'out';
  category: string;
  label: string;
  quantity: string | number | null;
  unit: string | null;
  total_value: string | number;
  currency: string;
  notes?: string | null;
  /** Réponse API Laravel (relation snake_case) */
  recorded_by_user?: { name?: string };
}

export interface InventoryMovementBody {
  movement_date: string;
  direction: 'in' | 'out';
  category?: string;
  label: string;
  quantity?: number | null;
  unit?: string | null;
  total_value?: number;
  currency?: string;
  notes?: string | null;
}

export interface PublicDossierApiResponse {
  success: boolean;
  message?: string;
  data?: {
    patient: Record<string, unknown>;
    appointments: { operation_type?: string; operation_date?: string; notes?: string; doctor_name?: string }[];
    recent_indicators: {
      heart_rate?: number | null;
      blood_glucose?: number | null;
      blood_pressure_systolic?: number | null;
      blood_pressure_diastolic?: number | null;
      pain_level?: number;
      temperature?: number;
      dressing_status?: string;
      recorded_at?: string;
    }[];
    lab_documents?: { id: number; title: string; original_filename: string; mime_type?: string; created_at?: string; download_url: string }[];
    active_alerts_count: number;
  };
}

export interface AdminCreateUserResponse {
  success: boolean;
  message?: string;
  data?: import('../models/user.model').User;
  user?: import('../models/user.model').User;
  generated_password?: string;
}

import { Component, OnInit, OnDestroy, inject, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { forkJoin, Observable } from 'rxjs';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService, DashboardRecentPayment } from '../../core/services/api.service';
import { AuthService } from '../../core/services/auth.service';
import { AppTranslateService } from '../../core/services/translate.service';
import { PortalService } from '../../core/services/portal.service';
import { Operation, Patient } from '../../core/models/patient.model';
import { NotificationItem } from '../../core/models/notification.model';
import { PatientQrWidgetComponent } from '../patients/patient-qr-widget/patient-qr-widget.component';
import { NotificationRealtimeService } from '../../core/services/notification-realtime.service';
import { dashImageUrls, dashImageFallbacks, dashNurseStripUrl, type DashImageKey } from './dashboard-image-assets';
import { LineChartComponent } from '../../shared/charts/line-chart/line-chart.component';
import { BarChartComponent } from '../../shared/charts/bar-chart/bar-chart.component';
import { PieChartComponent } from '../../shared/charts/pie-chart/pie-chart.component';
import { StatCardComponent } from '../../shared/ui/stat-card/stat-card.component';
import { SkeletonComponent, SkeletonCardComponent } from '../../shared/ui/skeleton/skeleton.component';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [
    CommonModule, RouterLink, TranslateModule,
    PatientQrWidgetComponent,
    LineChartComponent, BarChartComponent, PieChartComponent,
    StatCardComponent, SkeletonComponent, SkeletonCardComponent,
  ],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DashboardComponent implements OnInit, OnDestroy {
  readonly dashImages = dashImageUrls;
  readonly dashNurseStrip = dashNurseStripUrl;

  private api = inject(ApiService);
  private cdr = inject(ChangeDetectorRef);
  auth = inject(AuthService);
  translate = inject(AppTranslateService);
  portal = inject(PortalService);
  private realtime = inject(NotificationRealtimeService);

  availability: 'available' | 'busy' | 'offline' | 'on_call' = 'available';
  availabilityLoading = false;

  stats = {
    patients: 0,
    doctors: 0,
    appointments: 0,
    alerts: 0,
    secretaries: 0,
    paymentsToday: 0,
    paymentsMonth: 0,
  };
  recentPayments: DashboardRecentPayment[] = [];
  chartLabels: string[] = [];
  chartValues: number[] = [];
  recentAppointments: Operation[] = [];
  loading = true;
  chartLoading = true;

  doctorInbox: NotificationItem[] = [];
  private doctorPollId: ReturnType<typeof setInterval> | null = null;

  nurseInbox: NotificationItem[] = [];
  private nursePollId: ReturnType<typeof setInterval> | null = null;

  nursePatientPreview: Patient[] = [];

  private static readonly ADMIN_ORIENT_TYPES = new Set(['admin.patient_orient_staff', 'admin.patient_situation_update']);

  // Role-based stats for charts
  pieLabels: string[] = [];
  pieValues: number[] = [];

  doctorKpis = { assigned: 0, active: 0, alerts: 0 };
  secretaryKpis = { admissions: 0, pendingAppts: 0, doctorsAvailable: 0 };
  secretaryChartLabels: string[] = [];
  secretaryChartValues: number[] = [];

  ngOnInit(): void {
    if (this.auth.isNurse()) {
      this.loading = false;
      this.chartLoading = false;
      this.recentAppointments = [];
      this.loadNursePatientPreview();
      this.loadNurseInbox();
      this.realtime.start();
      this.realtime.notifications$.subscribe((n) => {
        if (this.isAdminOrientNotification(n) || !n.read_at) {
          this.nurseInbox = [n, ...this.nurseInbox.filter((x) => x.id !== n.id)].slice(0, 12);
        }
      });
      this.nursePollId = setInterval(() => {
        this.loadNurseInbox();
        this.loadNursePatientPreview();
      }, 10000);
      return;
    }

    if (this.auth.isSecretary()) {
      this.loading = true;
      this.chartLoading = true;
      this.api.getSecretaryAnalytics().subscribe({
        next: (r) => {
          if (r.success && r.data) {
            this.secretaryKpis = {
              admissions: r.data.admissions_month,
              pendingAppts: r.data.pending_specialist_appointments,
              doctorsAvailable: r.data.doctors_available,
            };
            this.secretaryChartLabels = r.data.admissions_chart.labels;
            this.secretaryChartValues = r.data.admissions_chart.values;
            this.pieLabels = r.data.specialist_by_status.labels;
            this.pieValues = r.data.specialist_by_status.values;
          }
          this.loading = false;
          this.chartLoading = false;
        },
        error: () => {
          this.loading = false;
          this.chartLoading = false;
        },
      });
      return;
    }

    if (!this.auth.isAdmin() && !this.auth.isDoctor()) {
      this.loading = false;
      this.chartLoading = false;
      this.recentAppointments = [];
      return;
    }

    this.loading = true;
    this.chartLoading = true;
    const doctorOnly = this.auth.isDoctor() && !this.auth.isAdmin();
    const batch: Record<string, Observable<unknown>> = {
      stats: this.api.getDashboardStats(),
      recent: this.api.getRecentAppointments(10),
    };
    if (!doctorOnly) {
      batch['chart'] = this.api.getDashboardChartData(30);
    }
    if (this.auth.isDoctor()) {
      batch['doctor'] = this.api.getDoctorAnalytics();
      batch['avail'] = this.api.doctorAvailabilityGet();
    }

    forkJoin(batch).subscribe({
      next: (r: Record<string, unknown>) => {
        const stats = r['stats'] as { success?: boolean; data?: Parameters<DashboardComponent['applyStats']>[0] } | undefined;
        if (stats?.success && stats.data) {
          this.applyStats(stats.data);
        }
        const chart = r['chart'] as { success?: boolean; data?: { labels?: string[]; consultations?: number[] } } | undefined;
        if (chart?.success && chart.data) {
          this.chartLabels = chart.data.labels ?? [];
          this.chartValues = chart.data.consultations ?? [];
        }
        const doctor = r['doctor'] as {
          success?: boolean;
          data?: {
            assigned_patients: number;
            active_patients: number;
            pending_alerts: number;
            consultations_30d: { labels: string[]; values: number[] };
          };
        } | undefined;
        if (doctor?.success && doctor.data) {
          this.doctorKpis = {
            assigned: doctor.data.assigned_patients,
            active: doctor.data.active_patients,
            alerts: doctor.data.pending_alerts,
          };
          if (doctorOnly) {
            this.chartLabels = doctor.data.consultations_30d.labels;
            this.chartValues = doctor.data.consultations_30d.values;
          }
        }
        const avail = r['avail'] as { data?: { status?: string } } | undefined;
        const s = avail?.data?.status;
        if (s === 'available' || s === 'busy' || s === 'offline' || s === 'on_call') {
          this.availability = s;
        }
        const recent = r['recent'] as { data?: Operation[] } | undefined;
        this.recentAppointments = (recent?.data ?? []).slice(0, 10);
        this.loading = false;
        this.chartLoading = false;
        this.cdr.markForCheck();
      },
      error: () => {
        this.loading = false;
        this.chartLoading = false;
        this.cdr.markForCheck();
      },
    });

    if (this.auth.isDoctor()) {
      this.loadDoctorInbox();
      this.realtime.start();
      this.realtime.notifications$.subscribe((n) => {
        if (this.isAdminOrientNotification(n) || !n.read_at) {
          this.doctorInbox = [n, ...this.doctorInbox.filter((x) => x.id !== n.id)].slice(0, 12);
          this.cdr.markForCheck();
        }
      });
      this.doctorPollId = setInterval(() => this.loadDoctorInbox(true), 30000);
    }
  }

  ngOnDestroy(): void {
    if (this.doctorPollId != null) {
      clearInterval(this.doctorPollId);
      this.doctorPollId = null;
    }
    if (this.nursePollId != null) {
      clearInterval(this.nursePollId);
      this.nursePollId = null;
    }
  }

  isAdminOrientNotification(n: NotificationItem): boolean {
    return DashboardComponent.ADMIN_ORIENT_TYPES.has(n.type);
  }

  loadNursePatientPreview(): void {
    this.api.getPatients().subscribe({
      next: (r) => {
        const list = r.data ?? [];
        this.nursePatientPreview = list.slice(0, 24);
      },
      error: () => (this.nursePatientPreview = []),
    });
  }

  nursePatientName(p: Patient): string {
    const fn = p.first_name;
    const ln = p.last_name;
    if (fn || ln) return [fn, ln].filter(Boolean).join(' ');
    return p.user?.name ?? `#${p.id}`;
  }

  loadDoctorInbox(background = false): void {
    this.api.getNotifications({ unread: true, limit: 20, forceRefresh: background }).subscribe({
      next: (r) => {
        if (r.success && r.data) {
          const adminPatient = r.data.filter((n) => this.isAdminOrientNotification(n));
          const rest = r.data.filter((n) => !this.isAdminOrientNotification(n));
          this.doctorInbox = [...adminPatient, ...rest].slice(0, 12);
        }
      },
      error: () => {},
    });
  }

  loadNurseInbox(): void {
    this.api.getNotifications({ unread: true, limit: 20 }).subscribe({
      next: (r) => {
        if (r.success && r.data) {
          const adminPatient = r.data.filter((n) => this.isAdminOrientNotification(n));
          const rest = r.data.filter((n) => !this.isAdminOrientNotification(n));
          this.nurseInbox = [...adminPatient, ...rest].slice(0, 12);
        }
      },
      error: () => {},
    });
  }

  loadAvailability(): void {
    if (!this.auth.isDoctor()) return;
    this.api.doctorAvailabilityGet().subscribe({
      next: (r) => {
        const s = (r.data as unknown as { status?: string })?.status;
        if (s === 'available' || s === 'busy' || s === 'offline' || s === 'on_call') {
          this.availability = s;
        }
      },
      error: () => {},
    });
  }

  setAvailability(s: 'available' | 'busy' | 'offline' | 'on_call'): void {
    if (!this.auth.isDoctor() || this.availabilityLoading) return;
    this.availabilityLoading = true;
    this.api.doctorAvailabilitySet(s).subscribe({
      next: () => {
        this.availability = s;
        this.availabilityLoading = false;
      },
      error: () => (this.availabilityLoading = false),
    });
  }

  private applyStats(d: {
    patients?: number;
    doctors?: number;
    appointments?: number;
    alerts?: number;
    secretaries?: number;
    payments_today?: number;
    payments_month?: number;
    recent_payments?: DashboardRecentPayment[];
  }): void {
    this.stats = {
      patients: d.patients ?? 0,
      doctors: d.doctors ?? 0,
      appointments: d.appointments ?? 0,
      alerts: d.alerts ?? 0,
      secretaries: d.secretaries ?? 0,
      paymentsToday: d.payments_today ?? 0,
      paymentsMonth: d.payments_month ?? 0,
    };
    this.recentPayments = d.recent_payments ?? [];
    this.pieLabels = ['Patients', 'Médecins', 'Rendez-vous', 'Alertes'];
    this.pieValues = [this.stats.patients, this.stats.doctors, this.stats.appointments, this.stats.alerts];
  }

  loadStats(): void {
    this.loading = true;
    this.api.getDashboardStats(true).subscribe({
      next: (r) => {
        if (r.success && r.data) this.applyStats(r.data);
        this.loading = false;
        this.cdr.markForCheck();
      },
      error: () => {
        this.loading = false;
        this.cdr.markForCheck();
      },
    });
  }

  loadChart(): void {
    this.chartLoading = true;
    this.api.getDashboardChartData(30).subscribe({
      next: (r) => {
        if (r.success && r.data) {
          this.chartLabels = r.data.labels ?? [];
          this.chartValues = r.data.consultations ?? [];
        }
        this.chartLoading = false;
      },
      error: () => (this.chartLoading = false),
    });
  }

  loadRecentAppointments(): void {
    this.api.getRecentAppointments(10).subscribe({
      next: (r) => {
        this.recentAppointments = (r.data ?? []).slice(0, 10);
      },
      error: () => {},
    });
  }

  getChartMax(): number {
    const m = Math.max(...this.chartValues, 1);
    return m;
  }

  deleteOperation(id: number): void {
    if (!confirm(this.translate.instant('DASHBOARD.DELETE_APPOINTMENT'))) return;
    this.api.deleteOperation(id).subscribe({
      next: () => this.loadRecentAppointments(),
      error: () => {},
    });
  }

  chamberLabel(p: Patient | undefined | null): string {
    const c = p?.chamber_number?.trim();
    return c ? c : '—';
  }

  patientName(op: Operation): string {
    const p = op.patient;
    const prefix = this.translate.instant('COMMON.PATIENT_PREFIX');
    if (!p) return `${prefix}${op.patient_id}`;
    const fn = (p as unknown as { first_name?: string }).first_name;
    const ln = (p as unknown as { last_name?: string }).last_name;
    if (fn || ln) return [fn, ln].filter(Boolean).join(' ');
    return (p as unknown as { user?: { name?: string } }).user?.name ?? `${prefix}${op.patient_id}`;
  }

  doctorName(op: Operation): string {
    const d = op.doctor;
    const prefix = this.translate.instant('COMMON.DOCTOR_PREFIX');
    return (d as unknown as { name?: string })?.name ?? `${prefix}${op.doctor_id}`;
  }

  formatMoney(n: number | string | undefined): string {
    const v = typeof n === 'string' ? parseFloat(n) : Number(n ?? 0);
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v);
  }

  paymentPatientName(p: DashboardRecentPayment): string {
    return p.patient?.user?.name ?? (p.patient_id != null ? `#${p.patient_id}` : '—');
  }

  onNurseStripError(ev: Event): void {
    const wrap = (ev.target as HTMLElement | null)?.closest('.dash-nurse-banner');
    if (wrap) (wrap as HTMLElement).style.display = 'none';
  }

  onDashImageError(ev: Event, key: DashImageKey): void {
    const el = ev.target as HTMLImageElement;
    if (el.dataset['dashImgDone'] === '1') return;
    const step = Number(el.dataset['dashImgStep'] ?? 0);
    const primary = dashImageUrls[key];
    if (step === 0) {
      const alt = primary.replace(/\.(jpe?g|png)$/i, '.webp');
      if (alt !== primary) {
        el.dataset['dashImgStep'] = '1';
        el.src = alt;
        return;
      }
    }
    el.dataset['dashImgDone'] = '1';
    el.src = dashImageFallbacks[key];
  }
}

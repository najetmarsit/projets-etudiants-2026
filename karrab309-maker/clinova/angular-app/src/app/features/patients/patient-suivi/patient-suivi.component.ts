import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../../core/services/api.service';
import { AuthService } from '../../../core/services/auth.service';
import { PortalService } from '../../../core/services/portal.service';
import { apiConfig } from '../../../core/config/api.config';
import type { Patient, HealthIndicator, Operation, Alert, Report } from '../../../core/models/patient.model';

@Component({
  selector: 'app-patient-suivi',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, TranslateModule],
  templateUrl: './patient-suivi.component.html',
  styleUrl: './patient-suivi.component.scss',
})
export class PatientSuiviComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private api = inject(ApiService);
  auth = inject(AuthService);
  portal = inject(PortalService);

  patient: Patient | null = null;
  loading = true;
  error = '';
  activeTab: 'preop' | 'postop' | 'messages' | 'analyses' = 'postop';

  /** Dernier indicateur pour les cartes en temps réel */
  lastIndicator: HealthIndicator | null = null;
  /** Indicateurs triés par date pour le graphique */
  indicatorsForChart: HealthIndicator[] = [];
  /** Alerte active (ex. température > 38°C) */
  activeAlert: Alert | null = null;
  /** Rapports du patient (onglet Analyses) */
  reports: { id: number; report_type: string; content: string | null; created_at?: string }[] = [];
  /** Édition des observations (médecin/admin) */
  doctorObservationsEdit = '';
  savingObservations = false;

  medicalHistoryEdit = '';
  diagnosisEdit = '';
  currentIllnessEdit = '';
  prescribedTreatmentEdit = '';
  preOpReportEdit = '';
  postOpReportEdit = '';
  savingMedical = false;

  /** QR code (data URL) pour lien public dossier */
  qrDataUrl: string | null = null;

  /** Libellé douleur : faible / modérée / sévère */
  get painLabel(): string {
    const p = this.lastIndicator?.pain_level;
    if (p == null) return '—';
    if (p <= 3) return 'Faible';
    if (p <= 6) return 'Modérée';
    return 'Sévère';
  }

  /** Couleur carte douleur */
  get painColor(): string {
    const p = this.lastIndicator?.pain_level;
    if (p == null) return 'gray';
    if (p <= 3) return 'green';
    if (p <= 6) return 'orange';
    return 'red';
  }

  /** Libellé pansement : normal / à surveiller */
  get dressingLabel(): string {
    const s = (this.lastIndicator?.dressing_status ?? '').toLowerCase();
    if (s === 'infected' || s === 'à surveiller' || s === 'a surveiller') return 'À surveiller';
    return s ? (s.charAt(0).toUpperCase() + s.slice(1)) : 'Normal';
  }

  /** Pansement à surveiller ? */
  get dressingWarning(): boolean {
    const s = (this.lastIndicator?.dressing_status ?? '').toLowerCase();
    return s === 'infected' || s === 'à surveiller' || s === 'a surveiller';
  }

  /** Température > 38°C => alerte */
  get hasTempAlert(): boolean {
    const t = this.lastIndicator?.temperature;
    return t != null && t > 38;
  }

  /**
   * Certains backends renvoient `recorded_at` au format `YYYY-MM-DD HH:mm:ss`
   * (non-ISO), que `new Date()` peut mal interpréter selon le navigateur.
   */
  private ts(dateLike: unknown): number {
    if (!dateLike) return 0;
    const s = String(dateLike);
    const normalized = s.includes(' ') && !s.includes('T') ? s.replace(' ', 'T') : s;
    const t = Date.parse(normalized);
    return Number.isFinite(t) ? t : 0;
  }

  tensionLabel(h: HealthIndicator | null): string {
    if (!h?.blood_pressure_systolic || !h?.blood_pressure_diastolic) {
      return '—';
    }
    return `${h.blood_pressure_systolic} / ${h.blood_pressure_diastolic}`;
  }

  /** Indicateurs avec photo pansement (téléversées par le patient), les plus récents en premier */
  get indicatorsWithPhoto(): HealthIndicator[] {
    const hi = this.getHealthIndicators();
    return hi
      .filter((h) => h.image_path)
      .sort((a, b) => this.ts(b.recorded_at) - this.ts(a.recorded_at));
  }

  /** URL publique d'une image stockée (photos pansement) */
  storageUrl(path: string | null | undefined): string {
    if (!path) return '';
    return `${apiConfig.storageBaseUrl}/storage/${path}`;
  }

  canEditObservations(): boolean {
    return this.auth.user()?.role === 'Doctor';
  }

  canEditMedicalDossier(): boolean {
    return this.auth.user()?.role === 'Doctor';
  }

  saveDoctorObservations(): void {
    if (!this.patient) return;
    this.savingObservations = true;
    this.api.updatePatient(this.patient.id, { doctor_observations: this.doctorObservationsEdit || null }).subscribe({
      next: () => {
        this.savingObservations = false;
        this.refreshPatient();
      },
      error: () => (this.savingObservations = false),
    });
  }

  saveMedicalDossier(): void {
    if (!this.patient) return;
    this.savingMedical = true;
    this.api
      .updatePatient(this.patient.id, {
        medical_history: this.medicalHistoryEdit || null,
        diagnosis: this.diagnosisEdit || null,
        current_illness: this.currentIllnessEdit || null,
        prescribed_treatment: this.prescribedTreatmentEdit || null,
        pre_op_report: this.preOpReportEdit || null,
        post_op_report: this.postOpReportEdit || null,
      })
      .subscribe({
        next: () => {
          this.savingMedical = false;
          this.refreshPatient();
        },
        error: () => (this.savingMedical = false),
      });
  }

  /** Dernière mesure par date d’enregistrement (toutes sources : infirmier, historique). */
  private syncLastIndicatorFromHealth(): void {
    const hi = this.getHealthIndicators();
    if (hi.length === 0) {
      this.lastIndicator = null;
      return;
    }
    this.lastIndicator =
      [...hi].sort((a, b) => this.ts(b.recorded_at) - this.ts(a.recorded_at))[0] ?? null;
  }

  private syncMedicalEditsFromPatient(): void {
    const p = this.patient;
    if (!p) return;
    this.medicalHistoryEdit = p.medical_history ?? '';
    this.diagnosisEdit = p.diagnosis ?? '';
    this.currentIllnessEdit = p.current_illness ?? '';
    this.prescribedTreatmentEdit = p.prescribed_treatment ?? '';
    this.preOpReportEdit = p.pre_op_report ?? '';
    this.postOpReportEdit = p.post_op_report ?? '';
  }

  onImageError(e: Event): void {
    const el = e.target as HTMLImageElement;
    if (el) {
      el.style.background = '#eee';
      el.alt = 'Image non disponible';
    }
  }

  /** Recharger les données patient (pour voir les dernières photos/messages en temps réel) */
  refreshPatient(): void {
    if (!this.patient) return;
    this.api.getPatient(this.patient.id).subscribe({
      next: (r) => {
        this.patient = r.data;
        const hi = this.getHealthIndicators();
        this.indicatorsForChart = [...hi].sort(
          (a, b) => this.ts(b.recorded_at) - this.ts(a.recorded_at)
        ).reverse();
        this.syncLastIndicatorFromHealth();
        const alerts = this.patient?.alerts ?? [];
        this.activeAlert = alerts.find((a) => a.status === 'new' || a.status === 'pending') ?? alerts[0] ?? null;
        this.doctorObservationsEdit = this.patient?.doctor_observations ?? '';
        this.syncMedicalEditsFromPatient();
        this.loadReports();
        void this.refreshQr();
      },
    });
  }

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (!id) {
      this.loading = false;
      return;
    }
    this.api.getPatient(+id).subscribe({
      next: (r) => {
        this.patient = r.data;
        this.doctorObservationsEdit = this.patient?.doctor_observations ?? '';
        this.syncMedicalEditsFromPatient();
        const hi = this.getHealthIndicators();
        this.indicatorsForChart = [...hi].sort(
          (a, b) => new Date(b.recorded_at).getTime() - new Date(a.recorded_at).getTime()
        ).reverse();
        this.syncLastIndicatorFromHealth();
        const alerts = this.patient?.alerts ?? [];
        this.activeAlert = alerts.find((a) => a.status === 'new' || a.status === 'pending') ?? alerts[0] ?? null;
        this.loading = false;
        if (this.patient) {
          this.loadReports();
          void this.refreshQr();
        }
      },
      error: (err) => {
        this.error = err.error?.message ?? 'Patient non trouvé';
        this.loading = false;
      },
    });
  }

  private loadReports(): void {
    if (!this.patient) return;
    this.api.getReports(this.patient.id).subscribe({
      next: (r) => (this.reports = r.data ?? []),
      error: () => {},
    });
  }

  private getHealthIndicators(): HealthIndicator[] {
    const p = this.patient;
    return (p?.health_indicators ?? p?.healthIndicators ?? []) as HealthIndicator[];
  }

  get lastOperation(): Operation | null {
    const ops = (this.patient?.operations ?? []) as Operation[];
    if (ops.length === 0) return null;
    return ops.sort((a, b) => new Date(b.operation_date).getTime() - new Date(a.operation_date).getTime())[0] ?? null;
  }

  get operationsList(): Operation[] {
    return (this.patient?.operations ?? []) as Operation[];
  }

  /** Données pour le graphique : dernières valeurs */
  get chartData(): { labels: string[]; temp: number[]; pain: number[] } {
    const list = this.indicatorsForChart.slice(-14);
    return {
      labels: list.map((i) => new Date(i.recorded_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })),
      temp: list.map((i) => i.temperature ?? 0),
      pain: list.map((i) => i.pain_level ?? 0),
    };
  }

  /** Points SVG pour la courbe température */
  get chartPointsTemp(): string {
    const d = this.chartData;
    if (d.temp.length === 0) return '';
    const w = 400;
    const h = 100;
    const max = Math.max(40, ...d.temp) || 40;
    return d.temp
      .map((v, i) => `${(i * w) / (d.temp.length - 1 || 1)},${h - (v / max) * h}`)
      .join(' ');
  }

  /** Points SVG pour la courbe douleur */
  get chartPointsPain(): string {
    const d = this.chartData;
    if (d.pain.length === 0) return '';
    const w = 400;
    const h = 100;
    const max = 10;
    return d.pain
      .map((v, i) => `${(i * w) / (d.pain.length - 1 || 1)},${h - (v / max) * h}`)
      .join(' ');
  }

  setTab(tab: 'preop' | 'postop' | 'messages' | 'analyses'): void {
    this.activeTab = tab;
  }

  generateReport(): void {
    if (!this.patient) return;
    this.api.createReport(this.patient.id, 'Rapport de suivi').subscribe({
      next: () => {
        this.api.getPatient(this.patient!.id).subscribe({
          next: (r) => (this.patient = r.data),
        });
      },
      error: (err) => alert(err.error?.message ?? 'Erreur lors de la génération'),
    });
  }

  sendMessage(): void {
    window.location.hash = 'messages';
    this.activeTab = 'messages';
  }

  canShowQr(): boolean {
    const r = this.auth.user()?.role;
    return r === 'Admin' || r === 'Doctor';
  }

  private async refreshQr(): Promise<void> {
    if (!this.canShowQr() || !this.patient?.qr_public_token) {
      this.qrDataUrl = null;
      return;
    }
    try {
      const QRCode = (await import('qrcode')).default;
      const url = `${apiConfig.publicAppOrigin}/public/dossier/${encodeURIComponent(this.patient.qr_public_token)}`;
      this.qrDataUrl = await QRCode.toDataURL(url, { width: 200, margin: 1 });
    } catch {
      this.qrDataUrl = null;
    }
  }
}

import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../../core/services/api.service';
import { AuthService } from '../../../core/services/auth.service';
import { PortalService } from '../../../core/services/portal.service';
import { AppTranslateService } from '../../../core/services/translate.service';
import { Patient } from '../../../core/models/patient.model';
import { User } from '../../../core/models/user.model';
import { apiConfig } from '../../../core/config/api.config';
import { PaymentBalanceResponse } from '../../../core/services/api.service';
import { ClinPageHeadComponent } from '../../../shared/ui/clin-page-head/clin-page-head.component';

@Component({
  selector: 'app-patient-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, TranslateModule, ClinPageHeadComponent],
  templateUrl: './patient-detail.component.html',
  styleUrl: './patient-detail.component.scss',
})
export class PatientDetailComponent implements OnInit {
  patient: Patient | null = null;
  loading = true;
  error = '';
  apiConfig = apiConfig;

  portal = inject(PortalService);
  private translate = inject(AppTranslateService);

  admissionAtEdit = '';
  dischargeAtEdit = '';
  billingNotesEdit = '';
  billingTotalDueEdit: number | null = null;
  billingLinesEdit: { label: string; amount: number }[] = [{ label: '', amount: 0 }];
  savingBilling = false;

  // Bilan automatique (actes)
  balance: PaymentBalanceResponse['data'] | null = null;
  loadingBalance = false;
  billingAddKind: 'medication' | 'analysis' | 'meal' | 'visit' = 'medication';
  billingAddLabel = '';
  billingAdding = false;
  billingAddError = '';

  /** Admin : orienter médecins / infirmiers vers ce patient */
  doctors: Pick<User, 'id' | 'name' | 'username'>[] = [];
  nurses: Pick<User, 'id' | 'name' | 'username'>[] = [];
  notifyDoctorUserId: number | null = null;
  notifyNurseIds: number[] = [];
  notifyTitle = '';
  notifyMessage = '';
  notifySending = false;
  notifyFeedback: { ok?: string; err?: string } | null = null;

  constructor(
    private route: ActivatedRoute,
    private api: ApiService,
    public auth: AuthService
  ) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.api.getPatient(+id).subscribe({
        next: (r) => {
          this.patient = r.data;
          this.syncBillingEdits();
          this.loadBalance();
          this.loading = false;
          if (this.auth.isAdmin()) {
            this.loadDoctorsForNotify();
            this.loadNursesForNotify();
          }
        },
        error: (err) => {
          this.error = err.error?.message ?? 'Patient non trouvé';
          this.loading = false;
        },
      });
    } else {
      this.loading = false;
    }
  }

  /** Modifier les coordonnées (admin uniquement). */
  canEditCoords(): boolean {
    return this.auth.isAdmin();
  }

  showSuivi(): boolean {
    return this.auth.isAdmin() || this.auth.isDoctor() || this.auth.isNurse();
  }

  /** Recharger la fiche après saisie des constantes (infirmier). */
  refreshAfterVitals(): void {
    if (!this.patient) return;
    this.api.getPatient(this.patient.id).subscribe({
      next: (r) => {
        this.patient = r.data;
        this.syncBillingEdits();
      },
    });
  }

  showMessage(): boolean {
    return this.auth.isAdmin() || this.auth.isDoctor();
  }

  patientHeadTitle(): string {
    const p = this.patient;
    if (!p) return '';
    return (p.first_name || p.last_name) ? `${(p.first_name ?? '').trim()} ${(p.last_name ?? '').trim()}`.trim() : (p.user?.name ?? `Patient #${p.id}`);
  }

  patientHeadSubtitle(): string {
    const p = this.patient;
    if (!p) return '';
    const g =
      p.gender === 'Male'
        ? this.translate.instant('STAFF.PATIENTS_GENDER_M')
        : p.gender === 'Female'
          ? this.translate.instant('STAFF.PATIENTS_GENDER_F')
          : this.translate.instant('STAFF.PATIENTS_GENDER_O');
    return `#${p.id} · ${p.age} ${this.translate.instant('STAFF.PATIENTS_YEARS')} · ${g}`;
  }

  hasDxSection(p: Patient): boolean {
    return !!(p.diagnosis?.trim() || p.current_illness?.trim() || p.doctor_observations?.trim());
  }

  hasHistorySection(p: Patient): boolean {
    return !!(p.medical_history?.trim() || p.pre_op_report?.trim() || p.post_op_report?.trim());
  }

  hasAnyMedicalInfo(p: Patient): boolean {
    return this.hasDxSection(p) || !!p.prescribed_treatment?.trim() || this.hasHistorySection(p);
  }

  isLab(): boolean {
    return this.auth.isLaboratory();
  }

  isAccountant(): boolean {
    return this.auth.isAccountant();
  }

  private syncBillingEdits(): void {
    const p = this.patient;
    if (!p) return;
    this.admissionAtEdit = p.admission_at ? p.admission_at.slice(0, 16) : '';
    this.dischargeAtEdit = p.discharge_at ? p.discharge_at.slice(0, 16) : '';
    this.billingNotesEdit = p.billing_notes ?? '';
    this.billingTotalDueEdit = p.billing_total_due != null ? Number(p.billing_total_due) : null;
    const lines = p.billing_breakdown?.length
      ? p.billing_breakdown.map((l) => ({ label: l.label, amount: Number(l.amount) }))
      : [{ label: '', amount: 0 }];
    this.billingLinesEdit = lines.length ? lines : [{ label: '', amount: 0 }];
  }

  canManageAutoBilling(): boolean {
    return this.auth.isAdmin() || this.auth.isDoctor() || this.auth.isNurse();
  }

  loadBalance(): void {
    if (!this.patient) return;
    this.loadingBalance = true;
    this.api.getPaymentBalance(this.patient.id).subscribe({
      next: (r) => {
        this.loadingBalance = false;
        this.balance = r.success ? r.data : null;
      },
      error: () => {
        this.loadingBalance = false;
        this.balance = null;
      },
    });
  }

  addBillableItem(): void {
    if (!this.patient || !this.canManageAutoBilling() || this.billingAdding) return;
    this.billingAddError = '';
    const label = this.billingAddLabel.trim();
    if (!label) {
      this.billingAddError = 'Libellé requis.';
      return;
    }
    this.billingAdding = true;
    this.api.postPatientBillingItem(this.patient.id, { kind: this.billingAddKind, label }).subscribe({
      next: () => {
        this.billingAdding = false;
        this.billingAddLabel = '';
        this.loadBalance();
      },
      error: (e) => {
        this.billingAdding = false;
        this.billingAddError = e?.error?.message ?? 'Erreur';
      },
    });
  }

  addBillingLine(): void {
    this.billingLinesEdit.push({ label: '', amount: 0 });
  }

  removeBillingLine(index: number): void {
    this.billingLinesEdit.splice(index, 1);
    if (!this.billingLinesEdit.length) {
      this.billingLinesEdit = [{ label: '', amount: 0 }];
    }
  }

  /** Présence (entrée / sortie) et facturation — réservé à l’admin, aligné sur le paiement patient. */
  savePresenceBilling(): void {
    if (!this.patient || !this.auth.isAdmin()) return;
    const breakdown = this.billingLinesEdit
      .filter((l) => l.label.trim() !== '')
      .map((l) => ({ label: l.label.trim(), amount: Number(l.amount) || 0 }));
    this.savingBilling = true;
    const body: Record<string, unknown> = {
      admission_at: this.admissionAtEdit || null,
      discharge_at: this.dischargeAtEdit || null,
      billing_notes: this.billingNotesEdit || null,
      billing_total_due: this.billingTotalDueEdit,
    };
    if (breakdown.length) {
      body['billing_breakdown'] = breakdown;
    } else {
      body['billing_breakdown'] = [];
    }
    this.api
      .updatePatient(this.patient.id, body as Partial<Patient>)
      .subscribe({
        next: (r) => {
          this.savingBilling = false;
          this.patient = r.data;
          this.syncBillingEdits();
        },
        error: () => (this.savingBilling = false),
      });
  }

  getHealthIndicators() {
    return this.patient?.health_indicators ?? this.patient?.healthIndicators ?? [];
  }

  private loadDoctorsForNotify(): void {
    this.api.getDoctors().subscribe({
      next: (r) => {
        if (r.success && r.data?.length) {
          this.doctors = r.data.map((d) => ({ id: d.id, name: d.name, username: d.username }));
          const assigned = this.patient?.assigned_doctor_id;
          this.notifyDoctorUserId =
            assigned && this.doctors.some((x) => x.id === assigned) ? assigned : null;
        } else {
          this.doctors = [];
          this.notifyDoctorUserId = null;
        }
      },
      error: () => {
        this.doctors = [];
        this.notifyDoctorUserId = null;
      },
    });
  }

  private loadNursesForNotify(): void {
    this.api.getNurses().subscribe({
      next: (r) => {
        if (r.success && r.data?.length) {
          this.nurses = r.data.map((n) => ({ id: n.id, name: n.name, username: n.username }));
          const assigned = this.patient?.assigned_nurse_id;
          if (assigned && this.nurses.some((x) => x.id === assigned)) {
            this.notifyNurseIds = [assigned];
          } else {
            this.notifyNurseIds = [];
          }
        } else {
          this.nurses = [];
          this.notifyNurseIds = [];
        }
      },
      error: () => {
        this.nurses = [];
        this.notifyNurseIds = [];
      },
    });
  }

  toggleNotifyNurse(id: number, checked: boolean): void {
    if (checked) {
      if (!this.notifyNurseIds.includes(id)) {
        this.notifyNurseIds = [...this.notifyNurseIds, id];
      }
    } else {
      this.notifyNurseIds = this.notifyNurseIds.filter((x) => x !== id);
    }
  }

  isNotifyNurseChecked(id: number): boolean {
    return this.notifyNurseIds.includes(id);
  }

  sendStaffOrientNotify(): void {
    this.notifyFeedback = null;
    if (!this.patient || !this.auth.isAdmin()) return;
    const doctorId = this.notifyDoctorUserId;
    const nurseIds = [...this.notifyNurseIds];
    if (!doctorId && nurseIds.length === 0) {
      this.notifyFeedback = { err: 'Sélectionnez au moins un médecin ou une infirmière.' };
      return;
    }
    const msg = this.notifyMessage.trim();
    if (!msg) {
      this.notifyFeedback = { err: 'Saisissez un message.' };
      return;
    }
    this.notifySending = true;
    const title = this.notifyTitle.trim();
    const body: {
      message: string;
      title?: string;
      doctor_user_id?: number | null;
      nurse_user_ids?: number[];
    } = { message: msg };
    if (title) body.title = title;
    if (doctorId) body.doctor_user_id = doctorId;
    if (nurseIds.length) body.nurse_user_ids = nurseIds;

    this.api.adminNotifyStaffAboutPatient(this.patient.id, body).subscribe({
      next: (res) => {
        this.notifySending = false;
        if (res.success) {
          this.notifyFeedback = { ok: res.message ?? 'Notification envoyée.' };
          this.notifyMessage = '';
          this.notifyTitle = '';
        } else {
          this.notifyFeedback = { err: res.message ?? 'Échec' };
        }
      },
      error: (e) => {
        this.notifySending = false;
        const err = e?.error;
        const first = err?.errors ? (Object.values(err.errors)[0] as string[] | undefined)?.[0] : null;
        this.notifyFeedback = { err: first ?? err?.message ?? 'Erreur réseau' };
      },
    });
  }
}

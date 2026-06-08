import { Component, OnInit, inject, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../../core/services/api.service';
import { apiConfig } from '../../../core/config/api.config';
import type { Patient } from '../../../core/models/patient.model';

@Component({
  selector: 'app-patient-qr-widget',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './patient-qr-widget.component.html',
  styleUrl: './patient-qr-widget.component.scss',
})
export class PatientQrWidgetComponent implements OnInit {
  private api = inject(ApiService);

  /** Si fourni, pré-sélectionne directement un patient. */
  @Input() initialPatientId: number | null = null;

  loading = true;
  error = '';
  patients: Patient[] = [];
  selectedPatientId: number | null = null;

  qrDataUrl: string | null = null;
  publicUrl: string | null = null;

  ngOnInit(): void {
    this.loading = true;
    this.api.getPatients().subscribe({
      next: (r) => {
        this.patients = r.data ?? [];
        const first = this.patients[0]?.id ?? null;
        this.selectedPatientId = this.initialPatientId ?? first;
        this.loading = false;
        void this.refreshQr();
      },
      error: (e) => {
        this.error = e.error?.message ?? 'Impossible de charger les patients.';
        this.loading = false;
      },
    });
  }

  patientLabel(p: Patient): string {
    const fn = (p.first_name ?? '').trim();
    const ln = (p.last_name ?? '').trim();
    const full = [fn, ln].filter(Boolean).join(' ').trim();
    return full || p.user?.name || `Patient #${p.id}`;
  }

  onPatientChange(): void {
    void this.refreshQr();
  }

  private async refreshQr(): Promise<void> {
    this.qrDataUrl = null;
    this.publicUrl = null;

    const p = this.patients.find((x) => x.id === this.selectedPatientId);
    if (!p) return;

    let token = p.qr_public_token ?? null;
    if (!token) {
      // Force la génération du token côté API (voir PatientController@show) puis re-génère le QR.
      try {
        const r = await new Promise<Patient | null>((resolve) => {
          this.api.getPatient(p.id).subscribe({
            next: (rr) => resolve(rr.data ?? null),
            error: () => resolve(null),
          });
        });
        const updated = r?.qr_public_token ?? null;
        if (updated) {
          p.qr_public_token = updated;
          token = updated;
        }
      } catch {}
    }

    if (!token) return;

    this.publicUrl = `${apiConfig.publicAppOrigin}/public/dossier/${encodeURIComponent(token)}`;
    try {
      const QRCode = (await import('qrcode')).default;
      this.qrDataUrl = await QRCode.toDataURL(this.publicUrl, { width: 200, margin: 1 });
    } catch {
      this.qrDataUrl = null;
    }
  }
}


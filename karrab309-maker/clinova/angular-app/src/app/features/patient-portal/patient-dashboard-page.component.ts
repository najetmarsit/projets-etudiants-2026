import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { forkJoin, of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { ApiService } from '../../core/services/api.service';
import { AuthService } from '../../core/services/auth.service';
import type { HealthIndicator } from '../../core/models/patient.model';
import { ClinPageHeadComponent } from '../../shared/ui/clin-page-head/clin-page-head.component';

@Component({
  selector: 'app-patient-dashboard-page',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule, ClinPageHeadComponent],
  templateUrl: './patient-dashboard-page.component.html',
  styleUrl: './patient-dashboard-page.component.scss',
})
export class PatientDashboardPageComponent implements OnInit {
  private api = inject(ApiService);
  auth = inject(AuthService);

  loading = true;
  remaining: number | null = null;
  currency = '';
  notifCount = 0;
  labPending = 0;
  hasDossier = false;
  /** Dernière ligne de constantes (lecture seule ; saisie par l’infirmier). */
  latestVitals: HealthIndicator | null = null;

  private ts(dateLike: unknown): number {
    if (!dateLike) return 0;
    const s = String(dateLike);
    const normalized = s.includes(' ') && !s.includes('T') ? s.replace(' ', 'T') : s;
    const t = Date.parse(normalized);
    return Number.isFinite(t) ? t : 0;
  }

  ngOnInit(): void {
    this.loading = true;
    forkJoin({
      bal: this.api.getPaymentBalance().pipe(
        map((r) => (r.success && r.data ? r.data : null)),
        catchError(() => of(null))
      ),
      notif: this.api.getNotifications({ limit: 80 }).pipe(
        map((r) => (r.data ?? []).filter((n) => !n.read_at).length),
        catchError(() => of(0))
      ),
      lab: this.api.getLabAppointments().pipe(
        map((r) => (r.success && r.data ? r.data.filter((a) => a.status === 'pending').length : 0)),
        catchError(() => of(0))
      ),
      me: this.api.getMyPatient().pipe(
        map((r) => !!r.data?.qr_public_token),
        catchError(() => of(false))
      ),
      vitals: this.api.getHealthIndicators().pipe(
        map((r) => {
          const list = r.data ?? [];
          if (list.length === 0) return null;
          return [...list].sort(
            (a, b) => this.ts(b.recorded_at) - this.ts(a.recorded_at)
          )[0] ?? null;
        }),
        catchError(() => of(null))
      ),
    }).subscribe({
      next: ({ bal, notif, lab, me, vitals }) => {
        if (bal) {
          this.remaining = bal.remaining;
          this.currency = bal.currency;
        }
        this.notifCount = notif;
        this.labPending = lab;
        this.hasDossier = me;
        this.latestVitals = vitals;
        this.loading = false;
      },
      error: () => (this.loading = false),
    });
  }

  formatMoney(n: number): string {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
  }
}

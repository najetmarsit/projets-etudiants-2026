import { Component, OnInit, inject, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { forkJoin } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import { PortalService } from '../../../core/services/portal.service';
import { BarChartComponent } from '../../../shared/charts/bar-chart/bar-chart.component';
import { PieChartComponent } from '../../../shared/charts/pie-chart/pie-chart.component';
import { StatCardComponent } from '../../../shared/ui/stat-card/stat-card.component';
import { SkeletonCardComponent } from '../../../shared/ui/skeleton/skeleton.component';

@Component({
  selector: 'app-secretary-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    TranslateModule,
    BarChartComponent,
    PieChartComponent,
    StatCardComponent,
    SkeletonCardComponent,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="container-fluid px-2 px-md-3 pb-4">
      <div class="page-head mb-4">
        <div>
          <h1 class="page-title">Réception Clinova</h1>
          <p class="page-subtitle">Admissions, rendez-vous spécialiste et disponibilité médecins</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a [routerLink]="['/', portal.seg(), 'patients', 'new']" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i> Nouveau patient
          </a>
          <a [routerLink]="['/', portal.seg(), 'patients']" class="btn btn-outline-primary btn-sm">Patients</a>
          <a [routerLink]="['/', portal.seg(), 'doctors']" class="btn btn-outline-primary btn-sm">Médecins</a>
        </div>
      </div>

      @if (loading) {
        <div class="row g-3 mb-4">
          @for (i of [1, 2, 3]; track i) {
            <div class="col-md-4"><app-skeleton-card /></div>
          }
        </div>
      } @else {
        <div class="row g-3 mb-4">
          <div class="col-md-4">
            <app-stat-card label="Admissions (mois)" [value]="kpis.admissions" icon="📥" trend="up" footer="Entrées enregistrées" />
          </div>
          <div class="col-md-4">
            <app-stat-card label="RDV spécialiste" [value]="kpis.pendingAppts" icon="📅" trend="neutral" footer="À venir" />
          </div>
          <div class="col-md-4">
            <app-stat-card label="Médecins disponibles" [value]="kpis.doctorsAvailable" icon="👨‍⚕️" trend="up" footer="En ligne" />
          </div>
        </div>

        <div class="row g-4">
          <div class="col-lg-8">
            <app-bar-chart
              [labels]="chartLabels"
              [values]="chartValues"
              title="Vue réception"
              subtitle="Indicateurs du mois"
              [colors]="['var(--primary)', 'var(--violet)', 'var(--accent)']" />
          </div>
          <div class="col-lg-4">
            <app-pie-chart
              [labels]="pieLabels"
              [values]="pieValues"
              title="RDV spécialiste"
              subtitle="Par statut" />
          </div>
        </div>
      }
    </div>
  `,
  styles: [`:host { display: block; }`],
})
export class SecretaryDashboardComponent implements OnInit {
  private api = inject(ApiService);
  private cdr = inject(ChangeDetectorRef);
  portal = inject(PortalService);

  loading = true;
  kpis = { admissions: 0, pendingAppts: 0, doctorsAvailable: 0 };
  chartLabels: string[] = [];
  chartValues: number[] = [];
  pieLabels: string[] = [];
  pieValues: number[] = [];

  ngOnInit(): void {
    forkJoin({
      analytics: this.api.getSecretaryAnalytics(),
      doctors: this.api.getDoctors(),
    }).subscribe({
      next: ({ analytics, doctors }) => {
        if (analytics.success && analytics.data) {
          const a = analytics.data;
          this.kpis = {
            admissions: a.admissions_month,
            pendingAppts: a.pending_specialist_appointments,
            doctorsAvailable: a.doctors_available,
          };
          this.chartLabels = a.admissions_chart.labels;
          this.chartValues = a.admissions_chart.values;
          this.pieLabels = a.specialist_by_status.labels;
          this.pieValues = a.specialist_by_status.values;
        }
        if (doctors.success && doctors.data?.length && this.kpis.doctorsAvailable === 0) {
          const online = doctors.data.filter(
            (d) => (d as { availability_status?: string }).availability_status === 'available'
          ).length;
          if (online > 0) this.kpis = { ...this.kpis, doctorsAvailable: online };
        }
        this.loading = false;
        this.cdr.markForCheck();
      },
      error: () => {
        this.loading = false;
        this.cdr.markForCheck();
      },
    });
  }
}

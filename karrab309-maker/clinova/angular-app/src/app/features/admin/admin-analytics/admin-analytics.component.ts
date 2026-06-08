import { Component, OnInit, inject, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TranslateModule } from '@ngx-translate/core';
import { forkJoin } from 'rxjs';
import { ApiService } from '../../../core/services/api.service';
import { BarChartComponent } from '../../../shared/charts/bar-chart/bar-chart.component';
import { LineChartComponent } from '../../../shared/charts/line-chart/line-chart.component';
import { PieChartComponent } from '../../../shared/charts/pie-chart/pie-chart.component';
import { StatCardComponent } from '../../../shared/ui/stat-card/stat-card.component';

@Component({
  selector: 'app-admin-analytics',
  standalone: true,
  imports: [
    CommonModule, TranslateModule,
    BarChartComponent, LineChartComponent, PieChartComponent,
    StatCardComponent,
  ],
  template: `
    <div class="container-fluid px-2 px-md-3 pb-4">
      <div class="page-head">
        <div>
          <h1 class="page-title">Analytiques avancées</h1>
          <p class="page-subtitle">Indicateurs clés de performance — données live API</p>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3" *ngFor="let kpi of kpis">
          <app-stat-card
            [label]="kpi.label"
            [value]="kpi.value"
            [icon]="kpi.icon"
            [trend]="kpi.trend"
            [footer]="kpi.footer"
            [loading]="loading">
          </app-stat-card>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-12 col-lg-8">
          <app-bar-chart
            [labels]="revenueLabels"
            [values]="revenueValues"
            title="Revenus mensuels"
            subtitle="12 derniers mois (paiements validés)"
            [colors]="['var(--primary)']"
            [loading]="loading">
          </app-bar-chart>
        </div>

        <div class="col-12 col-lg-4">
          <app-pie-chart
            [labels]="statusLabels"
            [values]="statusValues"
            title="Statut des patients"
            subtitle="Répartition actuelle"
            [loading]="loading">
          </app-pie-chart>
        </div>

        <div class="col-12 col-lg-8">
          <app-line-chart
            [labels]="consultationLabels"
            [values]="consultationValues"
            title="Consultations"
            subtitle="30 derniers jours"
            [loading]="loading"
            emptyText="Aucune donnée de consultation">
          </app-line-chart>
        </div>

        <div class="col-12 col-lg-4">
          <app-pie-chart
            [labels]="doctorLabels"
            [values]="doctorValues"
            title="Charge médecins"
            subtitle="Patients assignés"
            [colors]="['var(--primary)', 'var(--violet)', 'var(--accent)', 'var(--danger)', 'var(--warning)', '#06b6d4']"
            [loading]="loading">
          </app-pie-chart>
        </div>

        <div class="col-12 col-lg-6">
          <app-bar-chart
            [labels]="admissionLabels"
            [values]="admissionValues"
            title="Entrées / Sorties"
            subtitle="Mois en cours"
            [colors]="['var(--primary)', 'var(--danger)']"
            [loading]="loading">
          </app-bar-chart>
        </div>

        <div class="col-12 col-lg-6">
          <app-pie-chart
            [labels]="alertLabels"
            [values]="alertValues"
            title="Alertes par statut"
            subtitle="Vue d'ensemble"
            [colors]="['#22c55e', '#eab308', '#ef4444', '#6366f1']"
            [loading]="loading">
          </app-pie-chart>
        </div>
      </div>
    </div>
  `,
  styles: [`:host { display: block; }`],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminAnalyticsComponent implements OnInit {
  private api = inject(ApiService);
  private cdr = inject(ChangeDetectorRef);

  loading = true;

  kpis: { label: string; value: number; icon: string; trend: 'up' | 'down' | 'neutral'; footer: string }[] = [];
  revenueLabels: string[] = [];
  revenueValues: number[] = [];
  statusLabels: string[] = [];
  statusValues: number[] = [];
  consultationLabels: string[] = [];
  consultationValues: number[] = [];
  doctorLabels: string[] = [];
  doctorValues: number[] = [];
  admissionLabels: string[] = [];
  admissionValues: number[] = [];
  alertLabels: string[] = [];
  alertValues: number[] = [];

  ngOnInit(): void {
    forkJoin({
      stats: this.api.getDashboardStats(),
      analytics: this.api.getDashboardAnalytics(),
    }).subscribe({
      next: ({ stats, analytics }) => {
        if (stats.success && stats.data) {
          const d = stats.data;
          this.kpis = [
            { label: 'Patients', value: d.patients, icon: '👥', trend: 'neutral', footer: 'Total actif' },
            { label: 'Médecins', value: d.doctors, icon: '👨‍⚕️', trend: 'neutral', footer: 'Équipe médicale' },
            { label: 'Rendez-vous', value: d.appointments, icon: '📅', trend: 'neutral', footer: 'Opérations enregistrées' },
            {
              label: 'Alertes',
              value: d.alerts,
              icon: '🔔',
              trend: d.alerts > 0 ? 'down' : 'up',
              footer: d.alerts > 0 ? 'Non traitées' : 'Aucune en attente',
            },
          ];
        }

        if (analytics.success && analytics.data) {
          const a = analytics.data;
          this.revenueLabels = a.revenue_by_month.labels;
          this.revenueValues = a.revenue_by_month.values;
          this.statusLabels = a.patients_by_status.labels;
          this.statusValues = a.patients_by_status.values;
          this.consultationLabels = a.consultations_30d.labels;
          this.consultationValues = a.consultations_30d.values;
          this.doctorLabels = a.doctor_load.labels;
          this.doctorValues = a.doctor_load.values;
          this.admissionLabels = a.admissions_month.labels;
          this.admissionValues = a.admissions_month.values;
          this.alertLabels = a.alerts_by_status.labels;
          this.alertValues = a.alerts_by_status.values;
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

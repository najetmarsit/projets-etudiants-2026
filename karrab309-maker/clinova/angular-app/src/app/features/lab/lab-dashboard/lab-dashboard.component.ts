import { Component, OnInit, inject, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../../core/services/api.service';
import { PortalService } from '../../../core/services/portal.service';
import type { LabDocument } from '../../../core/models/patient.model';
import { PatientQrWidgetComponent } from '../../patients/patient-qr-widget/patient-qr-widget.component';
import { BarChartComponent } from '../../../shared/charts/bar-chart/bar-chart.component';
import { PieChartComponent } from '../../../shared/charts/pie-chart/pie-chart.component';
import { StatCardComponent } from '../../../shared/ui/stat-card/stat-card.component';
import { SkeletonCardComponent } from '../../../shared/ui/skeleton/skeleton.component';

@Component({
  selector: 'app-lab-dashboard',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    PatientQrWidgetComponent,
    BarChartComponent,
    PieChartComponent,
    StatCardComponent,
    SkeletonCardComponent,
  ],
  templateUrl: './lab-dashboard.component.html',
  styleUrl: './lab-dashboard.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LabDashboardComponent implements OnInit {
  private api = inject(ApiService);
  private cdr = inject(ChangeDetectorRef);
  portal = inject(PortalService);

  patientsCount = 0;
  documentsCount = 0;
  documentsMonth = 0;
  recent: LabDocument[] = [];
  loading = true;
  error = '';

  docMonthLabels: string[] = [];
  docMonthValues: number[] = [];
  apptLabels: string[] = [];
  apptValues: number[] = [];

  ngOnInit(): void {
    this.loading = true;
    this.api.getLabAnalytics().subscribe({
      next: (ar) => {
        if (ar.success && ar.data) {
          this.patientsCount = ar.data.patients_referenced;
          this.documentsCount = ar.data.documents_total;
          this.documentsMonth = ar.data.documents_month;
          this.docMonthLabels = ar.data.documents_by_month.labels;
          this.docMonthValues = ar.data.documents_by_month.values;
          this.apptLabels = ar.data.appointments_by_status.labels;
          this.apptValues = ar.data.appointments_by_status.values;
        }
        this.api.getLabDocuments().subscribe({
          next: (dr) => {
            this.recent = (dr.data ?? []).slice(0, 8);
            this.loading = false;
            this.cdr.markForCheck();
          },
          error: (e) => {
            this.error = e.error?.message ?? 'Erreur chargement documents';
            this.loading = false;
            this.cdr.markForCheck();
          },
        });
      },
      error: (e) => {
        this.error = e.error?.message ?? 'Erreur';
        this.loading = false;
        this.cdr.markForCheck();
      },
    });
  }
}

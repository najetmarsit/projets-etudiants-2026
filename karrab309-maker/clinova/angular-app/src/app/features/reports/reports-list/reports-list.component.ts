import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../../core/services/api.service';
import { AuthService } from '../../../core/services/auth.service';
import { PortalService } from '../../../core/services/portal.service';
import type { Report } from '../../../core/models/patient.model';
import type { Patient } from '../../../core/models/patient.model';

@Component({
  selector: 'app-reports-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './reports-list.component.html',
  styleUrl: './reports-list.component.scss',
})
export class ReportsListComponent implements OnInit {
  private api = inject(ApiService);
  auth = inject(AuthService);
  portal = inject(PortalService);

  reports: Report[] = [];
  patients: Patient[] = [];
  loading = true;
  error = '';
  generatingForPatientId: number | null = null;
  selectedPatientId: number | null = null;

  ngOnInit(): void {
    this.loadReports();
    this.api.getPatients().subscribe({
      next: (r) => (this.patients = r.data ?? []),
      error: () => {},
    });
  }

  loadReports(): void {
    this.loading = true;
    this.error = '';
    this.api.getReports().subscribe({
      next: (r) => {
        this.reports = r.data ?? [];
        this.loading = false;
      },
      error: (err) => {
        this.error = err.error?.message ?? 'Erreur';
        this.loading = false;
      },
    });
  }

  generateForPatient(patientId: number): void {
    this.generatingForPatientId = patientId;
    this.api.createReport(patientId, 'Rapport de suivi').subscribe({
      next: () => {
        this.loadReports();
        this.generatingForPatientId = null;
      },
      error: (err) => {
        this.error = err.error?.message ?? 'Erreur';
        this.generatingForPatientId = null;
      },
    });
  }

  patientName(patientId: number): string {
    const p = this.patients.find((x) => x.id === patientId);
    return p?.user?.name ?? `Patient #${patientId}`;
  }
}

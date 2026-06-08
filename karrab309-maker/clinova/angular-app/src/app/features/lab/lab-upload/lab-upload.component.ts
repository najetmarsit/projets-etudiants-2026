import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ApiService } from '../../../core/services/api.service';
import { PortalService } from '../../../core/services/portal.service';
import { Patient } from '../../../core/models/patient.model';

@Component({
  selector: 'app-lab-upload',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './lab-upload.component.html',
  styleUrl: './lab-upload.component.scss',
})
export class LabUploadComponent implements OnInit {
  private api = inject(ApiService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  portal = inject(PortalService);

  patients: Patient[] = [];
  patientId: number | null = null;
  title = '';
  file: File | null = null;
  /** Destinataire de la notification (le PDF reste dans le dossier patient). */
  notifyTo: 'patient' | 'doctor' = 'patient';
  loading = false;
  loadingList = true;
  error = '';

  ngOnInit(): void {
    this.route.queryParamMap.subscribe((qp) => {
      const p = qp.get('patientId');
      this.patientId = p ? +p : null;
    });
    this.api.getPatients().subscribe({
      next: (r) => {
        this.patients = r.data ?? [];
        this.loadingList = false;
        if (!this.patientId && this.patients.length === 1) {
          this.patientId = this.patients[0].id;
        }
      },
      error: () => (this.loadingList = false),
    });
  }

  onFile(e: Event): void {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0];
    this.file = f && f.type === 'application/pdf' ? f : null;
    if (f && f.type !== 'application/pdf') {
      this.error = 'Veuillez choisir un fichier PDF.';
    }
  }

  submit(): void {
    this.error = '';
    if (!this.patientId || !this.title.trim() || !this.file) {
      this.error = 'Patient, titre et fichier PDF sont requis.';
      return;
    }
    this.loading = true;
    this.api.uploadLabDocument(this.patientId, this.title.trim(), this.file, this.notifyTo).subscribe({
      next: () => {
        this.router.navigate([this.portal.prefix(), 'documents']);
      },
      error: (err) => {
        this.error = err.error?.message ?? 'Échec de l’envoi';
        this.loading = false;
      },
    });
  }
}

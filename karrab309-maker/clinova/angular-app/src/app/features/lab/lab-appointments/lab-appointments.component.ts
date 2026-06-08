import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService, LabAppointment } from '../../../core/services/api.service';

@Component({
  selector: 'app-lab-appointments',
  standalone: true,
  imports: [CommonModule, FormsModule, TranslateModule],
  templateUrl: './lab-appointments.component.html',
  styleUrl: './lab-appointments.component.scss',
})
export class LabAppointmentsComponent implements OnInit {
  private api = inject(ApiService);

  rows: LabAppointment[] = [];
  loading = false;
  error: string | null = null;
  updatingId: number | null = null;

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = null;
    this.api.getLabAppointments().subscribe({
      next: (r) => {
        this.loading = false;
        if (r.success && r.data) {
          this.rows = r.data;
        } else {
          this.error = 'Erreur';
        }
      },
      error: () => {
        this.loading = false;
        this.error = 'Erreur réseau';
      },
    });
  }

  setStatus(id: number, status: string): void {
    this.updatingId = id;
    this.api.patchLabAppointment(id, { status }).subscribe({
      next: () => {
        this.updatingId = null;
        this.load();
      },
      error: () => {
        this.updatingId = null;
      },
    });
  }
}

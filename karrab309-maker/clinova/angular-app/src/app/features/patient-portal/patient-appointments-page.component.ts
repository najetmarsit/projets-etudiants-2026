import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService, LabAppointment } from '../../core/services/api.service';
import { AppTranslateService } from '../../core/services/translate.service';
import { ClinPageHeadComponent } from '../../shared/ui/clin-page-head/clin-page-head.component';

@Component({
  selector: 'app-patient-appointments-page',
  standalone: true,
  imports: [CommonModule, FormsModule, TranslateModule, ClinPageHeadComponent],
  templateUrl: './patient-appointments-page.component.html',
  styleUrl: './patient-appointments-page.component.scss',
})
export class PatientAppointmentsPageComponent implements OnInit {
  private api = inject(ApiService);
  private i18n = inject(AppTranslateService);

  labAppointments: LabAppointment[] = [];
  labLoading = false;
  labLoadError = false;
  labSaving = false;
  labScheduled = '';
  labNote = '';

  /** Premier jour du mois affiché (calendrier) */
  calendarMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);

  ngOnInit(): void {
    void this.loadLab();
  }

  /** Libellés colonnes Lundi → Dimanche (étroits) */
  weekdayHeaders(): string[] {
    const loc = this.localeTag();
    const fmt = new Intl.DateTimeFormat(loc, { weekday: 'narrow' });
    const out: string[] = [];
    for (let i = 0; i < 7; i++) {
      const d = new Date(2024, 0, 1 + i);
      out.push(fmt.format(d));
    }
    return out;
  }

  monthHeading(): string {
    return new Intl.DateTimeFormat(this.localeTag(), { month: 'long', year: 'numeric' }).format(this.calendarMonth);
  }

  shiftMonth(delta: number): void {
    const y = this.calendarMonth.getFullYear();
    const m = this.calendarMonth.getMonth();
    this.calendarMonth = new Date(y, m + delta, 1);
  }

  calendarCells(): { day: number | null; isToday: boolean; hasLab: boolean }[] {
    const y = this.calendarMonth.getFullYear();
    const m = this.calendarMonth.getMonth();
    const first = new Date(y, m, 1);
    const firstMon0 = (first.getDay() + 6) % 7;
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const cells: { day: number | null; isToday: boolean; hasLab: boolean }[] = [];
    const today = new Date();
    for (let i = 0; i < firstMon0; i++) {
      cells.push({ day: null, isToday: false, hasLab: false });
    }
    for (let d = 1; d <= daysInMonth; d++) {
      const date = new Date(y, m, d);
      cells.push({
        day: d,
        isToday: this.sameDay(date, today),
        hasLab: this.labAppointments.some((a) => a.scheduled_at && this.sameDay(this.parseApptDate(a.scheduled_at), date)),
      });
    }
    while (cells.length % 7 !== 0) {
      cells.push({ day: null, isToday: false, hasLab: false });
    }
    return cells;
  }

  private localeTag(): string {
    switch (this.i18n.current()) {
      case 'ar':
        return 'ar';
      case 'fr':
        return 'fr-FR';
      default:
        return 'en-GB';
    }
  }

  private sameDay(a: Date, b: Date): boolean {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
  }

  private parseApptDate(raw: string): Date {
    const s = raw.includes(' ') && !raw.includes('T') ? raw.replace(' ', 'T') : raw;
    const d = new Date(s);
    return Number.isNaN(d.getTime()) ? new Date(0) : d;
  }

  private loadLab(): void {
    this.labLoading = true;
    this.labLoadError = false;
    this.api.getLabAppointments().subscribe({
      next: (r) => {
        this.labLoading = false;
        if (r.success && r.data) {
          this.labAppointments = r.data;
          this.labLoadError = false;
        } else {
          this.labAppointments = [];
          this.labLoadError = !r.success;
        }
      },
      error: () => {
        this.labLoading = false;
        this.labLoadError = true;
        this.labAppointments = [];
      },
    });
  }

  submitLabAppointment(): void {
    if (!this.labScheduled) return;
    this.labSaving = true;
    const iso = new Date(this.labScheduled).toISOString();
    this.api.postLabAppointment({ scheduled_at: iso, patient_note: this.labNote || undefined }).subscribe({
      next: () => {
        this.labSaving = false;
        this.labNote = '';
        void this.loadLab();
      },
      error: () => (this.labSaving = false),
    });
  }

  cancelLabAppointment(a: LabAppointment): void {
    if (a.status !== 'pending') return;
    this.api.cancelLabAppointment(a.id).subscribe({ next: () => void this.loadLab() });
  }
}

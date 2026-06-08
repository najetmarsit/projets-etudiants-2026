import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, ActivatedRoute } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../../core/services/api.service';
import { AuthService } from '../../../core/services/auth.service';
import { PortalService } from '../../../core/services/portal.service';
import { AppTranslateService } from '../../../core/services/translate.service';
import { Patient } from '../../../core/models/patient.model';
import { ClinPageHeadComponent } from '../../../shared/ui/clin-page-head/clin-page-head.component';

@Component({
  selector: 'app-patient-list',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule, ClinPageHeadComponent],
  templateUrl: './patient-list.component.html',
  styleUrl: './patient-list.component.scss',
})
export class PatientListComponent implements OnInit {
  private api = inject(ApiService);
  private translate = inject(AppTranslateService);
  auth = inject(AuthService);
  portal = inject(PortalService);
  private route = inject(ActivatedRoute);

  patients: Patient[] = [];
  loading = true;
  loadingMore = false;
  nextCursor: string | null = null;
  hasMore = false;
  error = '';
  searchQuery = '';

  ngOnInit(): void {
    this.route.queryParams.subscribe((qp) => {
      this.searchQuery = (qp['search'] ?? '').trim().toLowerCase();
    });
    this.load();
  }

  get filteredPatients(): Patient[] {
    if (!this.searchQuery) return this.patients;
    const q = this.searchQuery;
    return this.patients.filter((p) => {
      const name = [p.first_name, p.last_name].filter(Boolean).join(' ').toLowerCase() || (p.user?.name ?? '').toLowerCase();
      const email = (p.user?.email ?? '').toLowerCase();
      return name.includes(q) || email.includes(q);
    });
  }

  load(): void {
    this.loading = true;
    this.error = '';
    this.nextCursor = null;
    this.api.getPatients({ perPage: 40, forceRefresh: true }).subscribe({
      next: (r) => {
        this.patients = r.data ?? [];
        this.nextCursor = r.meta?.next_cursor ?? null;
        this.hasMore = !!r.meta?.has_more;
        this.loading = false;
      },
      error: (err) => {
        this.error =
          this.translate.apiErrorMessage(err.error?.message) || this.translate.instant('STAFF.PATIENTS_LOAD_ERROR');
        this.loading = false;
      },
    });
  }

  loadMore(): void {
    if (!this.nextCursor || this.loadingMore || this.loading) return;
    this.loadingMore = true;
    this.api.getPatients({ perPage: 40, cursor: this.nextCursor }).subscribe({
      next: (r) => {
        const batch = r.data ?? [];
        this.patients = [...this.patients, ...batch];
        this.nextCursor = r.meta?.next_cursor ?? null;
        this.hasMore = !!r.meta?.has_more;
        this.loadingMore = false;
      },
      error: () => (this.loadingMore = false),
    });
  }

  canAdd(): boolean {
    return this.auth.isAdmin() || this.auth.hasRole('Secretary');
  }

  isLabPortal(): boolean {
    return this.auth.isLaboratory();
  }

  canDelete(): boolean {
    return this.auth.isAdmin();
  }

  deletePatient(id: number, event: Event): void {
    event.preventDefault();
    event.stopPropagation();
    if (!confirm('Supprimer ce patient ?')) return;
    this.api.deletePatient(id).subscribe({
      next: () => this.load(),
      error: (err) => (this.error = this.translate.apiErrorMessage(err.error?.message)),
    });
  }
}

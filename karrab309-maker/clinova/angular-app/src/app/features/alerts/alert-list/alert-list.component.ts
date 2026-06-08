import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../../core/services/api.service';
import { PortalService } from '../../../core/services/portal.service';
import { AppTranslateService } from '../../../core/services/translate.service';
import { Alert } from '../../../core/models/patient.model';
import { ClinPageHeadComponent } from '../../../shared/ui/clin-page-head/clin-page-head.component';

@Component({
  selector: 'app-alert-list',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule, ClinPageHeadComponent],
  templateUrl: './alert-list.component.html',
  styleUrl: './alert-list.component.scss',
})
export class AlertListComponent implements OnInit {
  private api = inject(ApiService);
  private translate = inject(AppTranslateService);
  readonly portal = inject(PortalService);

  alerts: Alert[] = [];
  loading = true;
  error = '';

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading = true;
    this.error = '';
    this.api.getAlerts().subscribe({
      next: (r) => {
        this.alerts = r.data ?? [];
        this.loading = false;
      },
      error: (err) => {
        this.error = this.translate.apiErrorMessage(err.error?.message) || this.translate.instant('STAFF.ALERTS_LOAD_ERROR');
        this.loading = false;
      },
    });
  }

  acknowledge(id: number): void {
    this.api.acknowledgeAlert(id).subscribe({
      next: () => this.load(),
      error: (err) => {
        this.error = this.translate.apiErrorMessage(err.error?.message) || this.translate.instant('COMMON.ERROR');
      },
    });
  }
}

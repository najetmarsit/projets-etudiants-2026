import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../../core/services/api.service';
import { AuthService } from '../../../core/services/auth.service';
import { PortalService } from '../../../core/services/portal.service';
import { AppTranslateService } from '../../../core/services/translate.service';
import { HealthIndicator } from '../../../core/models/patient.model';
import { ClinPageHeadComponent } from '../../../shared/ui/clin-page-head/clin-page-head.component';

@Component({
  selector: 'app-health-indicator-list',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule, ClinPageHeadComponent],
  templateUrl: './health-indicator-list.component.html',
  styleUrl: './health-indicator-list.component.scss',
})
export class HealthIndicatorListComponent implements OnInit {
  private api = inject(ApiService);
  private translate = inject(AppTranslateService);
  readonly portal = inject(PortalService);
  readonly auth = inject(AuthService);

  indicators: HealthIndicator[] = [];
  loading = true;
  error = '';

  ngOnInit(): void {
    this.api.getHealthIndicators().subscribe({
      next: (r) => {
        this.indicators = r.data ?? [];
        this.loading = false;
      },
      error: (err) => {
        this.error = this.translate.apiErrorMessage(err.error?.message) || this.translate.instant('STAFF.VITALS_LOAD_ERROR');
        this.loading = false;
      },
    });
  }
}

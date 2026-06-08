import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../../core/services/api.service';
import { PortalService } from '../../../core/services/portal.service';
import { AppTranslateService } from '../../../core/services/translate.service';
import { Operation } from '../../../core/models/patient.model';
import { ClinPageHeadComponent } from '../../../shared/ui/clin-page-head/clin-page-head.component';

@Component({
  selector: 'app-operation-list',
  standalone: true,
  imports: [CommonModule, RouterLink, TranslateModule, ClinPageHeadComponent],
  templateUrl: './operation-list.component.html',
  styleUrl: './operation-list.component.scss',
})
export class OperationListComponent implements OnInit {
  private api = inject(ApiService);
  readonly portal = inject(PortalService);
  private translate = inject(AppTranslateService);

  operations: Operation[] = [];
  loading = true;
  error = '';

  ngOnInit(): void {
    this.api.getOperations().subscribe({
      next: (r) => {
        this.operations = r.data ?? [];
        this.loading = false;
      },
      error: (err) => {
        this.error = this.translate.apiErrorMessage(err.error?.message) || this.translate.instant('STAFF.OPERATIONS_LOAD_ERROR');
        this.loading = false;
      },
    });
  }
}

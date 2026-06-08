import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TranslateModule } from '@ngx-translate/core';
import { ApiService } from '../../core/services/api.service';
import { ClinPageHeadComponent } from '../../shared/ui/clin-page-head/clin-page-head.component';
import { NotificationItem } from '../../core/models/notification.model';

@Component({
  selector: 'app-patient-notifications-page',
  standalone: true,
  imports: [CommonModule, TranslateModule, ClinPageHeadComponent],
  templateUrl: './patient-notifications-page.component.html',
  styleUrl: './patient-notifications-page.component.scss',
})
export class PatientNotificationsPageComponent implements OnInit {
  private api = inject(ApiService);

  notifications: NotificationItem[] = [];
  loading = false;
  loadError = false;

  ngOnInit(): void {
    this.loading = true;
    this.loadError = false;
    this.api.getNotifications({ limit: 80 }).subscribe({
      next: (r) => {
        this.loading = false;
        this.notifications = r.data ?? [];
        this.loadError = false;
      },
      error: () => {
        this.loading = false;
        this.loadError = true;
        this.notifications = [];
      },
    });
  }
}
